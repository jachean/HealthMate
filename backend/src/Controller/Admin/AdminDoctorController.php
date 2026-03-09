<?php

namespace App\Controller\Admin;

use App\DTO\DoctorDTO;
use App\Entity\Doctor;
use App\Repository\DoctorRepository;
use App\Repository\TimeSlotRepository;
use App\Service\ClinicAdminContext;
use App\Service\TimeSlotGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/doctors')]
#[IsGranted('ROLE_CLINIC_ADMIN')]
class AdminDoctorController extends AdminController
{
    public function __construct(
        private DoctorRepository $doctorRepository,
        private TimeSlotRepository $timeSlotRepository,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private TimeSlotGenerator $timeSlotGenerator,
        private ClinicAdminContext $ctx,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $limit = max(1, min(100, (int) $request->query->get('limit', '20')));
        $search = $request->query->get('search') ?: null;

        $result = $this->doctorRepository->findAllPaginatedForAdmin(
            $page,
            $limit,
            $search,
            $this->ctx->getClinic()?->getId()
        );

        return $this->json([
            'data' => $result['data'],
            'total' => $result['total'],
            'page' => $page,
            'limit' => $limit,
        ], 200, [], ['groups' => ['doctor:list']]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->buildDtoFromRequest($request);

        // For clinic admins, override the clinic with their own
        $scopedClinic = $this->ctx->getClinic();
        if ($scopedClinic !== null) {
            $dto->clinicId = $scopedClinic->getId();
        }

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $clinic = $this->em->getRepository(\App\Entity\Clinic::class)->find($dto->clinicId);
        if (!$clinic) {
            return $this->json(['error' => 'Clinic not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $specialties = $this->resolveSpecialties($dto->specialtyIds);
        if ($specialties instanceof JsonResponse) {
            return $specialties;
        }

        $doctor = new Doctor();
        $doctor->setFirstName($dto->firstName);
        $doctor->setLastName($dto->lastName);
        $doctor->setBio($dto->bio);
        $doctor->setAcceptsInsurance($dto->acceptsInsurance);
        $doctor->setIsActive($dto->isActive);
        $doctor->setClinic($clinic);
        $doctor->setWorkDays($dto->workDays);
        $doctor->setStartHour($dto->startHour);
        $doctor->setEndHour($dto->endHour);
        $doctor->setAvatarPath($dto->avatar);
        foreach ($specialties as $specialty) {
            $doctor->addSpecialty($specialty);
        }

        $this->em->persist($doctor);
        $this->em->flush();

        $this->timeSlotGenerator->generateForDoctor($doctor);

        return $this->json($doctor, Response::HTTP_CREATED, [], ['groups' => ['doctor:list']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $doctor = $this->doctorRepository->find($id);
        if (!$doctor) {
            return $this->json(['error' => 'Doctor not found'], Response::HTTP_NOT_FOUND);
        }

        // Clinic admins can only edit doctors in their own clinic
        $scopedClinic = $this->ctx->getClinic();
        if ($scopedClinic !== null && $doctor->getClinic()->getId() !== $scopedClinic->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $dto = $this->buildDtoFromRequest($request);

        // Clinic admins cannot move the doctor to another clinic
        if ($scopedClinic !== null) {
            $dto->clinicId = $scopedClinic->getId();
        }

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $clinic = $this->em->getRepository(\App\Entity\Clinic::class)->find($dto->clinicId);
        if (!$clinic) {
            return $this->json(['error' => 'Clinic not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $specialties = $this->resolveSpecialties($dto->specialtyIds);
        if ($specialties instanceof JsonResponse) {
            return $specialties;
        }

        $doctor->setFirstName($dto->firstName);
        $doctor->setLastName($dto->lastName);
        $doctor->setBio($dto->bio);
        $doctor->setAcceptsInsurance($dto->acceptsInsurance);
        $doctor->setIsActive($dto->isActive);
        $doctor->setClinic($clinic);
        $doctor->setWorkDays($dto->workDays);
        $doctor->setStartHour($dto->startHour);
        $doctor->setEndHour($dto->endHour);
        $doctor->setAvatarPath($dto->avatar ?? $doctor->getAvatarPath());

        foreach ($doctor->getSpecialties()->toArray() as $existing) {
            $doctor->removeSpecialty($existing);
        }
        foreach ($specialties as $specialty) {
            $doctor->addSpecialty($specialty);
        }

        $this->em->flush();

        // Remove future unbooked slots that no longer fit the updated schedule,
        // then regenerate so only valid slots remain.
        $this->timeSlotRepository->deleteAllFutureUnbookedForDoctor($doctor->getId());
        $this->timeSlotGenerator->generateForDoctor($doctor);

        return $this->json($doctor, Response::HTTP_OK, [], ['groups' => ['doctor:list']]);
    }

    #[Route('/{id}/availability', methods: ['GET'])]
    public function availability(int $id): JsonResponse
    {
        $doctor = $this->doctorRepository->find($id);
        if (!$doctor) {
            return $this->json(['error' => 'Doctor not found'], Response::HTTP_NOT_FOUND);
        }

        $scopedClinic = $this->ctx->getClinic();
        if ($scopedClinic !== null && $doctor->getClinic()->getId() !== $scopedClinic->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $slots     = $this->timeSlotRepository->findAvailableSlotsForDoctor($doctor->getId());
        $workDays  = $doctor->getWorkDays();
        $startHour = $doctor->getStartHour();
        $endHour   = $doctor->getEndHour();

        $slots = array_values(array_filter($slots, static function ($slot) use ($workDays, $startHour, $endHour): bool {
            $start = $slot->getStartAt();
            if (!in_array((int) $start->format('N'), $workDays, true)) {
                return false;
            }
            $hour = (int) $start->format('G');
            return $hour >= $startHour && $hour < $endHour;
        }));

        return $this->json(array_map(
            static fn ($slot) => [
                'id'      => $slot->getId(),
                'startAt' => $slot->getStartAt()->format(\DateTimeInterface::ATOM),
                'endAt'   => $slot->getEndAt()->format(\DateTimeInterface::ATOM),
            ],
            $slots
        ));
    }

    private function buildDtoFromRequest(Request $request): DoctorDTO
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new DoctorDTO();
        $dto->firstName = $body['firstName'] ?? '';
        $dto->lastName = $body['lastName'] ?? '';
        $dto->bio = $body['bio'] ?? null;
        $dto->acceptsInsurance = $body['acceptsInsurance'] ?? false;
        $dto->isActive = $body['isActive'] ?? true;
        $dto->clinicId = $body['clinicId'] ?? 0;
        $dto->specialtyIds = $body['specialtyIds'] ?? [];
        $dto->workDays = $body['workDays'] ?? [1, 2, 3, 4, 5];
        $dto->startHour = (int) ($body['startHour'] ?? 9);
        $dto->endHour = (int) ($body['endHour'] ?? 17);
        $dto->avatar = $body['avatar'] ?? null;

        return $dto;
    }

    /**
     * @param int[] $specialtyIds
     * @return \App\Entity\Specialty[]|JsonResponse
     */
    private function resolveSpecialties(array $specialtyIds): array|JsonResponse
    {
        $specialties = [];
        foreach ($specialtyIds as $sid) {
            $specialty = $this->em->getRepository(\App\Entity\Specialty::class)->find($sid);
            if (!$specialty) {
                return $this->json(['error' => "Specialty $sid not found"], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $specialties[] = $specialty;
        }
        return $specialties;
    }
}
