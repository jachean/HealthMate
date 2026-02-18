<?php

namespace App\Repository;

use App\Entity\Doctor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Doctor>
 */
class DoctorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Doctor::class);
    }

    public function findActiveDoctorsWithFilters(
        ?int $clinicId,
        ?string $specialtySlug,
        ?bool $acceptsInsurance,
        ?string $city
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.clinic', 'c')
            ->leftJoin('d.specialties', 's')
            ->leftJoin('d.doctorServices', 'ds')
            ->addSelect('c', 's', 'ds')
            ->andWhere('d.isActive = true');

        if ($clinicId !== null) {
            $qb
                ->andWhere('c.id = :clinicId')
                ->setParameter('clinicId', $clinicId);
        }

        if ($city !== null) {
            $qb
                ->andWhere('c.city = :city')
                ->setParameter('city', $city);
        }

        if ($acceptsInsurance !== null) {
            $qb
                ->andWhere('d.acceptsInsurance = :ai')
                ->setParameter('ai', $acceptsInsurance);
        }

        if ($specialtySlug !== null) {
            $qb->andWhere(
                $qb->expr()->exists(
                    $this->createQueryBuilder('d2')
                        ->select('1')
                        ->innerJoin('d2.specialties', 's2')
                        ->where('d2 = d')
                        ->andWhere('s2.slug = :slug')
                        ->getDQL()
                )
            )
                ->setParameter('slug', $specialtySlug);
        }

        return $qb
            ->orderBy('d.lastName', 'ASC')
            ->addOrderBy('d.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
