<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DoctorDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $lastName;

    #[Assert\Length(max: 255)]
    public ?string $bio = null;

    #[Assert\NotNull]
    public bool $acceptsInsurance;

    #[Assert\NotNull]
    public bool $isActive;

    #[Assert\NotNull]
    #[Assert\Type('integer')]
    public int $clinicId;

    #[Assert\NotNull]
    #[Assert\Count(min: 1)]
    public array $specialtyIds = [];
}
