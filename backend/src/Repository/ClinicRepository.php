<?php

namespace App\Repository;

use App\Entity\Clinic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Clinic>
 */
class ClinicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Clinic::class);
    }
    public function findForFilters(?string $city): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($city !== null) {
            $qb->andWhere('c.city = :city')
                ->setParameter('city', $city);
        }

        return $qb
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
