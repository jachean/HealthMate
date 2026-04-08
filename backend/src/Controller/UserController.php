<?php

namespace App\Controller;

use App\DTO\UserUpdateDTO;
use App\Entity\User;
use App\Entity\UserRole;
use App\Repository\DoctorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function me(DoctorRepository $doctorRepository): JsonResponse
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

        $doctor   = $doctorRepository->findOneBy(['user' => $user]);
        $doctorId = $doctor?->getId();

        return $this->json([
            'id'                  => $user->getId(),
            'email'               => $user->getEmail(),
            'username'            => $user->getDisplayUsername(),
            'firstName'           => $user->getFirstName(),
            'lastName'            => $user->getLastName(),
            'profileImage'        => $user->getProfileImage(),
            'roles'               => $user->getRoles(),
            'clinicAdminClinicId' => $clinicAdminClinicId,
            'doctorId'            => $doctorId,
        ]);
    }

    #[Route('/api/me', name: 'api_me_update', methods: ['PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateMe(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $body = json_decode($request->getContent(), true) ?? [];

        $dto = new UserUpdateDTO();
        $dto->firstName = trim($body['firstName'] ?? '');
        $dto->lastName  = trim($body['lastName']  ?? '');
        $dto->username  = trim($body['username']  ?? '');

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $e) {
                $messages[$e->getPropertyPath()] = $e->getMessage();
            }
            return $this->json(['errors' => $messages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check username uniqueness (exclude current user)
        $existing = $em->getRepository(User::class)->findOneBy(['username' => $dto->username]);
        if ($existing && $existing->getId() !== $user->getId()) {
            return $this->json(
                ['errors' => ['username' => 'This username is already taken.']],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setUsername($dto->username);

        $em->flush();

        return $this->json([
            'id'           => $user->getId(),
            'email'        => $user->getEmail(),
            'username'     => $user->getDisplayUsername(),
            'firstName'    => $user->getFirstName(),
            'lastName'     => $user->getLastName(),
            'profileImage' => $user->getProfileImage(),
            'roles'        => $user->getRoles(),
        ]);
    }

    #[Route('/api/me/upload-avatar', name: 'api_me_upload_avatar', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function uploadAvatar(
        Request $request,
        EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded.'], Response::HTTP_BAD_REQUEST);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            return $this->json(
                ['error' => 'Invalid file type. Allowed: jpeg, png, webp.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $maxSize = 2 * 1024 * 1024; // 2 MB
        if ($file->getSize() > $maxSize) {
            return $this->json(
                ['error' => 'File too large. Maximum size is 2 MB.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        };

        $filename  = 'avatar_' . $user->getId() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $uploadDir = $projectDir . '/public/uploads/avatars';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Remove old avatar file if it was stored locally
        $oldPath = $user->getProfileImage();
        if ($oldPath && str_starts_with($oldPath, '/uploads/avatars/')) {
            $oldFile = $projectDir . '/public' . $oldPath;
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        $file->move($uploadDir, $filename);

        $path = '/uploads/avatars/' . $filename;
        $user->setProfileImage($path);
        $em->flush();

        return $this->json(['profileImage' => $path]);
    }
}
