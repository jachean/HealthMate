<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AppointmentCreateDTO
{
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    public int $timeSlotId;
}
