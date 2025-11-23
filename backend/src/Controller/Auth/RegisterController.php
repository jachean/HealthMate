<?php

namespace App\Controller\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

final class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function __invoke(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return $this->json(
                ['errors' => ['body' => 'Invalid JSON payload.']],
                Response::HTTP_BAD_REQUEST
            );
        }

        $email           = trim($data['email'] ?? '');
        $username        = trim($data['username'] ?? '');
        $firstName       = trim($data['firstName'] ?? '');
        $lastName        = trim($data['lastName'] ?? '');
        $password        = $data['password'] ?? '';
        $confirmPassword = $data['confirmPassword'] ?? '';

        $errors = [];

        if ($email === '') {
            $errors['email'][] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Invalid email address.';
        } elseif ($userRepository->findOneBy(['email' => $email])) {
            $errors['email'][] = 'Email already exists.';
        }

        if ($username === '') {
            $errors['username'][] = 'Username is required.';
        } elseif ($userRepository->findOneBy(['username' => $username])) {
            $errors['username'][] = 'Username already exists.';
        }

        if ($firstName === '') {
            $errors['firstName'][] = 'First name is required.';
        }

        if ($lastName === '') {
            $errors['lastName'][] = 'Last name is required.';
        }

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        }

        if ($confirmPassword === '') {
            $errors['confirmPassword'][] = 'Confirm password is required.';
        }

        if ($password !== '' && $confirmPassword !== '' && $password !== $confirmPassword) {
            $errors['confirmPassword'][] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return $this->json(
                ['errors' => $errors],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = new User();
        $user
            ->setEmail($email)
            ->setUsername($username)
            ->setFirstName($firstName)
            ->setLastName($lastName);

        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return $this->json(
            [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'username'  => $user->getUsername(),
                'firstName' => $user->getFirstName(),
                'lastName'  => $user->getLastName(),
            ],
            Response::HTTP_CREATED
        );
    }
}
