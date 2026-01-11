<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ReviewDTO
{
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5)]
    public int $rating;

    #[Assert\Length(max: 255)]
    public ?string $comment = null;

    #[Assert\Type('integer')]
    public ?int $doctorId = null;

    #[Assert\Type('integer')]
    public ?int $clinicId = null;
}
