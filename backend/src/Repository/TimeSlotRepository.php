<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\TimeSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findAvailableSlotsForDoctor(int $doctorId, int $requestedDurationMinutes = 60): array
    {
        $now = new \DateTimeImmutable();

        $slots = $this->createQueryBuilder('ts')
            ->where('ts.doctor = :doctorId')
            ->andWhere('ts.startAt > :now')
            ->andWhere('ts.isBooked = false')
            ->setParameter('doctorId', $doctorId)
            ->setParameter('now', $now)
            ->orderBy('ts.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        // Fetch all active (non-cancelled) appointments for this doctor
        // with their actual service durations to compute true blocked periods.
        $bookedPeriods = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('ats.startAt AS start, ds.durationMinutes AS duration')
            ->from(Appointment::class, 'a')
            ->join('a.timeSlot', 'ats')
            ->join('a.doctorService', 'ds')
            ->where('ats.doctor = :doctorId')
            ->andWhere('a.status != :cancelled')
            ->andWhere('ats.startAt > :now')
            ->setParameter('doctorId', $doctorId)
            ->setParameter('cancelled', Appointment::STATUS_CANCELLED)
            ->setParameter('now', $now)
            ->getQuery()
            ->getArrayResult();

        return array_values(array_filter(
            $slots,
            function (TimeSlot $slot) use ($bookedPeriods, $requestedDurationMinutes): bool {
                $slotStart = $slot->getStartAt();
                $slotEnd   = $slotStart->modify("+{$requestedDurationMinutes} minutes");

                foreach ($bookedPeriods as $period) {
                    /** @var \DateTimeImmutable $bookedStart */
                    $bookedStart = $period['start'];
                    $bookedEnd   = $bookedStart->modify("+{$period['duration']} minutes");

                    // Two intervals overlap when one starts before the other ends.
                    if ($bookedStart < $slotEnd && $bookedEnd > $slotStart) {
                        return false;
                    }
                }

                return true;
            }
        ));
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

    public function hasOverlappingAppointment(TimeSlot $slot, int $serviceDurationMinutes = 60): bool
    {
        $slotStart = $slot->getStartAt();
        $slotEnd   = $slotStart->modify("+{$serviceDurationMinutes} minutes");

        $bookedPeriods = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('ats.startAt AS start, ds.durationMinutes AS duration')
            ->from(Appointment::class, 'a')
            ->join('a.timeSlot', 'ats')
            ->join('a.doctorService', 'ds')
            ->where('ats.doctor = :doctor')
            ->andWhere('a.status != :cancelled')
            ->setParameter('doctor', $slot->getDoctor())
            ->setParameter('cancelled', Appointment::STATUS_CANCELLED)
            ->getQuery()
            ->getArrayResult();

        foreach ($bookedPeriods as $period) {
            /** @var \DateTimeImmutable $bookedStart */
            $bookedStart = $period['start'];
            $bookedEnd   = $bookedStart->modify("+{$period['duration']} minutes");

            if ($bookedStart < $slotEnd && $bookedEnd > $slotStart) {
                return true;
            }
        }

        return false;
    }
}
