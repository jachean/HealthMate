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
}
