<?php

namespace App\Entity;

use App\Repository\DoctorServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctorServiceRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_doctor_service', columns: ['doctor_id', 'medical_service_id'])]
class DoctorService
{
    #[Groups(['doctor_service:list'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'doctorServices')]
    #[ORM\JoinColumn(nullable: false)]
    private Doctor $doctor;

    #[Groups(['doctor_service:list'])]
    #[ORM\ManyToOne(inversedBy: 'doctorServices')]
    #[ORM\JoinColumn(nullable: false)]
    private MedicalService $medicalService;

    #[Groups(['doctor_service:list'])]
    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $price;

    #[Groups(['doctor_service:list'])]
    #[ORM\Column]
    private int $durationMinutes;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'doctorService')]
    private Collection $appointments;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMedicalService(): MedicalService
    {
        return $this->medicalService;
    }

    public function setMedicalService(MedicalService $medicalService): static
    {
        $this->medicalService = $medicalService;

        return $this;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes): static
    {
        $this->durationMinutes = $durationMinutes;

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }
}
