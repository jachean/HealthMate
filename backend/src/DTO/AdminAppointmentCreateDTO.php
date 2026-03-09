<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AdminAppointmentCreateDTO
{
    public function __construct(
        #[Assert\NotNull(message: 'User is required.')]
        #[Assert\Positive]
        public readonly int $userId,
        #[Assert\NotNull(message: 'Time slot is required.')]
        #[Assert\Positive]
        public readonly int $timeSlotId,
        #[Assert\NotNull(message: 'Doctor service is required.')]
        #[Assert\Positive]
        public readonly int $doctorServiceId,
    ) {
    }
}
