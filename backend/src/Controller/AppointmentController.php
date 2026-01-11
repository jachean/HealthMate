<?php

namespace App\Controller;

use App\DTO\AppointmentCreateDTO;
use App\DTO\AppointmentReadDTO;
use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
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

        $em->persist($appointment);
        $em->flush();

        $slot = $appointment->getTimeSlot();
        $doctor = $slot->getDoctor();
        $clinic = $doctor->getClinic();

        return $this->json(
            new AppointmentReadDTO(
                $appointment->getId(),
                $appointment->getStatus(),
                $appointment->getCreatedAt()->format(\DateTimeInterface::ATOM),
                $doctor->getId(),
                $doctor->getFirstName() . ' ' . $doctor->getLastName(),
                $clinic->getName(),
                $slot->getStartAt()->format(\DateTimeInterface::ATOM),
                $slot->getEndAt()->format(\DateTimeInterface::ATOM),
            ),
            201
        );
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
            $slot = $appointment->getTimeSlot();
            $doctor = $slot->getDoctor();
            $clinic = $doctor->getClinic();

            $result[] = new AppointmentReadDTO(
                $appointment->getId(),
                $appointment->getStatus(),
                $appointment->getCreatedAt()->format(\DateTimeInterface::ATOM),
                $doctor->getId(),
                $doctor->getFirstName() . ' ' . $doctor->getLastName(),
                $clinic->getName(),
                $slot->getStartAt()->format(\DateTimeInterface::ATOM),
                $slot->getEndAt()->format(\DateTimeInterface::ATOM),
            );
        }

        return $this->json($result);
    }
}
