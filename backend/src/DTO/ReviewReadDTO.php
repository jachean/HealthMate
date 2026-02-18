<?php

namespace App\DTO;

final class ReviewReadDTO
{
    public function __construct(
        public int $id,
        public float $rating,
        public ?string $comment,
        public string $createdAt,
        public string $authorName,
    ) {
    }
}
