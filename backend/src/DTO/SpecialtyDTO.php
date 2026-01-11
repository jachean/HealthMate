<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SpecialtyDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[Assert\Regex('/^[a-z0-9-]+$/')]
    public string $slug;
}
