<?php

namespace App\Controller\Auth;

use Symfony\Component\Routing\Annotation\Route;

final class LoginController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function __invoke(): never
    {
        // Never called: handled by json_login authenticator
        throw new \LogicException('This route is handled by the security system.');
    }
}
