<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function existsForTimeSlot(int $timeSlotId): bool
    {
        return (bool) $this->createQueryBuilder('a')
            ->select('1')
            ->where('a.timeSlot = :slot')
            ->setParameter('slot', $timeSlotId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForUserOrderedByStartAt(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.timeSlot', 'ts')
            ->where('a.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ts.startAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
