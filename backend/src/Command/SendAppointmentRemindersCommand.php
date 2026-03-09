<?php

namespace App\Command;

use App\Email\AppointmentReminderEmail;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:send-appointment-reminders',
    description: 'Send reminder emails for appointments starting within the next 24 hours.',
)]
class SendAppointmentRemindersCommand extends Command
{
    public function __construct(
        private AppointmentRepository $appointments,
        private MailerInterface $mailer,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $fromEmail = $_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@healthmate.ro';
        $fromName  = $_ENV['MAILER_FROM_NAME'] ?? 'HealthMate';

        $pending = $this->appointments->findPendingReminders();

        if (empty($pending)) {
            $io->success('No reminders to send.');
            return Command::SUCCESS;
        }

        $sent   = 0;
        $failed = 0;

        foreach ($pending as $appointment) {
            try {
                $email = AppointmentReminderEmail::create($appointment, $fromEmail, $fromName);
                $this->mailer->send($email);
                $appointment->setReminderSentAt(new \DateTimeImmutable());
                $sent++;
            } catch (\Throwable $e) {
                $io->warning(sprintf(
                    'Failed to send reminder for appointment #%d: %s',
                    $appointment->getId(),
                    $e->getMessage()
                ));
                $failed++;
            }
        }

        $this->em->flush();

        $io->success(sprintf('Sent %d reminder(s). Failed: %d.', $sent, $failed));

        return Command::SUCCESS;
    }
}
