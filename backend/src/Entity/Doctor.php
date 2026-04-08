<?php

namespace App\Entity;

use App\Repository\DoctorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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

    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bio = null;

    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Column]
    private bool $acceptsInsurance;

    #[Groups(['doctor:list'])]
    #[ORM\Column]
    private bool $isActive;

    /** Work days as ISO weekday numbers: 1=Mon … 7=Sun */
    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Column(type: Types::JSON)]
    private array $workDays = [1, 2, 3, 4, 5];

    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $startHour = 9;

    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $endHour = 17;

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

    /**
     * @var Collection<int, DoctorService>
     */
    #[ORM\OneToMany(targetEntity: DoctorService::class, mappedBy: 'doctor', orphanRemoval: true)]
    private Collection $doctorServices;

    #[Groups(['doctor:list', 'doctor:detail'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarPath = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, unique: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /**
     * @var Collection<int, DoctorUnavailability>
     */
    #[ORM\OneToMany(
        targetEntity: DoctorUnavailability::class,
        mappedBy: 'doctor',
        cascade: ['remove'],
        orphanRemoval: true
    )]
    private Collection $unavailabilities;

    public function __construct()
    {
        $this->acceptsInsurance = false;
        $this->isActive = true;

        $this->specialties = new ArrayCollection();
        $this->timeSlots = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->doctorServices = new ArrayCollection();
        $this->unavailabilities = new ArrayCollection();
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
            $doctorService->setDoctor($this);
        }

        return $this;
    }

    public function removeDoctorService(DoctorService $doctorService): static
    {
        $this->doctorServices->removeElement($doctorService);

        return $this;
    }

    /**
     * @return Collection<int, DoctorUnavailability>
     */
    public function getUnavailabilities(): Collection
    {
        return $this->unavailabilities;
    }

    public function getWorkDays(): array
    {
        return $this->workDays;
    }

    public function setWorkDays(array $workDays): static
    {
        $this->workDays = $workDays;

        return $this;
    }

    public function getStartHour(): int
    {
        return $this->startHour;
    }

    public function setStartHour(int $startHour): static
    {
        $this->startHour = $startHour;

        return $this;
    }

    public function getEndHour(): int
    {
        return $this->endHour;
    }

    public function setEndHour(int $endHour): static
    {
        $this->endHour = $endHour;

        return $this;
    }

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function setAvatarPath(?string $avatarPath): static
    {
        $this->avatarPath = $avatarPath;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    #[Groups(['doctor:list'])]
    public function getAverageRating(): ?float
    {
        if ($this->reviews->isEmpty()) {
            return null;
        }

        $sum = 0.0;
        foreach ($this->reviews as $review) {
            $sum += $review->getRating();
        }

        return round($sum / $this->reviews->count(), 1);
    }

    #[Groups(['doctor:list'])]
    public function getReviewCount(): int
    {
        return $this->reviews->count();
    }

    #[Groups(['doctor:list'])]
    public function getStartingPrice(): ?string
    {
        if ($this->doctorServices->isEmpty()) {
            return null;
        }

        $min = null;
        foreach ($this->doctorServices as $ds) {
            $price = $ds->getPrice();
            if ($min === null || bccomp($price, $min, 2) < 0) {
                $min = $price;
            }
        }

        return $min;
    }
}
