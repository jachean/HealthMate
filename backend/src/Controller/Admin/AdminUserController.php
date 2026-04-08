<?php

namespace App\Controller\Admin;

use App\Entity\Clinic;
use App\Entity\Doctor;
use App\Entity\User;
use App\Repository\DoctorRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AdminController
{
    public function __construct(
        private UserRepository $userRepository,
        private DoctorRepository $doctorRepository,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page  = max(1, (int) $request->query->get('page', '1'));
        $limit = max(1, min(100, (int) $request->query->get('limit', '20')));

        $filters = ['search' => $request->query->get('search')];

        $result = $this->userRepository->findAllPaginatedForAdmin($page, $limit, $filters);

        return $this->json([
            'data'  => array_map($this->toArray(...), $result['data']),
            'total' => $result['total'],
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('/{id}/deactivate', methods: ['PATCH'])]
    public function deactivate(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->json(['error' => 'Cannot deactivate admin accounts'], Response::HTTP_FORBIDDEN);
        }

        $user->setIsActive(false);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/activate', methods: ['PATCH'])]
    public function activate(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $user->setIsActive(true);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/make-clinic-admin', methods: ['POST'])]
    public function makeClinicAdmin(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $body     = json_decode($request->getContent(), true) ?? [];
        $clinicId = isset($body['clinicId']) ? (int) $body['clinicId'] : null;

        if (!$clinicId) {
            return $this->json(['error' => 'clinicId is required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $clinic = $this->em->getRepository(Clinic::class)->find($clinicId);
        if (!$clinic) {
            return $this->json(['error' => 'Clinic not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Remove any existing ROLE_CLINIC_ADMIN entries (regardless of clinic)
        foreach ($user->getUserRoles()->toArray() as $userRole) {
            if ($userRole->getRole() === 'ROLE_CLINIC_ADMIN') {
                $user->getUserRoles()->removeElement($userRole);
                $this->em->remove($userRole);
            }
        }

        $user->addRole('ROLE_CLINIC_ADMIN', $clinic);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/remove-clinic-admin', methods: ['DELETE'])]
    public function removeClinicAdmin(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        foreach ($user->getUserRoles()->toArray() as $userRole) {
            if ($userRole->getRole() === 'ROLE_CLINIC_ADMIN') {
                $user->getUserRoles()->removeElement($userRole);
                $this->em->remove($userRole);
            }
        }

        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/make-doctor', methods: ['POST'])]
    public function makeDoctor(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $body     = json_decode($request->getContent(), true) ?? [];
        $doctorId = isset($body['doctorId']) ? (int) $body['doctorId'] : null;

        if (!$doctorId) {
            return $this->json(['error' => 'doctorId is required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $doctor = $this->doctorRepository->find($doctorId);
        if (!$doctor) {
            return $this->json(['error' => 'Doctor not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($doctor->getUser() !== null) {
            return $this->json(
                ['error' => 'This doctor is already linked to a user account'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if (in_array('ROLE_DOCTOR', $user->getRoles(), true)) {
            return $this->json(['error' => 'User already has ROLE_DOCTOR'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $doctor->setUser($user);
        $user->addRole('ROLE_DOCTOR');
        $this->em->flush();

        return $this->json($this->toArray($user));
    }

    #[Route('/{id}/remove-doctor', methods: ['DELETE'])]
    public function removeDoctor(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $doctor = $this->doctorRepository->findOneBy(['user' => $user]);
        if ($doctor) {
            $doctor->setUser(null);
        }

        foreach ($user->getUserRoles()->toArray() as $userRole) {
            if ($userRole->getRole() === 'ROLE_DOCTOR') {
                $user->getUserRoles()->removeElement($userRole);
                $this->em->remove($userRole);
            }
        }

        $this->em->flush();

        return $this->json($this->toArray($user));
    }

    private function toArray(User $user): array
    {
        $clinicAdminClinicId   = null;
        $clinicAdminClinicName = null;
        foreach ($user->getUserRoles() as $userRole) {
            if ($userRole->getRole() === 'ROLE_CLINIC_ADMIN' && $userRole->getClinic() !== null) {
                $clinicAdminClinicId   = $userRole->getClinic()->getId();
                $clinicAdminClinicName = $userRole->getClinic()->getName();
                break;
            }
        }

        $linkedDoctor     = $this->doctorRepository->findOneBy(['user' => $user]);
        $isDoctorUser     = $linkedDoctor !== null;
        $linkedDoctorId   = $linkedDoctor?->getId();
        $linkedDoctorName = $linkedDoctor
            ? $linkedDoctor->getFirstName() . ' ' . $linkedDoctor->getLastName()
            : null;

        return [
            'id'                    => $user->getId(),
            'firstName'             => $user->getFirstName(),
            'lastName'              => $user->getLastName(),
            'email'                 => $user->getEmail(),
            'username'              => $user->getDisplayUsername(),
            'profileImage'          => $user->getProfileImage(),
            'isAdmin'               => in_array('ROLE_ADMIN', $user->getRoles(), true),
            'isClinicAdmin'         => in_array('ROLE_CLINIC_ADMIN', $user->getRoles(), true)
                                       && !in_array('ROLE_ADMIN', $user->getRoles(), true),
            'clinicAdminClinicId'   => $clinicAdminClinicId,
            'clinicAdminClinicName' => $clinicAdminClinicName,
            'isActive'              => $user->isActive(),
            'isDoctorUser'          => $isDoctorUser,
            'linkedDoctorId'        => $linkedDoctorId,
            'linkedDoctorName'      => $linkedDoctorName,
        ];
    }
}
