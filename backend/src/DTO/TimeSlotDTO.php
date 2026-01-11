<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TimeSlotDTO
{
    #[Assert\NotBlank]
    #[Assert\DateTime]
    public string $startAt;

    #[Assert\NotBlank]
    #[Assert\DateTime]
    public string $endAt;

    #[Assert\NotNull]
    public bool $isBooked;

    #[Assert\NotNull]
    #[Assert\Type('integer')]
    public int $doctorId;
}
