<?php

namespace App\Entity;

use App\Repository\SpecialtyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpecialtyRepository::class)]
class Specialty
{
    #[Groups(['specialty:list', 'doctor:list', 'doctor:detail'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['specialty:list', 'doctor:list', 'doctor:detail'])]
    #[ORM\Column(length: 100)]
    private string $name;

    #[Groups(['specialty:list', 'doctor:list', 'doctor:detail'])]
    #[ORM\Column(length: 100)]
    private string $slug;

    /**
     * @var Collection<int, Doctor>
     */
    #[ORM\ManyToMany(targetEntity: Doctor::class, mappedBy: 'specialties')]
    private Collection $doctors;

    public function __construct()
    {
        $this->doctors = new ArrayCollection();
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

    /**
     * @return Collection<int, Doctor>
     */
    public function getDoctors(): Collection
    {
        return $this->doctors;
    }

    public function addDoctor(Doctor $doctor): static
    {
        if (!$this->doctors->contains($doctor)) {
            $this->doctors->add($doctor);

            $doctor->addSpecialty($this);
        }

        return $this;
    }

    public function removeDoctor(Doctor $doctor): static
    {
        $this->doctors->removeElement($doctor);

        $doctor->removeSpecialty($this);

        return $this;
    }
}
