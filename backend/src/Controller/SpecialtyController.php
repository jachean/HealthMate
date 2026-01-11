<?php

namespace App\Controller;

use App\Repository\SpecialtyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/specialties')]
final class SpecialtyController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(SpecialtyRepository $specialtyRepository): JsonResponse
    {
        $specialties = $specialtyRepository->findBy([], ['name' => 'ASC']);

        return $this->json($specialties, context: ['groups' => ['specialty:list']]);
    }
}
