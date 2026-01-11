<?php

namespace App\Entity;

use App\Repository\TimeSlotRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TimeSlotRepository::class)]
class TimeSlot
{
    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $startAt;

    #[ORM\Column]
    private \DateTimeImmutable $endAt;

    #[ORM\Column]
    private bool $isBooked;

    #[ORM\ManyToOne(inversedBy: 'timeSlots')]
    #[ORM\JoinColumn(nullable: false)]
    private Doctor $doctor;

    #[ORM\OneToOne(mappedBy: 'timeSlot', cascade: ['persist'])]
    private ?Appointment $appointment = null;

    public function __construct()
    {
        $this->isBooked = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): \DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): static
    {
        if ($endAt <= $this->startAt) {
            throw new \InvalidArgumentException('End time must be after start time.');
        }

        $this->endAt = $endAt;

        return $this;
    }

    public function isBooked(): bool
    {
        return $this->isBooked;
    }

    public function setIsBooked(bool $isBooked): static
    {
        $this->isBooked = $isBooked;

        return $this;
    }

    public function getDoctor(): Doctor
    {
        return $this->doctor;
    }

    public function setDoctor(Doctor $doctor): static
    {
        $this->doctor = $doctor;

        return $this;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment): static
    {
        if ($appointment !== null && $appointment->getTimeSlot() !== $this) {
            $appointment->setTimeSlot($this);
        }

        $this->appointment = $appointment;

        return $this;
    }
}
