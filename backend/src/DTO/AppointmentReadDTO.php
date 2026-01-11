<?php

namespace App\DTO;

final class AppointmentReadDTO
{
    public function __construct(
        public int $id,
        public string $status,
        public string $createdAt,
        public int $doctorId,
        public string $doctorName,
        public string $clinicName,
        public string $startAt,
        public string $endAt,
    ) {
    }
}
