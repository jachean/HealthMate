<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * Returns reviews for a doctor, newest first.
     */
    public function findByDoctor(int $doctorId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.doctor = :doctorId')
            ->setParameter('doctorId', $doctorId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a review already exists for an appointment.
     */
    public function findByAppointment(int $appointmentId): ?Review
    {
        return $this->createQueryBuilder('r')
            ->where('r.appointment = :appointmentId')
            ->setParameter('appointmentId', $appointmentId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
