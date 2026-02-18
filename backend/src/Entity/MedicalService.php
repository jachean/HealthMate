<?php

namespace App\Entity;

use App\Repository\MedicalServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedicalServiceRepository::class)]
class MedicalService
{
    #[Groups(['medical_service:list', 'doctor_service:list'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['medical_service:list', 'doctor_service:list'])]
    #[ORM\Column(length: 150)]
    private string $name;

    #[Groups(['medical_service:list', 'doctor_service:list'])]
    #[ORM\Column(length: 150)]
    private string $slug;

    #[Groups(['medical_service:list'])]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Specialty $specialty = null;

    /**
     * @var Collection<int, DoctorService>
     */
    #[ORM\OneToMany(targetEntity: DoctorService::class, mappedBy: 'medicalService')]
    private Collection $doctorServices;

    public function __construct()
    {
        $this->doctorServices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSpecialty(): ?Specialty
    {
        return $this->specialty;
    }

    public function setSpecialty(?Specialty $specialty): static
    {
        $this->specialty = $specialty;

        return $this;
    }

    /**
     * @return Collection<int, DoctorService>
     */
    public function getDoctorServices(): Collection
    {
        return $this->doctorServices;
    }

    public function addDoctorService(DoctorService $doctorService): static
    {
        if (!$this->doctorServices->contains($doctorService)) {
            $this->doctorServices->add($doctorService);
            $doctorService->setMedicalService($this);
        }

        return $this;
    }

    public function removeDoctorService(DoctorService $doctorService): static
    {
        $this->doctorServices->removeElement($doctorService);

        return $this;
    }
}
