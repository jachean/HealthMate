<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'float')]
    private float $rating;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private User $author;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    private ?Doctor $doctor = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    private ?Clinic $clinic = null;

    #[ORM\OneToOne(targetEntity: Appointment::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Appointment $appointment = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $authorName = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRating(): float
    {
        return (float) $this->rating;
    }

    public function setRating(float $rating): static
    {
        $rounded = round($rating * 2) / 2;
        if ($rounded < 0.5 || $rounded > 5.0) {
            throw new \InvalidArgumentException('Rating must be between 0.5 and 5.0 in 0.5 increments.');
        }

        $this->rating = $rounded;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getDoctor(): ?Doctor
    {
        return $this->doctor;
    }

    public function setDoctor(?Doctor $doctor): static
    {
        if ($doctor !== null) {
            $this->clinic = null;
        }

        $this->doctor = $doctor;

        return $this;
    }

    public function getClinic(): ?Clinic
    {
        return $this->clinic;
    }

    public function setClinic(?Clinic $clinic): static
    {
        if ($clinic !== null) {
            $this->doctor = null;
        }

        $this->clinic = $clinic;

        return $this;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment): static
    {
        $this->appointment = $appointment;

        return $this;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(?string $authorName): static
    {
        $this->authorName = $authorName;

        return $this;
    }
}
