<?php

namespace App\Controller\Admin;

use App\Entity\DoctorUnavailability;
use App\Repository\DoctorRepository;
use App\Repository\DoctorUnavailabilityRepository;
use App\Repository\TimeSlotRepository;
use App\Service\ClinicAdminContext;
use App\Service\TimeSlotGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/doctors/{doctorId}/unavailability')]
#[IsGranted('ROLE_CLINIC_ADMIN')]
class AdminDoctorAvailabilityController extends AdminController
{
    public function __construct(
        private DoctorRepository $doctorRepository,
        private DoctorUnavailabilityRepository $unavailabilityRepository,
        private TimeSlotRepository $timeSlotRepository,
        private TimeSlotGenerator $slotGenerator,
        private EntityManagerInterface $em,
        private ClinicAdminContext $ctx,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(int $doctorId): JsonResponse
    {
        $doctor = $this->doctorRepository->find($doctorId);
        if (!$doctor) {
            return $this->json(['error' => 'Doctor not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isAccessible($doctor)) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $periods = $this->unavailabilityRepository->findForDoctor($doctor);

        return $this->json(array_map($this->toArray(...), $periods));
    }

    #[Route('', methods: ['POST'])]
    public function create(int $doctorId, Request $request): JsonResponse
    {
        $doctor = $this->doctorRepository->find($doctorId);
        if (!$doctor) {
            return $this->json(['error' => 'Doctor not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isAccessible($doctor)) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (empty($data['dateFrom']) || empty($data['dateTo'])) {
            return $this->json(['error' => 'dateFrom and dateTo are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $dateFrom = new \DateTimeImmutable($data['dateFrom']);
            $dateTo   = new \DateTimeImmutable($data['dateTo']);
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }

        if ($dateFrom > $dateTo) {
            return $this->json(['error' => 'dateFrom must be before or equal to dateTo'], Response::HTTP_BAD_REQUEST);
        }

        $overlapping = $this->unavailabilityRepository->findOverlapping($doctor, $dateFrom, $dateTo);
        if (!empty($overlapping)) {
            return $this->json(['error' => 'overlap'], Response::HTTP_CONFLICT);
        }

        $period = new DoctorUnavailability();
        $period->setDoctor($doctor);
        $period->setDateFrom($dateFrom);
        $period->setDateTo($dateTo);
        $period->setReason($data['reason'] ?? null);

        $this->em->persist($period);

        // Delete unbooked slots in the blocked range
        $this->timeSlotRepository->deleteUnbookedSlotsInRange($doctor->getId(), $dateFrom, $dateTo);

        $this->em->flush();

        return $this->json($this->toArray($period), Response::HTTP_CREATED);
    }

    #[Route('/{unavailId}', methods: ['DELETE'])]
    public function delete(int $doctorId, int $unavailId): Response
    {
        $doctor = $this->doctorRepository->find($doctorId);
        if (!$doctor) {
            return $this->json(['error' => 'Doctor not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->isAccessible($doctor)) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $period = $this->unavailabilityRepository->find($unavailId);
        if (!$period || $period->getDoctor()->getId() !== $doctor->getId()) {
            return $this->json(['error' => 'Period not found'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($period);
        $this->em->flush();

        // Regenerate slots for the newly available dates
        $this->slotGenerator->generateForDoctor($doctor);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function isAccessible(\App\Entity\Doctor $doctor): bool
    {
        $scopedClinic = $this->ctx->getClinic();

        return $scopedClinic === null || $doctor->getClinic()->getId() === $scopedClinic->getId();
    }

    private function toArray(DoctorUnavailability $period): array
    {
        return [
            'id'       => $period->getId(),
            'dateFrom' => $period->getDateFrom()->format('Y-m-d'),
            'dateTo'   => $period->getDateTo()->format('Y-m-d'),
            'reason'   => $period->getReason(),
        ];
    }
}
