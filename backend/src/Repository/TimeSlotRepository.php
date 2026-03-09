<?php

namespace App\Repository;

use App\Entity\TimeSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Entity\Appointment;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimeSlot>
 */
class TimeSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeSlot::class);
    }

    public function findAvailableSlotsForDoctor(int $doctorId): array
    {
        return $this->createQueryBuilder('ts')
            ->where('ts.doctor = :doctorId')
            ->andWhere('ts.startAt > :now')
            ->andWhere(
                'NOT EXISTS (
                SELECT 1
                FROM App\Entity\Appointment a
                JOIN a.timeSlot ats
                WHERE ats.doctor = ts.doctor
                AND ats.startAt < ts.endAt
                AND ats.endAt > ts.startAt
            )'
            )
            ->setParameter('doctorId', $doctorId)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('ts.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForBooking(int $id): ?TimeSlot
    {
        return $this->createQueryBuilder('ts')
            ->andWhere('ts.id = :id')
            ->andWhere('ts.startAt > :now')
            ->setParameter('id', $id)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns a hashtable keyed by 'Y-m-d H:i' for all existing slots
     * of a doctor in [from, to). Used by TimeSlotGenerator for O(1) de-dup.
     */
    public function findExistingSlotStartTimes(
        int $doctorId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        $rows = $this->createQueryBuilder('ts')
            ->select('ts.startAt')
            ->where('ts.doctor = :doctorId')
            ->andWhere('ts.startAt >= :from')
            ->andWhere('ts.startAt < :to')
            ->setParameter('doctorId', $doctorId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();

        $keys = [];
        foreach ($rows as $row) {
            $keys[$row['startAt']->format('Y-m-d H:i')] = true;
        }

        return $keys;
    }

    public function deleteOldUnbookedSlots(\DateTimeImmutable $cutoff): int
    {
        return $this->createQueryBuilder('ts')
            ->delete()
            ->where('ts.endAt < :cutoff')
            ->andWhere('ts.id NOT IN (
            SELECT IDENTITY(a.timeSlot)
            FROM App\Entity\Appointment a
        )')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->execute();
    }

    /**
     * Deletes unbooked time slots for a doctor within a date range [from, to] (inclusive by date).
     */
    public function deleteUnbookedSlotsInRange(
        int $doctorId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): int {
        $rangeStart = $from->setTime(0, 0, 0);
        $rangeEnd   = $to->setTime(23, 59, 59);

        return $this->createQueryBuilder('ts')
            ->delete()
            ->where('ts.doctor = :doctorId')
            ->andWhere('ts.startAt >= :rangeStart')
            ->andWhere('ts.startAt <= :rangeEnd')
            ->andWhere('ts.isBooked = false')
            ->setParameter('doctorId', $doctorId)
            ->setParameter('rangeStart', $rangeStart)
            ->setParameter('rangeEnd', $rangeEnd)
            ->getQuery()
            ->execute();
    }

    /**
     * Deletes all future unbooked slots for a doctor (used when schedule changes).
     */
    public function deleteAllFutureUnbookedForDoctor(int $doctorId): int
    {
        return $this->createQueryBuilder('ts')
            ->delete()
            ->where('ts.doctor = :doctorId')
            ->andWhere('ts.startAt > :now')
            ->andWhere('ts.isBooked = false')
            ->setParameter('doctorId', $doctorId)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function hasOverlappingAppointment(TimeSlot $slot): bool
    {
        return (bool) $this->getEntityManager()
            ->createQueryBuilder()
            ->select('1')
            ->from(Appointment::class, 'a')
            ->join('a.timeSlot', 'ts')
            ->where('ts.doctor = :doctor')
            ->andWhere('ts.startAt < :end')
            ->andWhere('ts.endAt > :start')
            ->setParameter('doctor', $slot->getDoctor())
            ->setParameter('start', $slot->getStartAt())
            ->setParameter('end', $slot->getEndAt())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
