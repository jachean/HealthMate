<?php

namespace App\Controller\Admin;

use App\Service\TimeSlotGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/tools')]
#[IsGranted('ROLE_ADMIN')]
class AdminToolsController extends AdminController
{
    public function __construct(
        private TimeSlotGenerator $generator,
    ) {
    }

    #[Route('/regenerate-slots', methods: ['POST'])]
    public function regenerateSlots(): Response
    {
        $this->generator->run();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
