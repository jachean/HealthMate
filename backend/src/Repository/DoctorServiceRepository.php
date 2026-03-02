<?php

namespace App\Repository;

use App\Entity\DoctorService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DoctorService>
 */
class DoctorServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DoctorService::class);
    }

    /**
     * @return DoctorService[]
     */
    public function findByDoctor(int $doctorId): array
    {
        return $this->createQueryBuilder('ds')
            ->innerJoin('ds.medicalService', 'ms')
            ->addSelect('ms')
            ->leftJoin('ms.specialty', 'spec')
            ->addSelect('spec')
            ->andWhere('ds.doctor = :doctorId')
            ->setParameter('doctorId', $doctorId)
            ->orderBy('ms.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
