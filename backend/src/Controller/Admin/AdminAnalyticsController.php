<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\Clinic;
use App\Entity\Review;
use App\Service\ClinicAdminContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/analytics')]
#[IsGranted('ROLE_CLINIC_ADMIN')]
class AdminAnalyticsController extends AdminController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ClinicAdminContext $ctx,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'month');
        $clinic = $this->ctx->getClinic();

        return $this->json([
            'summary'              => $this->getSummary($clinic),
            'appointmentsByPeriod' => $this->getAppointmentsByPeriod($period, $clinic),
            'topDoctors'           => $this->getTopDoctors($clinic),
            'topSpecialties'       => $this->getTopSpecialties($clinic),
            'clinicOccupancy'      => $this->getClinicOccupancy($clinic),
            'reviewScores'         => $this->getReviewScores($clinic),
        ]);
    }

    private function getSummary(?Clinic $clinic): array
    {
        $conn = $this->em->getConnection();

        if ($clinic !== null) {
            $apt = $conn->fetchAssociative('
                SELECT
                    COUNT(*) AS total,
                    SUM(a.status = :booked)    AS booked,
                    SUM(a.status = :cancelled) AS cancelled
                FROM appointment a
                INNER JOIN time_slot ts ON a.time_slot_id = ts.id
                INNER JOIN doctor d ON ts.doctor_id = d.id
                WHERE d.clinic_id = :clinicId
            ', [
                'booked'    => Appointment::STATUS_BOOKED,
                'cancelled' => Appointment::STATUS_CANCELLED,
                'clinicId'  => $clinic->getId(),
            ]);

            $rev = $conn->fetchAssociative('
                SELECT COUNT(*) AS total, AVG(r.rating) AS avg_rating
                FROM review r
                INNER JOIN doctor d ON r.doctor_id = d.id
                WHERE d.clinic_id = :clinicId
            ', ['clinicId' => $clinic->getId()]);
        } else {
            $apt = $conn->fetchAssociative('
                SELECT
                    COUNT(*) AS total,
                    SUM(status = :booked)    AS booked,
                    SUM(status = :cancelled) AS cancelled
                FROM appointment
            ', [
                'booked'    => Appointment::STATUS_BOOKED,
                'cancelled' => Appointment::STATUS_CANCELLED,
            ]);

            $rev = $conn->fetchAssociative('SELECT COUNT(*) AS total, AVG(rating) AS avg_rating FROM review');
        }

        $total     = (int) $apt['total'];
        $cancelled = (int) $apt['cancelled'];

        return [
            'totalAppointments'     => $total,
            'bookedAppointments'    => (int) $apt['booked'],
            'cancelledAppointments' => $cancelled,
            'cancellationRate'      => $total > 0 ? round($cancelled / $total * 100, 1) : 0,
            'totalReviews'          => (int) $rev['total'],
            'averageRating'         => $rev['avg_rating'] !== null ? round((float) $rev['avg_rating'], 2) : null,
        ];
    }

    private function getAppointmentsByPeriod(string $period, ?Clinic $clinic): array
    {
        $conn = $this->em->getConnection();
        $clinicJoin  = $clinic !== null
            ? 'INNER JOIN time_slot ts ON a.time_slot_id = ts.id INNER JOIN doctor d ON ts.doctor_id = d.id'
            : '';
        $clinicWhere = $clinic !== null ? 'AND d.clinic_id = :clinicId' : '';

        switch ($period) {
            case 'day':
                $from = (new \DateTime())->modify('-30 days')->format('Y-m-d');
                $sql  = "
                    SELECT DATE(a.created_at) AS label, a.status, COUNT(*) AS count
                    FROM appointment a
                    $clinicJoin
                    WHERE a.created_at >= :from $clinicWhere
                    GROUP BY DATE(a.created_at), a.status
                    ORDER BY label ASC
                ";
                break;

            case 'week':
                $from = (new \DateTime())->modify('-84 days')->format('Y-m-d');
                $sql  = "
                    SELECT CONCAT(YEAR(a.created_at), '-W', LPAD(WEEK(a.created_at, 1), 2, '0')) AS label,
                           a.status, COUNT(*) AS count
                    FROM appointment a
                    $clinicJoin
                    WHERE a.created_at >= :from $clinicWhere
                    GROUP BY YEAR(a.created_at), WEEK(a.created_at, 1), a.status
                    ORDER BY YEAR(a.created_at) ASC, WEEK(a.created_at, 1) ASC
                ";
                break;

            default: // month
                $from = (new \DateTime())->modify('-365 days')->format('Y-m-d');
                $sql  = "
                    SELECT DATE_FORMAT(a.created_at, '%Y-%m') AS label, a.status, COUNT(*) AS count
                    FROM appointment a
                    $clinicJoin
                    WHERE a.created_at >= :from $clinicWhere
                    GROUP BY DATE_FORMAT(a.created_at, '%Y-%m'), a.status
                    ORDER BY label ASC
                ";
        }

        $params = ['from' => $from];
        if ($clinic !== null) {
            $params['clinicId'] = $clinic->getId();
        }

        $rows = $conn->fetchAllAssociative($sql, $params);

        $data = [];
        foreach ($rows as $row) {
            $label = $row['label'];
            if (!isset($data[$label])) {
                $data[$label] = ['label' => $label, 'booked' => 0, 'cancelled' => 0];
            }
            $data[$label][$row['status']] = (int) $row['count'];
        }

        return array_values($data);
    }

    private function getTopDoctors(?Clinic $clinic): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('d.id, d.firstName, d.lastName, COUNT(a.id) AS appointmentCount, AVG(r.rating) AS averageRating')
            ->from(Appointment::class, 'a')
            ->join('a.doctorService', 'ds')
            ->join('ds.doctor', 'd')
            ->leftJoin('d.reviews', 'r')
            ->where('a.status = :status')
            ->setParameter('status', Appointment::STATUS_BOOKED)
            ->groupBy('d.id, d.firstName, d.lastName')
            ->orderBy('appointmentCount', 'DESC')
            ->setMaxResults(10);

        if ($clinic !== null) {
            $qb->andWhere('d.clinic = :clinic')->setParameter('clinic', $clinic);
        }

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(fn ($r) => [
            'id'              => $r['id'],
            'name'            => 'Dr. ' . $r['firstName'] . ' ' . $r['lastName'],
            'appointmentCount' => (int) $r['appointmentCount'],
            'averageRating'   => $r['averageRating'] !== null ? round((float) $r['averageRating'], 1) : null,
        ], $rows);
    }

    private function getTopSpecialties(?Clinic $clinic): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s.id, s.name, s.slug, COUNT(a.id) AS appointmentCount')
            ->from(Appointment::class, 'a')
            ->join('a.doctorService', 'ds')
            ->join('ds.doctor', 'd')
            ->join('d.specialties', 's')
            ->where('a.status = :status')
            ->setParameter('status', Appointment::STATUS_BOOKED)
            ->groupBy('s.id, s.name, s.slug')
            ->orderBy('appointmentCount', 'DESC')
            ->setMaxResults(10);

        if ($clinic !== null) {
            $qb->andWhere('d.clinic = :clinic')->setParameter('clinic', $clinic);
        }

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(fn ($r) => [
            'id'              => $r['id'],
            'name'            => $r['name'],
            'slug'            => $r['slug'],
            'appointmentCount' => (int) $r['appointmentCount'],
        ], $rows);
    }

    private function getClinicOccupancy(?Clinic $clinic): array
    {
        $from = (new \DateTimeImmutable())->modify('-30 days')->format('Y-m-d H:i:s');
        $to   = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $clinicWhere = $clinic !== null ? 'AND c.id = :clinicId' : '';
        $params = ['from' => $from, 'to' => $to];
        if ($clinic !== null) {
            $params['clinicId'] = $clinic->getId();
        }

        $rows = $this->em->getConnection()->fetchAllAssociative("
            SELECT c.id, c.name, c.city,
                   COUNT(ts.id)       AS total_slots,
                   SUM(ts.is_booked)  AS booked_slots
            FROM time_slot ts
            INNER JOIN doctor d  ON ts.doctor_id  = d.id
            INNER JOIN clinic c  ON d.clinic_id   = c.id
            WHERE ts.start_at BETWEEN :from AND :to $clinicWhere
            GROUP BY c.id, c.name, c.city
            ORDER BY booked_slots DESC
        ", $params);

        return array_map(fn ($r) => [
            'id'           => (int) $r['id'],
            'name'         => $r['name'],
            'city'         => $r['city'],
            'totalSlots'   => (int) $r['total_slots'],
            'bookedSlots'  => (int) $r['booked_slots'],
            'occupancyRate' => $r['total_slots'] > 0
                ? round((int) $r['booked_slots'] / (int) $r['total_slots'] * 100, 1)
                : 0,
        ], $rows);
    }

    private function getReviewScores(?Clinic $clinic): array
    {
        $distQb = $this->em->createQueryBuilder()
            ->select('r.rating, COUNT(r.id) AS count')
            ->from(Review::class, 'r')
            ->groupBy('r.rating')
            ->orderBy('r.rating', 'ASC');

        if ($clinic !== null) {
            $distQb->join('r.doctor', 'd')
                ->andWhere('d.clinic = :clinic')
                ->setParameter('clinic', $clinic);
        }

        $distRows = $distQb->getQuery()->getArrayResult();

        $distribution = [];
        foreach ($distRows as $row) {
            $distribution[(string) $row['rating']] = (int) $row['count'];
        }

        $doctorQb = $this->em->createQueryBuilder()
            ->select('d.id, d.firstName, d.lastName, AVG(r.rating) AS averageRating, COUNT(r.id) AS reviewCount')
            ->from(Review::class, 'r')
            ->join('r.doctor', 'd')
            ->groupBy('d.id, d.firstName, d.lastName')
            ->orderBy('averageRating', 'DESC')
            ->addOrderBy('reviewCount', 'DESC')
            ->setMaxResults(10);

        if ($clinic !== null) {
            $doctorQb->andWhere('d.clinic = :clinic')->setParameter('clinic', $clinic);
        }

        $doctorRows = $doctorQb->getQuery()->getArrayResult();

        return [
            'distribution'   => $distribution,
            'averageByDoctor' => array_map(fn ($r) => [
                'id'            => $r['id'],
                'name'          => 'Dr. ' . $r['firstName'] . ' ' . $r['lastName'],
                'averageRating' => round((float) $r['averageRating'], 1),
                'reviewCount'   => (int) $r['reviewCount'],
            ], $doctorRows),
        ];
    }
}
