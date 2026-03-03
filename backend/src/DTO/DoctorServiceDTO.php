<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DoctorServiceDTO
{
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    public int $medicalServiceId;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: 'Price must be a valid decimal number.')]
    public string $price;

    #[Assert\NotNull]
    #[Assert\Range(min: 5, max: 480)]
    public int $durationMinutes;
}
