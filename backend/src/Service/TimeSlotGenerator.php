<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Doctor;
use App\Entity\TimeSlot;
use App\Repository\DoctorRepository;
use App\Repository\TimeSlotRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class TimeSlotGenerator
{
    private const DAYS_AHEAD = 7;
    private const DAY_START_HOUR = 9;
    private const DAY_END_HOUR = 17;

    private const SLOT_DURATION_MINUTES = 60;
    private const SLOT_STEP_MINUTES = 30;

    private const DEMO_BOOKING_PROBABILITY = 15; // %

    public function __construct(
        private readonly DoctorRepository $doctorRepository,
        private readonly TimeSlotRepository $timeSlotRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em
    ) {
    }

    public function run(): void
    {
        $this->cleanupOldUnbookedSlots();
        $this->generateFutureSlots();
    }

    private function generateFutureSlots(): void
    {
        $doctors = $this->doctorRepository->findBy(['isActive' => true]);
        $users = $this->userRepository->findAll();

        foreach ($doctors as $doctor) {
            $this->generateSlotsForDoctor($doctor, $users);
        }

        $this->em->flush();
    }

    private function cleanupOldUnbookedSlots(): void
    {
        $cutoff = new \DateTimeImmutable('-7 days');

        $this->timeSlotRepository->deleteOldUnbookedSlots($cutoff);
    }


    private function generateSlotsForDoctor(Doctor $doctor, array $users): void
    {
        $today = new \DateTimeImmutable('today');

        for ($day = 0; $day < self::DAYS_AHEAD; $day++) {
            $date = $today->modify("+{$day} days");

            $startOfDay = $date->setTime(self::DAY_START_HOUR, 0);
            $endOfDay = $date->setTime(self::DAY_END_HOUR, 0);

            for (
                $cursor = $startOfDay;
                $cursor->modify('+' . self::SLOT_DURATION_MINUTES . ' minutes') <= $endOfDay;
                $cursor = $cursor->modify('+' . self::SLOT_STEP_MINUTES . ' minutes')
            ) {
                $start = $cursor;
                $end = $cursor->modify('+' . self::SLOT_DURATION_MINUTES . ' minutes');

                if ($this->slotExists($doctor, $start, $end)) {
                    continue;
                }

                $slot = new TimeSlot();
                $slot->setDoctor($doctor);
                $slot->setStartAt($start);
                $slot->setEndAt($end);
                $slot->setIsBooked(false);

                if (!empty($users) && random_int(1, 100) <= self::DEMO_BOOKING_PROBABILITY) {
                    if (!$this->hasOverlappingAppointment($doctor, $start, $end)) {
                        $this->createDemoAppointment($slot, $users);
                    }
                }

                $this->em->persist($slot);
            }
        }
    }

    private function slotExists(Doctor $doctor, \DateTimeImmutable $start, \DateTimeImmutable $end): bool
    {
        return $this->timeSlotRepository->findOneBy([
                'doctor' => $doctor,
                'startAt' => $start,
                'endAt' => $end,
            ]) !== null;
    }

    private function hasOverlappingAppointment(
        Doctor $doctor,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): bool {
        return (bool) $this->em->createQueryBuilder()
            ->select('1')
            ->from(Appointment::class, 'a')
            ->join('a.timeSlot', 'ts')
            ->where('ts.doctor = :doctor')
            ->andWhere('ts.startAt < :end')
            ->andWhere('ts.endAt > :start')
            ->setParameter('doctor', $doctor)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function createDemoAppointment(TimeSlot $slot, array $users): void
    {
        $user = $users[array_rand($users)];

        $appointment = new Appointment();
        $appointment->setUser($user);
        $appointment->setTimeSlot($slot);

        $slot->setIsBooked(true);

        $this->em->persist($appointment);
    }
}
