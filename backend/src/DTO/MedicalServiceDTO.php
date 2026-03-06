<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class MedicalServiceDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public string $name = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    #[Assert\Regex(pattern: '/^[a-z0-9-]+$/', message: 'Slug may only contain lowercase letters, digits and hyphens.')]
    public string $slug = '';

    public ?int $specialtyId = null;
}
