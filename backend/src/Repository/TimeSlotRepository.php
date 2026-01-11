<?php

namespace App\Repository;

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
}
