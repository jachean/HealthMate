<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserRole;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $clinicAdminClinicId = null;
        foreach ($user->getUserRoles() as $userRole) {
            /** @var UserRole $userRole */
            if ($userRole->getRole() === 'ROLE_CLINIC_ADMIN' && $userRole->getClinic() !== null) {
                $clinicAdminClinicId = $userRole->getClinic()->getId();
                break;
            }
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getDisplayUsername(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
            'clinicAdminClinicId' => $clinicAdminClinicId,
        ]);
    }
}
