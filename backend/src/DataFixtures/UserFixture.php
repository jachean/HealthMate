<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixture extends Fixture
{
    public const USER_COUNT = 10;

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            ['firstName' => 'Ion', 'lastName' => 'Popescu', 'username' => 'ion.popescu', 'email' => 'ion@example.com'],
            ['firstName' => 'Maria', 'lastName' => 'Ionescu', 'username' => 'maria.ionescu',
                'email' => 'maria@example.com'],
            ['firstName' => 'Andrei', 'lastName' => 'Dumitrescu', 'username' => 'andrei.d',
                'email' => 'andrei@example.com'],
            ['firstName' => 'Elena', 'lastName' => 'Popa', 'username' => 'elena.popa', 'email' => 'elena@example.com'],
            ['firstName' => 'Mihai', 'lastName' => 'Stan', 'username' => 'mihai.stan', 'email' => 'mihai@example.com'],
            ['firstName' => 'Ana', 'lastName' => 'Radu', 'username' => 'ana.radu', 'email' => 'ana@example.com'],
            ['firstName' => 'Cristian', 'lastName' => 'Marin', 'username' => 'cristian.m',
                'email' => 'cristian@example.com'],
            ['firstName' => 'Laura', 'lastName' => 'Gheorghe', 'username' => 'laura.g', 'email' => 'laura@example.com'],
            ['firstName' => 'Stefan', 'lastName' => 'Stoica', 'username' => 'stefan.stoica',
                'email' => 'stefan@example.com'],
            ['firstName' => 'Admin', 'lastName' => 'User', 'username' => 'admin', 'email' => 'admin@example.com'],
        ];

        foreach ($users as $i => $data) {
            $user = new User();
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setUsername($data['username']);
            $user->setEmail($data['email']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));

            if ($data['username'] === 'admin') {
                $user->addRole('ROLE_ADMIN');
            }

            $manager->persist($user);
            $this->addReference('user_' . $i, $user);
        }

        $manager->flush();
    }
}
