<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\Doctor;
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

    /**
     * Returns booked appointments whose slot starts within the next 24 hours
     * and for which no reminder has been sent yet.
     *
     * @return Appointment[]
     */
    public function findPendingReminders(): array
    {
        $now  = new \DateTimeImmutable();
        $in24 = $now->modify('+24 hours');

        return $this->createQueryBuilder('a')
            ->join('a.timeSlot', 'ts')
            ->where('a.status = :status')
            ->andWhere('a.reminderSentAt IS NULL')
            ->andWhere('ts.startAt > :now')
            ->andWhere('ts.startAt <= :in24')
            ->setParameter('status', Appointment::STATUS_BOOKED)
            ->setParameter('now', $now)
            ->setParameter('in24', $in24)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all non-cancelled appointments for a given doctor on a specific date, ordered by slot start ASC.
     *
     * @return Appointment[]
     */
    public function findForDoctorByDate(Doctor $doctor, \DateTimeImmutable $date): array
    {
        $dayStart = $date->setTime(0, 0, 0);
        $dayEnd   = $dayStart->modify('+1 day');

        return $this->createQueryBuilder('a')
            ->join('a.timeSlot', 'ts')
            ->join('a.doctorService', 'ds')
            ->join('ds.medicalService', 'ms')
            ->join('a.user', 'u')
            ->addSelect('ts', 'ds', 'ms', 'u')
            ->where('ts.doctor = :doctor')
            ->andWhere('ts.startAt >= :dayStart')
            ->andWhere('ts.startAt < :dayEnd')
            ->andWhere('a.status != :cancelled')
            ->setParameter('doctor', $doctor)
            ->setParameter('dayStart', $dayStart)
            ->setParameter('dayEnd', $dayEnd)
            ->setParameter('cancelled', Appointment::STATUS_CANCELLED)
            ->orderBy('ts.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns subsequent booked/in_progress appointments for a doctor today, after a given slot start time.
     *
     * @return Appointment[]
     */
    public function findSubsequentTodayForDoctor(Doctor $doctor, \DateTimeImmutable $afterSlotStart): array
    {
        $todayStart = new \DateTimeImmutable('today midnight');
        $todayEnd   = $todayStart->modify('+1 day');

        return $this->createQueryBuilder('a')
            ->join('a.timeSlot', 'ts')
            ->join('a.user', 'u')
            ->join('a.doctorService', 'ds')
            ->join('ds.medicalService', 'ms')
            ->addSelect('ts', 'u', 'ds', 'ms')
            ->where('ts.doctor = :doctor')
            ->andWhere('ts.startAt > :afterSlotStart')
            ->andWhere('ts.startAt >= :todayStart')
            ->andWhere('ts.startAt < :todayEnd')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('doctor', $doctor)
            ->setParameter('afterSlotStart', $afterSlotStart)
            ->setParameter('todayStart', $todayStart)
            ->setParameter('todayEnd', $todayEnd)
            ->setParameter('statuses', [Appointment::STATUS_BOOKED, Appointment::STATUS_IN_PROGRESS])
            ->orderBy('ts.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findInProgressForDoctor(Doctor $doctor): ?Appointment
    {
        return $this->createQueryBuilder('a')
            ->join('a.timeSlot', 'ts')
            ->where('ts.doctor = :doctor')
            ->andWhere('a.status = :status')
            ->setParameter('doctor', $doctor)
            ->setParameter('status', Appointment::STATUS_IN_PROGRESS)
            ->setMaxResults(1)
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
