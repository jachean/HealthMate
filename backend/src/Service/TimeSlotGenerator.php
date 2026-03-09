<?php

namespace App\Service;

use App\Entity\Doctor;
use App\Entity\TimeSlot;
use App\Repository\DoctorRepository;
use App\Repository\DoctorUnavailabilityRepository;
use App\Repository\TimeSlotRepository;
use Doctrine\ORM\EntityManagerInterface;

final class TimeSlotGenerator
{
    public const DAYS_AHEAD = 30;

    private const BATCH_SIZE = 50;
    private const SLOT_DURATION_MINUTES = 60;
    private const SLOT_STEP_MINUTES = 30;

    public function __construct(
        private readonly DoctorRepository $doctorRepository,
        private readonly TimeSlotRepository $timeSlotRepository,
        private readonly DoctorUnavailabilityRepository $unavailabilityRepository,
        private readonly EntityManagerInterface $em
    ) {
    }

    public function run(): void
    {
        $this->cleanupOldUnbookedSlots();
        $this->generateFutureSlots();
    }

    public function generateForDoctor(Doctor $doctor): void
    {
        $this->generateSlotsForDoctor($doctor);
        $this->em->flush();
    }

    private function generateFutureSlots(): void
    {
        // Fetch only IDs so we can safely call em->clear() between batches
        // without detaching the doctor objects we still need.
        $ids    = $this->doctorRepository->findActiveIds();
        $chunks = array_chunk($ids, self::BATCH_SIZE);

        foreach ($chunks as $idBatch) {
            $doctors = $this->doctorRepository->findBy(['id' => $idBatch]);
            foreach ($doctors as $doctor) {
                $this->generateSlotsForDoctor($doctor);
            }
            $this->em->flush();
            $this->em->clear(); // safe: next batch re-fetches doctors by ID
        }
    }

    private function cleanupOldUnbookedSlots(): void
    {
        $cutoff = new \DateTimeImmutable('today');

        $this->timeSlotRepository->deleteOldUnbookedSlots($cutoff);
    }


    private function generateSlotsForDoctor(Doctor $doctor): void
    {
        $today   = new \DateTimeImmutable('today');
        $horizon = $today->modify('+' . self::DAYS_AHEAD . ' days');

        // One query for the whole range — O(1) lookup per candidate slot
        $existing = $this->timeSlotRepository->findExistingSlotStartTimes(
            $doctor->getId(),
            $today,
            $horizon
        );

        // Build a set of blocked dates for O(1) lookup
        $unavailabilities = $this->unavailabilityRepository->findForDoctor($doctor);
        $blockedDates = [];
        foreach ($unavailabilities as $period) {
            $cursor = $period->getDateFrom();
            while ($cursor <= $period->getDateTo()) {
                $blockedDates[$cursor->format('Y-m-d')] = true;
                $cursor = $cursor->modify('+1 day');
            }
        }

        $workDays  = $doctor->getWorkDays();
        $startHour = $doctor->getStartHour();
        $endHour   = $doctor->getEndHour();

        for ($day = 0; $day < self::DAYS_AHEAD; $day++) {
            $date = $today->modify("+{$day} days");

            // format('N') → 1 (Mon) … 7 (Sun), matching ISO weekday convention
            if (!in_array((int) $date->format('N'), $workDays, true)) {
                continue;
            }

            // Skip blocked (unavailability) dates
            if (isset($blockedDates[$date->format('Y-m-d')])) {
                continue;
            }

            $startOfDay = $date->setTime($startHour, 0);
            $endOfDay   = $date->setTime($endHour, 0);

            for (
                $cursor = $startOfDay;
                $cursor->modify('+' . self::SLOT_DURATION_MINUTES . ' minutes') <= $endOfDay;
                $cursor = $cursor->modify('+' . self::SLOT_STEP_MINUTES . ' minutes')
            ) {
                $start = $cursor;
                $end   = $cursor->modify('+' . self::SLOT_DURATION_MINUTES . ' minutes');

                if (isset($existing[$start->format('Y-m-d H:i')])) {
                    continue;
                }

                $slot = new TimeSlot();
                $slot->setDoctor($doctor);
                $slot->setStartAt($start);
                $slot->setEndAt($end);
                $slot->setIsBooked(false);

                $this->em->persist($slot);
            }
        }
    }
}
