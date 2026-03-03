<?php

namespace App\Controller\Admin;

use App\DTO\DoctorDTO;
use App\Entity\Doctor;
use App\Repository\DoctorRepository;
use App\Service\TimeSlotGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/doctors')]
#[IsGranted('ROLE_ADMIN')]
class AdminDoctorController extends AdminController
{
    public function __construct(
        private DoctorRepository $doctorRepository,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private TimeSlotGenerator $timeSlotGenerator,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', '1'));
        $limit = max(1, min(100, (int) $request->query->get('limit', '20')));
        $search = $request->query->get('search') ?: null;

        $result = $this->doctorRepository->findAllPaginatedForAdmin($page, $limit, $search);

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

        $dto = $this->buildDtoFromRequest($request);

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

        foreach ($doctor->getSpecialties()->toArray() as $existing) {
            $doctor->removeSpecialty($existing);
        }
        foreach ($specialties as $specialty) {
            $doctor->addSpecialty($specialty);
        }

        $this->em->flush();

        return $this->json($doctor, Response::HTTP_OK, [], ['groups' => ['doctor:list']]);
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
