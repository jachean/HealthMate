<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ReviewCreateDTO
{
    #[Assert\NotNull]
    #[Assert\Range(min: 0.5, max: 5.0)]
    public float $rating;

    #[Assert\Length(max: 500)]
    public ?string $comment = null;
}
