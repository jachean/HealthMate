<?php

namespace App\Command;

use App\Entity\Appointment;
use App\Entity\Review;
use App\Entity\TimeSlot;
use App\Repository\DoctorRepository;
use App\Repository\DoctorServiceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reviews:generate',
    description: 'Generates past appointments with realistic reviews for all active doctors'
)]
final class GenerateReviewsCommand extends Command
{
    private const DAY_START_HOUR = 9;
    private const DAY_END_HOUR = 17;
    private const SLOT_DURATION_MINUTES = 60;

    private const COMMENTS = [
        '5.0' => [
            'Exceptional doctor, very thorough and caring. Highly recommend!',
            'Outstanding experience from start to finish. Will definitely return.',
            'Best consultation I\'ve had. Listened carefully and explained everything clearly.',
            'Incredibly professional and knowledgeable. Felt truly cared for.',
        ],
        '4.5' => [
            'Very good doctor, took the time to explain my condition in detail.',
            'Great experience overall. Would recommend to family and friends.',
            'Very professional and attentive. Minor wait time but worth it.',
            'Knowledgeable and kind. The appointment was efficient and helpful.',
        ],
        '4.0' => [
            'Good consultation, clear explanation of the diagnosis.',
            'Solid doctor, answered all my questions patiently.',
            'Pleasant experience. Could have spent a bit more time with me.',
            'Competent and professional. Waiting room was a bit crowded.',
        ],
        '3.5' => [
            'Decent consultation but felt a bit rushed.',
            'OK experience. The doctor was fine but not very communicative.',
            'Average visit. Got what I needed but nothing exceptional.',
        ],
        '3.0' => [
            'Satisfactory appointment, but expected more thorough examination.',
            'The doctor was professional but the wait was quite long.',
        ],
        '2.5' => [
            'Not very impressed. Felt like the consultation was too short.',
            'Below expectations. The doctor seemed distracted.',
        ],
        '2.0' => [
            'Disappointing experience. My concerns were not fully addressed.',
        ],
    ];

    public function __construct(
        private readonly DoctorRepository $doctorRepository,
        private readonly DoctorServiceRepository $doctorServiceRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'days',
            'd',
            InputOption::VALUE_OPTIONAL,
            'How many days into the past to generate appointments for',
            30
        );

        $this->addOption(
            'per-doctor',
            null,
            InputOption::VALUE_OPTIONAL,
            'Number of reviewed appointments to generate per doctor',
            5
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = max(1, (int) $input->getOption('days'));
        $perDoctor = max(1, (int) $input->getOption('per-doctor'));

        $io->title('Generating past appointments with reviews');

        $doctors = $this->doctorRepository->findBy(['isActive' => true]);
        $users = $this->userRepository->findAll();

        if (empty($doctors)) {
            $io->error('No active doctors found. Run fixtures first.');
            return Command::FAILURE;
        }

        if (empty($users)) {
            $io->error('No users found. Run fixtures first.');
            return Command::FAILURE;
        }

        $totalReviews = 0;

        foreach ($doctors as $doctor) {
            $services = $this->doctorServiceRepository->findByDoctor($doctor->getId());

            if (empty($services)) {
                continue;
            }

            $io->write("  Dr. {$doctor->getFirstName()} {$doctor->getLastName()}... ");

            $created = 0;

            // Spread appointments evenly across the past N days
            $dayOffsets = $this->pickDayOffsets($days, $perDoctor);

            foreach ($dayOffsets as $daysAgo) {
                $date = new \DateTimeImmutable("-{$daysAgo} days");
                $hour = random_int(self::DAY_START_HOUR, self::DAY_END_HOUR - 1);
                $startAt = $date->setTime($hour, 0, 0);
                $endAt = $startAt->modify('+' . self::SLOT_DURATION_MINUTES . ' minutes');

                // Create a past time slot (already booked)
                $slot = new TimeSlot();
                $slot->setDoctor($doctor);
                $slot->setStartAt($startAt);
                $slot->setEndAt($endAt);
                $slot->setIsBooked(true);
                $this->em->persist($slot);

                // Pick random user and service
                $user = $users[array_rand($users)];
                $service = $services[array_rand($services)];

                $appointment = new Appointment();
                $appointment->setUser($user);
                $appointment->setTimeSlot($slot);
                $appointment->setDoctorService($service);
                $this->em->persist($appointment);

                // Create review
                $rating = $this->pickRating();
                $authorName = $user->getFirstName() . ' ' . mb_substr($user->getLastName(), 0, 1) . '.';

                $review = new Review();
                $review->setRating($rating);
                $review->setComment($this->pickComment($rating));
                $review->setAuthor($user);
                $review->setDoctor($doctor);
                $review->setAppointment($appointment);
                $review->setAuthorName($authorName);
                // Spread review dates to match appointment dates
                $review->setCreatedAt(\DateTimeImmutable::createFromMutable(
                    \DateTime::createFromImmutable($startAt)->modify('+' . random_int(10, 120) . ' minutes')
                ));
                $this->em->persist($review);

                $created++;
            }

            $this->em->flush();
            $totalReviews += $created;

            $io->writeln("<info>{$created} reviews</info>");
        }

        $io->success("Done. Created {$totalReviews} reviewed appointments across " . count($doctors) . " doctors.");

        return Command::SUCCESS;
    }

    /**
     * Pick $count unique day offsets (1...$days ago), spread across the range.
     */
    private function pickDayOffsets(int $days, int $count): array
    {
        $count = min($count, $days);
        $offsets = range(1, $days);
        shuffle($offsets);
        return array_slice($offsets, 0, $count);
    }

    /**
     * Weighted random rating biased toward 4.0–5.0.
     */
    private function pickRating(): float
    {
        $weights = [
            '5.0' => 25,
            '4.5' => 30,
            '4.0' => 20,
            '3.5' => 10,
            '3.0' => 8,
            '2.5' => 5,
            '2.0' => 2,
        ];

        $total = array_sum($weights);
        $rand = random_int(1, $total);
        $cumulative = 0;

        foreach ($weights as $rating => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return (float) $rating;
            }
        }

        return 4.0;
    }

    private function pickComment(float $rating): ?string
    {
        // 20% chance of no comment
        if (random_int(1, 100) <= 20) {
            return null;
        }

        // Find the closest rating bucket
        $buckets = array_keys(self::COMMENTS);
        $closest = $buckets[0];
        foreach ($buckets as $bucket) {
            if (abs($bucket - $rating) <= abs($closest - $rating)) {
                $closest = $bucket;
            }
        }

        $comments = self::COMMENTS[$closest];
        return $comments[array_rand($comments)];
    }
}
