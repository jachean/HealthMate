<?php

namespace App\Controller;

use App\Email\AppointmentDelayEmail;
use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/doctor')]
#[IsGranted('ROLE_DOCTOR')]
class DoctorAppointmentController extends AbstractController
{
    public function __construct(
        private DoctorRepository $doctorRepository,
        private AppointmentRepository $appointmentRepository,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/appointments', methods: ['GET'])]
    public function appointments(Request $request): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $doctor = $this->doctorRepository->findOneBy(['user' => $user]);
        if (!$doctor) {
            return $this->json(['error' => 'No doctor profile linked to your account.'], Response::HTTP_FORBIDDEN);
        }

        $dateParam = $request->query->get('date');
        if ($dateParam) {
            $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateParam);
            if (!$date) {
                return $this->json(['error' => 'Invalid date format. Use Y-m-d.'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $date = new \DateTimeImmutable('today');
        }

        $appointments = $this->appointmentRepository->findForDoctorByDate($doctor, $date);

        return $this->json(array_map($this->appointmentToArray(...), $appointments));
    }

    #[Route('/appointments/{id}/start', methods: ['PATCH'])]
    public function start(int $id): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $doctor = $this->doctorRepository->findOneBy(['user' => $user]);
        if (!$doctor) {
            return $this->json(['error' => 'No doctor profile linked to your account.'], Response::HTTP_FORBIDDEN);
        }

        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->json(['error' => 'Appointment not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($appointment->getTimeSlot()->getDoctor()->getId() !== $doctor->getId()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        if ($appointment->getStatus() !== Appointment::STATUS_BOOKED) {
            return $this->json(
                ['error' => 'Appointment must be in booked status to start.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $alreadyInProgress = $this->appointmentRepository->findInProgressForDoctor($doctor);
        if ($alreadyInProgress) {
            return $this->json(
                ['error' => 'You already have a consultation in progress. Please end it before starting a new one.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $appointment->setStatus(Appointment::STATUS_IN_PROGRESS);
        $appointment->setStartedAt(new \DateTimeImmutable());
        $this->em->flush();

        return $this->json($this->appointmentToArray($appointment));
    }

    #[Route('/appointments/{id}/cancel', methods: ['PATCH'])]
    public function cancel(int $id): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $doctor = $this->doctorRepository->findOneBy(['user' => $user]);
        if (!$doctor) {
            return $this->json(['error' => 'No doctor profile linked to your account.'], Response::HTTP_FORBIDDEN);
        }

        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->json(['error' => 'Appointment not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($appointment->getTimeSlot()->getDoctor()->getId() !== $doctor->getId()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        if ($appointment->getStatus() !== Appointment::STATUS_BOOKED) {
            return $this->json(
                ['error' => 'Only booked appointments can be cancelled.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $appointment->setStatus(Appointment::STATUS_CANCELLED);
        $appointment->getTimeSlot()->setIsBooked(false);
        $this->em->flush();

        return $this->json($this->appointmentToArray($appointment));
    }

    #[Route('/appointments/{id}/complete', methods: ['PATCH'])]
    public function complete(int $id): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $doctor = $this->doctorRepository->findOneBy(['user' => $user]);
        if (!$doctor) {
            return $this->json(['error' => 'No doctor profile linked to your account.'], Response::HTTP_FORBIDDEN);
        }

        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->json(['error' => 'Appointment not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($appointment->getTimeSlot()->getDoctor()->getId() !== $doctor->getId()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        if ($appointment->getStatus() !== Appointment::STATUS_IN_PROGRESS) {
            return $this->json(
                ['error' => 'Appointment must be in_progress to complete.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $now = new \DateTimeImmutable();
        $appointment->setStatus(Appointment::STATUS_COMPLETED);
        $appointment->setCompletedAt($now);

        // Compute scheduled end: slotStart + existingDelay + duration
        $slot            = $appointment->getTimeSlot();
        $durationMinutes = $appointment->getDoctorService()->getDurationMinutes();
        $scheduledEnd    = $slot->getStartAt()
            ->modify('+' . $appointment->getDelayMinutes() . ' minutes')
            ->modify('+' . $durationMinutes . ' minutes');

        $additionalDelay      = 0;
        $delayedAppointments  = [];

        if ($now > $scheduledEnd) {
            $overrunSeconds  = $now->getTimestamp() - $scheduledEnd->getTimestamp();
            $additionalDelay = (int) ceil($overrunSeconds / 60);

            $subsequent = $this->appointmentRepository->findSubsequentTodayForDoctor($doctor, $slot->getStartAt());

            foreach ($subsequent as $subAppt) {
                $subAppt->setDelayMinutes($subAppt->getDelayMinutes() + $additionalDelay);

                $effectiveStart = $subAppt->getTimeSlot()->getStartAt()
                    ->modify('+' . $subAppt->getDelayMinutes() . ' minutes');

                $delayedAppointments[] = [
                    'id'             => $subAppt->getId(),
                    'patientName'    => $subAppt->getUser()->getFirstName() . ' ' . $subAppt->getUser()->getLastName(),
                    'effectiveStart' => $effectiveStart->format(\DateTimeInterface::ATOM),
                    'delayMinutes'   => $subAppt->getDelayMinutes(),
                ];

                try {
                    $email = AppointmentDelayEmail::create(
                        $subAppt,
                        $additionalDelay,
                        $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@healthmate.ro',
                        $_ENV['MAILER_FROM_NAME'] ?? 'HealthMate'
                    );
                    $this->mailer->send($email);
                } catch (\Throwable $e) {
                    $this->logger->error(
                        'Failed to send delay email for appointment ' . $subAppt->getId() . ': ' . $e->getMessage()
                    );
                }
            }
        }

        $this->em->flush();

        return $this->json([
            'completedAt'           => $now->format(\DateTimeInterface::ATOM),
            'additionalDelayMinutes' => $additionalDelay,
            'delayedAppointments'   => $delayedAppointments,
        ]);
    }

    #[Route('/appointments/{id}/pause', methods: ['PATCH'])]
    public function pause(int $id): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $doctor = $this->doctorRepository->findOneBy(['user' => $user]);
        if (!$doctor) {
            return $this->json(['error' => 'No doctor profile linked to your account.'], Response::HTTP_FORBIDDEN);
        }

        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->json(['error' => 'Appointment not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($appointment->getTimeSlot()->getDoctor()->getId() !== $doctor->getId()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        if ($appointment->getStatus() !== Appointment::STATUS_IN_PROGRESS) {
            return $this->json(
                ['error' => 'Appointment must be in_progress to pause.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $appointment->setStatus(Appointment::STATUS_BOOKED);
        $appointment->setStartedAt(null);
        $this->em->flush();

        return $this->json($this->appointmentToArray($appointment));
    }

    #[Route('/appointments/{id}/reopen', methods: ['PATCH'])]
    public function reopen(int $id): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $doctor = $this->doctorRepository->findOneBy(['user' => $user]);
        if (!$doctor) {
            return $this->json(['error' => 'No doctor profile linked to your account.'], Response::HTTP_FORBIDDEN);
        }

        $appointment = $this->appointmentRepository->find($id);
        if (!$appointment) {
            return $this->json(['error' => 'Appointment not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($appointment->getTimeSlot()->getDoctor()->getId() !== $doctor->getId()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        if ($appointment->getStatus() !== Appointment::STATUS_COMPLETED) {
            return $this->json(
                ['error' => 'Only completed appointments can be reopened.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $alreadyInProgress = $this->appointmentRepository->findInProgressForDoctor($doctor);
        if ($alreadyInProgress) {
            return $this->json(
                ['error' => 'You already have a consultation in progress. Please end it before reopening another.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $appointment->setStatus(Appointment::STATUS_IN_PROGRESS);
        $appointment->setCompletedAt(null);
        $this->em->flush();

        return $this->json($this->appointmentToArray($appointment));
    }

    private function appointmentToArray(Appointment $appointment): array
    {
        $slot            = $appointment->getTimeSlot();
        $doctorService   = $appointment->getDoctorService();
        $durationMinutes = $doctorService->getDurationMinutes();
        $delayMinutes    = $appointment->getDelayMinutes();

        $effectiveStart = $slot->getStartAt()->modify('+' . $delayMinutes . ' minutes');
        $effectiveEnd   = $effectiveStart->modify('+' . $durationMinutes . ' minutes');

        return [
            'id'              => $appointment->getId(),
            'status'          => $appointment->getStatus(),
            'patientName'     => $appointment->getUser()->getFirstName() . ' ' . $appointment->getUser()->getLastName(),
            'patientEmail'    => $appointment->getUser()->getEmail(),
            'serviceName'     => $doctorService->getMedicalService()->getName(),
            'durationMinutes' => $durationMinutes,
            'slotStartAt'     => $slot->getStartAt()->format(\DateTimeInterface::ATOM),
            'slotEndAt'       => $slot->getEndAt()->format(\DateTimeInterface::ATOM),
            'delayMinutes'    => $delayMinutes,
            'effectiveStartAt' => $effectiveStart->format(\DateTimeInterface::ATOM),
            'effectiveEndAt'   => $effectiveEnd->format(\DateTimeInterface::ATOM),
            'startedAt'       => $appointment->getStartedAt()?->format(\DateTimeInterface::ATOM),
            'completedAt'     => $appointment->getCompletedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
