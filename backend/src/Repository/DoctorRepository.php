<?php

namespace App\Repository;

use App\Entity\Doctor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
        ?string $city,
        ?\DateTimeImmutable $availableFrom = null,
        ?\DateTimeImmutable $availableTo = null,
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

        if ($availableFrom !== null && $availableTo !== null) {
            $qb->andWhere(
                'EXISTS (
                    SELECT 1 FROM App\Entity\TimeSlot ts2
                    WHERE ts2.doctor = d
                    AND ts2.startAt >= :avFrom
                    AND ts2.startAt < :avTo
                    AND ts2.startAt > :avNow
                    AND NOT EXISTS (
                        SELECT 1 FROM App\Entity\Appointment appt
                        JOIN appt.timeSlot ats2
                        WHERE ats2.doctor = ts2.doctor
                        AND ats2.startAt < ts2.endAt
                        AND ats2.endAt > ts2.startAt
                    )
                )'
            )
                ->setParameter('avFrom', $availableFrom)
                ->setParameter('avTo', $availableTo)
                ->setParameter('avNow', new \DateTimeImmutable());
        }

        return $qb
            ->orderBy('d.lastName', 'ASC')
            ->addOrderBy('d.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return int[] */
    public function findActiveIds(): array
    {
        return array_column(
            $this->createQueryBuilder('d')
                ->select('d.id')
                ->where('d.isActive = :active')
                ->setParameter('active', true)
                ->getQuery()
                ->getArrayResult(),
            'id'
        );
    }

    /** @return Doctor[] */
    public function findDoctorsWithoutUser(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.clinic', 'c')
            ->addSelect('c')
            ->where('d.user IS NULL')
            ->orderBy('d.lastName', 'ASC')
            ->addOrderBy('d.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllPaginatedForAdmin(int $page, int $limit, ?string $search, ?int $clinicId = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.clinic', 'c')
            ->leftJoin('d.specialties', 's')
            ->leftJoin('d.doctorServices', 'ds')
            ->addSelect('c', 's', 'ds');

        if ($clinicId !== null) {
            $qb->andWhere('d.clinic = :clinic')->setParameter('clinic', $clinicId);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(d.firstName)', ':search'),
                    $qb->expr()->like('LOWER(d.lastName)', ':search'),
                    $qb->expr()->like('LOWER(c.name)', ':search')
                )
            )->setParameter('search', '%' . strtolower($search) . '%');
        }

        $qb->orderBy('d.lastName', 'ASC')
            ->addOrderBy('d.firstName', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, fetchJoinCollection: true);

        return [
            'data' => iterator_to_array($paginator),
            'total' => count($paginator),
        ];
    }
}
