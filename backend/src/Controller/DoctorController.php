<?php

namespace App\Controller;

use App\DTO\TimeSlotReadDTO;
use App\Repository\DoctorRepository;
use App\Repository\TimeSlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/doctors')]
final class DoctorController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(Request $request, DoctorRepository $doctorRepository): JsonResponse
    {
        $clinicId = $request->query->getInt('clinic') ?: null;

        $city = $request->query->get('city');
        $city = is_string($city) && $city !== '' ? $city : null;

        $specialtySlug = $request->query->get('specialty');
        $specialtySlug = is_string($specialtySlug) && $specialtySlug !== '' ? $specialtySlug : null;

        $acceptsInsurance = $request->query->has('acceptsInsurance')
            ? (bool) $request->query->getInt('acceptsInsurance')
            : null;

        $doctors = $doctorRepository->findActiveDoctorsWithFilters(
            clinicId: $clinicId,
            specialtySlug: $specialtySlug,
            acceptsInsurance: $acceptsInsurance,
            city: $city,
        );

        return $this->json($doctors, context: ['groups' => ['doctor:list']]);
    }


    #[Route('/{id}', methods: ['GET'])]
    public function details(
        int $id,
        DoctorRepository $doctorRepository
    ): JsonResponse {
        $doctor = $doctorRepository->find($id);

        if (!$doctor || !$doctor->isActive()) {
            return $this->json(['error' => 'Doctor not found'], 404);
        }

        return $this->json(
            $doctor,
            context: ['groups' => 'doctor:detail']
        );
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/deactivate', methods: ['PATCH'])]
    public function deactivate(
        int $id,
        DoctorRepository $doctorRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $doctor = $doctorRepository->find($id);

        if (!$doctor) {
            return $this->json(['error' => 'Doctor not found'], 404);
        }

        $doctor->setIsActive(false);
        $em->flush();

        return $this->json(['status' => 'deactivated']);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/activate', methods: ['PATCH'])]
    public function activate(
        int $id,
        DoctorRepository $doctorRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $doctor = $doctorRepository->find($id);

        if (!$doctor) {
            return $this->json(['error' => 'Doctor not found'], 404);
        }

        $doctor->setIsActive(true);
        $em->flush();

        return $this->json(['status' => 'activated']);
    }
    #[Route('/{id}/availability', methods: ['GET'])]
    public function availability(
        int $id,
        DoctorRepository $doctorRepository,
        TimeSlotRepository $timeSlotRepository
    ): JsonResponse {
        $doctor = $doctorRepository->find($id);

        if (!$doctor || !$doctor->isActive()) {
            return $this->json(
                ['error' => 'Doctor not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $slots = $timeSlotRepository->findAvailableSlotsForDoctor($id);

        $result = array_map(
            static fn ($slot) => new TimeSlotReadDTO(
                $slot->getId(),
                $slot->getStartAt()->format(\DateTimeInterface::ATOM),
                $slot->getEndAt()->format(\DateTimeInterface::ATOM),
            ),
            $slots
        );

        return $this->json($result);
    }
}
