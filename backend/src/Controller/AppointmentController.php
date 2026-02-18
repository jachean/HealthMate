<?php

namespace App\Controller;

use App\DTO\AppointmentCreateDTO;
use App\DTO\AppointmentReadDTO;
use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorServiceRepository;
use App\Repository\TimeSlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class AppointmentController extends AbstractController
{
    #[Route('/appointments', methods: ['POST'])]
    public function book(
        Request $request,
        ValidatorInterface $validator,
        TimeSlotRepository $timeSlotRepository,
        AppointmentRepository $appointmentRepository,
        DoctorServiceRepository $doctorServiceRepository,
        EntityManagerInterface $em,
        Security $security
    ): JsonResponse {
        /** @var User|null $user */
        $user = $security->getUser();

        if (!$user) {
            return $this->json([
                'error' => [
                    'code' => 'AUTH_REQUIRED',
                    'message' => 'Authentication is required to book an appointment.',
                ]
            ], 401);
        }

        $dto = new AppointmentCreateDTO();
        $data = json_decode($request->getContent(), true) ?? [];

        $dto->timeSlotId = $data['timeSlotId'] ?? null;
        $dto->doctorServiceId = $data['doctorServiceId'] ?? null;

        $errors = $validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json([
                'error' => [
                    'code' => 'INVALID_REQUEST',
                    'message' => 'Invalid request payload.',
                ]
            ], 400);
        }

        $timeSlot = $timeSlotRepository->findOneForBooking($dto->timeSlotId);

        if (!$timeSlot) {
            return $this->json([
                'error' => [
                    'code' => 'TIME_SLOT_NOT_AVAILABLE',
                    'message' => 'This time slot is no longer available.',
                ]
            ], 400);
        }

        $doctorService = $doctorServiceRepository->find($dto->doctorServiceId);

        if (!$doctorService) {
            return $this->json([
                'error' => [
                    'code' => 'SERVICE_NOT_FOUND',
                    'message' => 'The selected service was not found.',
                ]
            ], 400);
        }

        if ($doctorService->getDoctor()->getId() !== $timeSlot->getDoctor()->getId()) {
            return $this->json([
                'error' => [
                    'code' => 'SERVICE_DOCTOR_MISMATCH',
                    'message' => 'The selected service does not belong to this doctor.',
                ]
            ], 400);
        }

        if ($timeSlotRepository->hasOverlappingAppointment($timeSlot)) {
            return $this->json([
                'error' => [
                    'code' => 'TIME_SLOT_CONFLICT',
                    'message' => 'This time slot is no longer available.',
                ]
            ], 409);
        }

        if ($appointmentRepository->existsForTimeSlot($timeSlot->getId())) {
            return $this->json([
                'error' => [
                    'code' => 'TIME_SLOT_ALREADY_BOOKED',
                    'message' => 'This time slot has already been booked.',
                ]
            ], 409);
        }

        $appointment = new Appointment();
        $appointment->setUser($user);
        $appointment->setTimeSlot($timeSlot);
        $appointment->setDoctorService($doctorService);

        $timeSlot->setIsBooked(true);

        $em->persist($appointment);
        $em->flush();

        return $this->json($this->toReadDTO($appointment), 201);
    }

    #[Route('/me/appointments', methods: ['GET'])]
    public function myAppointments(
        AppointmentRepository $repository,
        Security $security
    ): JsonResponse {
        /** @var User|null $user */
        $user = $security->getUser();

        if (!$user) {
            return $this->json([
                'error' => [
                    'code' => 'AUTH_REQUIRED',
                    'message' => 'Authentication is required.',
                ]
            ], 401);
        }

        $appointments = $repository->findForUserOrderedByStartAt($user);

        $result = [];

        foreach ($appointments as $appointment) {
            $result[] = $this->toReadDTO($appointment);
        }

        return $this->json($result);
    }

    private function toReadDTO(Appointment $appointment): AppointmentReadDTO
    {
        $slot = $appointment->getTimeSlot();
        $doctor = $slot->getDoctor();
        $clinic = $doctor->getClinic();
        $doctorService = $appointment->getDoctorService();
        $review = $appointment->getReview();

        return new AppointmentReadDTO(
            $appointment->getId(),
            $appointment->getStatus(),
            $appointment->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $doctor->getId(),
            $doctor->getFirstName() . ' ' . $doctor->getLastName(),
            $clinic->getName(),
            $slot->getStartAt()->format(\DateTimeInterface::ATOM),
            $slot->getEndAt()->format(\DateTimeInterface::ATOM),
            $doctorService->getMedicalService()->getName(),
            $doctorService->getPrice(),
            $review?->getId(),
            $review?->getRating(),
        );
    }
}
