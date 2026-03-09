<?php

namespace App\Repository;

use App\Entity\Doctor;
use App\Entity\DoctorUnavailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DoctorUnavailability>
 */
class DoctorUnavailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DoctorUnavailability::class);
    }

    /** @return DoctorUnavailability[] */
    public function findForDoctor(Doctor $doctor): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.doctor = :doctor')
            ->setParameter('doctor', $doctor)
            ->orderBy('u.dateFrom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** Returns any periods that overlap with [from, to] for this doctor. */
    public function findOverlapping(
        Doctor $doctor,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        ?int $excludeId = null
    ): array {
        $qb = $this->createQueryBuilder('u')
            ->where('u.doctor = :doctor')
            ->andWhere('u.dateFrom <= :to')
            ->andWhere('u.dateTo >= :from')
            ->setParameter('doctor', $doctor)
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if ($excludeId !== null) {
            $qb->andWhere('u.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }
}
