<?php

namespace App\Entity;

use App\Repository\DoctorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctorRepository::class)]
class Doctor
{
    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Column(length: 100)]
    private string $firstName;

    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Column(length: 100)]
    private string $lastName;

    #[Groups(['doctor:list'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bio = null;

    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Column]
    private bool $acceptsInsurance;

    #[Groups(['doctor:list'])]
    #[ORM\Column]
    private bool $isActive;

    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\ManyToOne(inversedBy: 'doctors')]
    #[ORM\JoinColumn(nullable: false)]
    private Clinic $clinic;

    /**
     * @var Collection<int, Specialty>
     */
    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\ManyToMany(targetEntity: Specialty::class, inversedBy: 'doctors')]
    private Collection $specialties;

    /**
     * @var Collection<int, TimeSlot>
     */
    #[ORM\OneToMany(
        targetEntity: TimeSlot::class,
        mappedBy: 'doctor',
        orphanRemoval: true
    )]
    private Collection $timeSlots;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'doctor')]
    private Collection $reviews;

    public function __construct()
    {
        $this->acceptsInsurance = false;
        $this->isActive = true;

        $this->specialties = new ArrayCollection();
        $this->timeSlots = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function acceptsInsurance(): bool
    {
        return $this->acceptsInsurance;
    }

    public function setAcceptsInsurance(bool $acceptsInsurance): static
    {
        $this->acceptsInsurance = $acceptsInsurance;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getClinic(): Clinic
    {
        return $this->clinic;
    }

    public function setClinic(Clinic $clinic): static
    {
        $this->clinic = $clinic;

        return $this;
    }

    /**
     * @return Collection<int, Specialty>
     */
    public function getSpecialties(): Collection
    {
        return $this->specialties;
    }

    public function addSpecialty(Specialty $specialty): static
    {
        if (!$this->specialties->contains($specialty)) {
            $this->specialties->add($specialty);
        }

        return $this;
    }

    public function removeSpecialty(Specialty $specialty): static
    {
        $this->specialties->removeElement($specialty);

        return $this;
    }

    /**
     * @return Collection<int, TimeSlot>
     */
    public function getTimeSlots(): Collection
    {
        return $this->timeSlots;
    }

    public function addTimeSlot(TimeSlot $timeSlot): static
    {
        if (!$this->timeSlots->contains($timeSlot)) {
            $this->timeSlots->add($timeSlot);
            $timeSlot->setDoctor($this);
        }

        return $this;
    }

    public function removeTimeSlot(TimeSlot $timeSlot): static
    {
        $this->timeSlots->removeElement($timeSlot);

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setDoctor($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getDoctor() === $this) {
                $review->setDoctor(null);
            }
        }

        return $this;
    }
}
