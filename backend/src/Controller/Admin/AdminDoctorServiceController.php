<?php

namespace App\Controller\Admin;

use App\DTO\DoctorServiceDTO;
use App\Entity\Appointment;
use App\Entity\Doctor;
use App\Entity\DoctorService;
use App\Entity\MedicalService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminDoctorServiceController extends AdminController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('/medical-services', methods: ['GET'])]
    public function listMedicalServices(): JsonResponse
    {
        $services = $this->em->getRepository(MedicalService::class)->findBy([], ['name' => 'ASC']);
        return $this->json($services, 200, [], ['groups' => ['medical_service:list']]);
    }

    #[Route('/doctors/{id}/services', methods: ['GET'])]
    public function listDoctorServices(int $id): JsonResponse
    {
        $doctor = $this->em->getRepository(Doctor::class)->find($id);
        if (!$doctor) {
            return $this->json(['error' => 'Doctor not found'], Response::HTTP_NOT_FOUND);
        }

        $services = $this->em->getRepository(DoctorService::class)->findBy(['doctor' => $doctor]);
        return $this->json($services, 200, [], ['groups' => ['doctor_service:list']]);
    }

    #[Route('/doctors/{id}/services', methods: ['POST'])]
    public function addService(int $id, Request $request): JsonResponse
    {
        $doctor = $this->em->getRepository(Doctor::class)->find($id);
        if (!$doctor) {
            return $this->json(['error' => 'Doctor not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new DoctorServiceDTO();
        $dto->medicalServiceId = $body['medicalServiceId'] ?? 0;
        $dto->price = $body['price'] ?? '';
        $dto->durationMinutes = $body['durationMinutes'] ?? 0;

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $medicalService = $this->em->getRepository(MedicalService::class)->find($dto->medicalServiceId);
        if (!$medicalService) {
            return $this->json(['error' => 'Medical service not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check for duplicate
        $existing = $this->em->getRepository(DoctorService::class)->findOneBy([
            'doctor' => $doctor,
            'medicalService' => $medicalService,
        ]);
        if ($existing) {
            return $this->json(['error' => 'This service is already assigned to the doctor'], Response::HTTP_CONFLICT);
        }

        $doctorService = new DoctorService();
        $doctorService->setDoctor($doctor);
        $doctorService->setMedicalService($medicalService);
        $doctorService->setPrice($dto->price);
        $doctorService->setDurationMinutes($dto->durationMinutes);

        $this->em->persist($doctorService);
        $this->em->flush();

        return $this->json($doctorService, Response::HTTP_CREATED, [], ['groups' => ['doctor_service:list']]);
    }

    #[Route('/doctor-services/{id}', methods: ['PUT'])]
    public function updateService(int $id, Request $request): JsonResponse
    {
        $doctorService = $this->em->getRepository(DoctorService::class)->find($id);
        if (!$doctorService) {
            return $this->json(['error' => 'Doctor service not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new DoctorServiceDTO();
        $dto->medicalServiceId = (int) $doctorService->getMedicalService()->getId();
        $dto->price = $body['price'] ?? '';
        $dto->durationMinutes = $body['durationMinutes'] ?? 0;

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $doctorService->setPrice($dto->price);
        $doctorService->setDurationMinutes($dto->durationMinutes);

        $this->em->flush();

        return $this->json($doctorService, Response::HTTP_OK, [], ['groups' => ['doctor_service:list']]);
    }

    #[Route('/doctor-services/{id}', methods: ['DELETE'])]
    public function deleteService(int $id): Response
    {
        $doctorService = $this->em->getRepository(DoctorService::class)->find($id);
        if (!$doctorService) {
            return $this->json(['error' => 'Doctor service not found'], Response::HTTP_NOT_FOUND);
        }

        // Check for future active appointments
        $now = new \DateTimeImmutable();
        $futureAppointment = $this->em->createQueryBuilder()
            ->select('a')
            ->from(Appointment::class, 'a')
            ->join('a.timeSlot', 'ts')
            ->where('a.doctorService = :ds')
            ->andWhere('a.status = :status')
            ->andWhere('ts.startAt > :now')
            ->setParameter('ds', $doctorService)
            ->setParameter('status', Appointment::STATUS_BOOKED)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($futureAppointment) {
            return $this->json(
                ['error' => 'Cannot delete: there are future active appointments for this service'],
                Response::HTTP_CONFLICT
            );
        }

        $this->em->remove($doctorService);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
