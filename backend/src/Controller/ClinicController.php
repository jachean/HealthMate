<?php

namespace App\Controller;

use App\Repository\ClinicRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/clinics')]
final class ClinicController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function list(Request $request, ClinicRepository $clinicRepository): JsonResponse
    {
        $city = $request->query->get('city');
        $city = is_string($city) && $city !== '' ? $city : null;

        $clinics = $clinicRepository->findForFilters($city);

        return $this->json($clinics, context: ['groups' => ['clinic:list']]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, ClinicRepository $clinicRepository): JsonResponse
    {
        $clinic = $clinicRepository->find($id);

        if (!$clinic) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Clinic not found.']], 404);
        }

        return $this->json($clinic, context: ['groups' => ['clinic:detail']]);
    }

    #[Route('/{id}/doctors', methods: ['GET'])]
    public function doctors(int $id, ClinicRepository $clinicRepository): JsonResponse
    {
        $clinic = $clinicRepository->find($id);

        if (!$clinic) {
            return $this->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'Clinic not found.']], 404);
        }

        return $this->json($clinic->getDoctors()->toArray(), context: ['groups' => ['doctor:list']]);
    }
}
