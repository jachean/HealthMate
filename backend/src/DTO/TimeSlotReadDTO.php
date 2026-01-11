<?php

namespace App\DTO;

class TimeSlotReadDTO
{
    public function __construct(
        public int $id,
        public string $startAt,
        public string $endAt
    ) {
    }
}
