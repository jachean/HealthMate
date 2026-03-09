<?php

namespace App\Controller\Admin;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_CLINIC_ADMIN')]
class UploadController extends AdminController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {
    }

    #[Route('/upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $type = $request->query->get('type', '');
        if (!in_array($type, ['doctors', 'clinics'], true)) {
            return $this->json(
                ['error' => 'Invalid type. Must be "doctors" or "clinics".'],
                Response::HTTP_BAD_REQUEST
            );
        }

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

        $maxSize = 5 * 1024 * 1024; // 5 MB
        if ($file->getSize() > $maxSize) {
            return $this->json(
                ['error' => 'File too large. Maximum size is 5 MB.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $extension = match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        };

        $filename  = bin2hex(random_bytes(16)) . '.' . $extension;
        $uploadDir = $this->projectDir . '/public/uploads/' . $type;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $filename);

        return $this->json(['path' => '/uploads/' . $type . '/' . $filename]);
    }
}
