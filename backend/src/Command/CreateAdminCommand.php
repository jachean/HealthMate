<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:admin:create',
    description: 'Creates an admin user account for testing',
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email address')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password')
            ->addOption('username', null, InputOption::VALUE_OPTIONAL, 'Display username', 'admin')
            ->addOption('first-name', null, InputOption::VALUE_OPTIONAL, 'First name', 'Admin')
            ->addOption('last-name', null, InputOption::VALUE_OPTIONAL, 'Last name', 'User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email')
            ?? $io->ask('Email', 'admin@healthmate.local');

        $password = $input->getArgument('password')
            ?? $io->askHidden('Password');

        if (!$email || !$password) {
            $io->error('Email and password are required.');
            return Command::FAILURE;
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            $io->error("A user with email \"$email\" already exists.");
            return Command::FAILURE;
        }

        $username = $input->getOption('username');
        if ($this->userRepository->findOneBy(['username' => $username])) {
            $io->error("A user with username \"$username\" already exists. Use --username to choose a different one.");
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setFirstName($input->getOption('first-name'));
        $user->setLastName($input->getOption('last-name'));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        $io->success("Admin account created successfully.");
        $io->table(
            ['Field', 'Value'],
            [
                ['Email', $email],
                ['Username', $user->getDisplayUsername()],
                ['Roles', implode(', ', $user->getRoles())],
            ]
        );

        return Command::SUCCESS;
    }
}
