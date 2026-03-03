<?php

namespace App\Controller\Admin;

use App\DTO\ClinicDTO;
use App\Entity\Clinic;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/clinics')]
#[IsGranted('ROLE_ADMIN')]
class AdminClinicController extends AdminController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $clinics = $this->em->getRepository(Clinic::class)->findBy([], ['name' => 'ASC']);
        return $this->json($clinics, Response::HTTP_OK, [], ['groups' => ['admin:clinic:list']]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new ClinicDTO();
        $dto->name = $body['name'] ?? '';
        $dto->description = $body['description'] ?? null;
        $dto->address = $body['address'] ?? '';
        $dto->city = $body['city'] ?? '';

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $clinic = new Clinic();
        $clinic->setName($dto->name);
        $clinic->setDescription($dto->description);
        $clinic->setAddress($dto->address);
        $clinic->setCity($dto->city);

        $this->em->persist($clinic);
        $this->em->flush();

        return $this->json($clinic, Response::HTTP_CREATED, [], ['groups' => ['admin:clinic:list']]);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $clinic = $this->em->getRepository(Clinic::class)->find($id);
        if (!$clinic) {
            return $this->json(['error' => 'Clinic not found'], Response::HTTP_NOT_FOUND);
        }

        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new ClinicDTO();
        $dto->name = $body['name'] ?? '';
        $dto->description = $body['description'] ?? null;
        $dto->address = $body['address'] ?? '';
        $dto->city = $body['city'] ?? '';

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $clinic->setName($dto->name);
        $clinic->setDescription($dto->description);
        $clinic->setAddress($dto->address);
        $clinic->setCity($dto->city);

        $this->em->flush();

        return $this->json($clinic, Response::HTTP_OK, [], ['groups' => ['admin:clinic:list']]);
    }
}
