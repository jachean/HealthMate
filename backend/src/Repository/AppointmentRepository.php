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

    public function findAllPaginatedForAdmin(int $page, int $limit, array $filters): array
    {
        $qb = $this->createQueryBuilder('a')
            ->join('a.timeSlot', 'ts')
            ->join('ts.doctor', 'd')
            ->join('d.clinic', 'c')
            ->join('a.user', 'u')
            ->join('a.doctorService', 'ds')
            ->join('ds.medicalService', 'ms')
            ->orderBy('ts.startAt', 'DESC');

        if (!empty($filters['dateFrom'])) {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $filters['dateFrom']);
            if ($from !== false) {
                $qb->andWhere('ts.startAt >= :dateFrom')
                    ->setParameter('dateFrom', $from->setTime(0, 0));
            }
        }

        if (!empty($filters['dateTo'])) {
            $to = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $filters['dateTo']);
            if ($to !== false) {
                $qb->andWhere('ts.startAt <= :dateTo')
                    ->setParameter('dateTo', $to->setTime(23, 59, 59));
            }
        }

        if (!empty($filters['doctorId'])) {
            $qb->andWhere('d.id = :doctorId')
                ->setParameter('doctorId', (int) $filters['doctorId']);
        }

        if (!empty($filters['clinicId'])) {
            $qb->andWhere('c.id = :clinicId')
                ->setParameter('clinicId', (int) $filters['clinicId']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', (string) $filters['status']);
        }

        if (!empty($filters['patient'])) {
            $qb->andWhere(
                'LOWER(CONCAT(u.firstName, \' \', u.lastName)) LIKE :patient OR LOWER(u.email) LIKE :patient'
            )->setParameter('patient', '%' . mb_strtolower((string) $filters['patient']) . '%');
        }

        $total = (int) (clone $qb)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();

        $data = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['data' => $data, 'total' => $total];
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
