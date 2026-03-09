<?php

namespace App\Controller\Admin;

use App\Email\AppointmentConfirmationEmail;
use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorServiceRepository;
use App\Repository\TimeSlotRepository;
use App\Repository\UserRepository;
use App\Service\ClinicAdminContext;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/appointments')]
#[IsGranted('ROLE_CLINIC_ADMIN')]
class AdminAppointmentController extends AdminController
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private EntityManagerInterface $em,
        private ClinicAdminContext $ctx,
        private UserRepository $userRepository,
        private TimeSlotRepository $timeSlotRepository,
        private DoctorServiceRepository $doctorServiceRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $limit = max(1, min(100, (int) $request->query->get('limit', '20')));

        $filters = [
            'dateFrom' => $request->query->get('dateFrom'),
            'dateTo'   => $request->query->get('dateTo'),
            'doctorId' => $request->query->get('doctorId'),
            'clinicId' => $request->query->get('clinicId'),
            'status'   => $request->query->get('status'),
            'patient'  => $request->query->get('patient'),
        ];

        // Clinic admins are restricted to their own clinic
        $scopedClinic = $this->ctx->getClinic();
        if ($scopedClinic !== null) {
            $filters['clinicId'] = $scopedClinic->getId();
        }

        $result = $this->appointmentRepository->findAllPaginatedForAdmin($page, $limit, $filters);

        return $this->json([
            'data'  => array_map($this->toAdminArray(...), $result['data']),
            'total' => $result['total'],
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function detail(int $id): Response
    {
        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->json(['error' => 'Appointment not found'], Response::HTTP_NOT_FOUND);
        }

        $scopedClinic = $this->ctx->getClinic();
        if ($scopedClinic !== null) {
            $appointmentClinicId = $appointment->getTimeSlot()->getDoctor()->getClinic()->getId();
            if ($appointmentClinicId !== $scopedClinic->getId()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
        }

        return $this->json($this->toAdminArray($appointment));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $userId          = isset($data['userId']) ? (int) $data['userId'] : null;
        $timeSlotId      = isset($data['timeSlotId']) ? (int) $data['timeSlotId'] : null;
        $doctorServiceId = isset($data['doctorServiceId']) ? (int) $data['doctorServiceId'] : null;

        if (!$userId || !$timeSlotId || !$doctorServiceId) {
            return $this->json(
                ['error' => 'userId, timeSlotId and doctorServiceId are required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $timeSlot = $this->timeSlotRepository->find($timeSlotId);
        if (!$timeSlot || $timeSlot->getStartAt() <= new \DateTimeImmutable()) {
            return $this->json(['error' => 'TIME_SLOT_NOT_AVAILABLE'], Response::HTTP_BAD_REQUEST);
        }

        if ($timeSlot->isBooked()) {
            return $this->json(['error' => 'TIME_SLOT_ALREADY_BOOKED'], Response::HTTP_CONFLICT);
        }

        $doctorService = $this->doctorServiceRepository->find($doctorServiceId);
        if (!$doctorService) {
            return $this->json(['error' => 'Doctor service not found'], Response::HTTP_NOT_FOUND);
        }

        // Clinic admin scoping
        $scopedClinic = $this->ctx->getClinic();
        if ($scopedClinic !== null) {
            if ($timeSlot->getDoctor()->getClinic()->getId() !== $scopedClinic->getId()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
        }

        $appointment = new Appointment();
        $appointment->setUser($user);
        $appointment->setTimeSlot($timeSlot);
        $appointment->setDoctorService($doctorService);

        $timeSlot->setIsBooked(true);

        $this->em->persist($appointment);
        $this->em->flush();

        try {
            $confirmationEmail = AppointmentConfirmationEmail::create(
                $appointment,
                $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@healthmate.ro',
                $_ENV['MAILER_FROM_NAME'] ?? 'HealthMate'
            );
            $this->mailer->send($confirmationEmail);
        } catch (\Throwable $e) {
            $this->logger->error('Admin appointment confirmation email failed: ' . $e->getMessage());
        }

        return $this->json($this->toAdminArray($appointment), Response::HTTP_CREATED);
    }

    #[Route('/{id}/cancel', methods: ['PATCH'])]
    public function cancel(int $id): Response
    {
        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->json(['error' => 'Appointment not found'], Response::HTTP_NOT_FOUND);
        }

        // Clinic admins can only cancel appointments in their own clinic
        $scopedClinic = $this->ctx->getClinic();
        if ($scopedClinic !== null) {
            $appointmentClinicId = $appointment->getTimeSlot()->getDoctor()->getClinic()->getId();
            if ($appointmentClinicId !== $scopedClinic->getId()) {
                return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
        }

        if ($appointment->getStatus() === Appointment::STATUS_CANCELLED) {
            return $this->json(['error' => 'Appointment is already cancelled'], Response::HTTP_CONFLICT);
        }

        $appointment->setStatus(Appointment::STATUS_CANCELLED);
        $appointment->getTimeSlot()->setIsBooked(false);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function toAdminArray(Appointment $appointment): array
    {
        $slot         = $appointment->getTimeSlot();
        $doctor       = $slot->getDoctor();
        $user         = $appointment->getUser();
        $doctorService = $appointment->getDoctorService();

        return [
            'id'        => $appointment->getId(),
            'status'    => $appointment->getStatus(),
            'createdAt' => $appointment->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'startAt'   => $slot->getStartAt()->format(\DateTimeInterface::ATOM),
            'endAt'     => $slot->getEndAt()->format(\DateTimeInterface::ATOM),
            'doctor'    => [
                'id'     => $doctor->getId(),
                'name'   => $doctor->getFirstName() . ' ' . $doctor->getLastName(),
                'clinic' => $doctor->getClinic()->getName(),
            ],
            'patient'   => [
                'id'    => $user->getId(),
                'name'  => $user->getFirstName() . ' ' . $user->getLastName(),
                'email' => $user->getEmail(),
            ],
            'service'   => [
                'name'     => $doctorService->getMedicalService()->getName(),
                'price'    => $doctorService->getPrice(),
                'duration' => $doctorService->getDurationMinutes(),
            ],
            'review'    => $appointment->getReview() !== null ? [
                'id'      => $appointment->getReview()->getId(),
                'rating'  => $appointment->getReview()->getRating(),
                'comment' => $appointment->getReview()->getComment(),
            ] : null,
        ];
    }
}
