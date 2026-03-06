<?php

namespace App\Controller\Admin;

use App\DTO\SpecialtyDTO;
use App\Entity\MedicalService;
use App\Entity\Specialty;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/specialties')]
#[IsGranted('ROLE_CLINIC_ADMIN')]
class AdminSpecialtyController extends AdminController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $specialties = $this->em->getRepository(Specialty::class)->findBy([], ['name' => 'ASC']);
        return $this->json($specialties, 200, [], ['groups' => ['specialty:list']]);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];
        $dto  = $this->buildDto($body);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $specialty = new Specialty();
        $specialty->setName($dto->name);
        $specialty->setSlug($dto->slug);

        $this->em->persist($specialty);
        $this->em->flush();

        return $this->json($specialty, Response::HTTP_CREATED, [], ['groups' => ['specialty:list']]);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): Response
    {
        $specialty = $this->em->getRepository(Specialty::class)->find($id);
        if (!$specialty) {
            return $this->json(['error' => 'Specialty not found'], Response::HTTP_NOT_FOUND);
        }

        $services = $this->em->getRepository(MedicalService::class)->findBy(['specialty' => $specialty]);
        if (count($services) > 0) {
            return $this->json(
                ['error' => 'Cannot delete: there are medical services using this specialty'],
                Response::HTTP_CONFLICT
            );
        }

        $this->em->remove($specialty);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function buildDto(array $body): SpecialtyDTO
    {
        $dto       = new SpecialtyDTO();
        $dto->name = trim((string) ($body['name'] ?? ''));
        $dto->slug = trim((string) ($body['slug'] ?? ''));

        return $dto;
    }
}
