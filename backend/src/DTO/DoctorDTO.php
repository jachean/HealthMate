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

    #[Assert\NotNull]
    #[Assert\Count(min: 1)]
    #[Assert\All([new Assert\Choice(choices: [1, 2, 3, 4, 5, 6, 7])])]
    public array $workDays = [1, 2, 3, 4, 5];

    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 23)]
    public int $startHour = 9;

    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 24)]
    public int $endHour = 17;

    public ?string $avatar = null;
}
