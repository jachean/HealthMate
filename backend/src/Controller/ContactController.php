<?php

namespace App\Controller;

use App\Email\ContactConfirmationEmail;
use App\Email\ContactNotificationEmail;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/api/contact', name: 'api_contact', methods: ['POST'])]
    public function __invoke(
        Request $request,
        MailerInterface $mailer,
        LoggerInterface $logger,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid payload.'], Response::HTTP_BAD_REQUEST);
        }

        $name    = trim($data['name'] ?? '');
        $email   = trim($data['email'] ?? '');
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');

        if ($name === '' || $email === '' || $message === '') {
            return $this->json(['error' => 'Name, email and message are required.'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email address.'], Response::HTTP_BAD_REQUEST);
        }

        $fromEmail = $_ENV['MAILER_FROM_EMAIL'];
        $fromName  = $_ENV['MAILER_FROM_NAME'] ?? 'HealthMate';

        try {
            // Branded notification to the support inbox
            $notification = ContactNotificationEmail::create(
                $name,
                $email,
                $subject,
                $message,
                $fromEmail,
                $fromName,
            );

            $mailer->send($notification);

            // Confirmation to the user
            $confirmation = ContactConfirmationEmail::create(
                $email,
                $name,
                $subject,
                $message,
                $fromEmail,
                $fromName,
            );

            $mailer->send($confirmation);
        } catch (\Throwable $e) {
            $logger->error('Contact email failed: ' . $e->getMessage());

            return $this->json(
                ['error' => 'Failed to send message. Please try again.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json(['success' => true], Response::HTTP_OK);
    }
}
