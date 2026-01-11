<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ClinicDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\Length(max: 255)]
    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $address;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $city;
}
