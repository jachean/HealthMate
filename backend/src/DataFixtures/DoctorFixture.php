<?php

namespace App\DataFixtures;

use App\Entity\Clinic;
use App\Entity\Doctor;
use App\Entity\Specialty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class DoctorFixture extends Fixture implements DependentFixtureInterface
{
    public const DOCTOR_COUNT = 100;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('ro_RO');

        $specialtySlugs = array_map(
            fn ($s) => strtolower(str_replace(' ', '-', $s)),
            SpecialtyFixture::SPECIALTIES
        );

        for ($i = 0; $i < self::DOCTOR_COUNT; $i++) {
            $doctor = new Doctor();

            $doctor->setFirstName($faker->firstName);
            $doctor->setLastName($faker->lastName);
            $doctor->setBio($faker->optional()->paragraph);
            $doctor->setAcceptsInsurance($faker->boolean(70));
            $doctor->setIsActive(true);

            $clinicIndex = $faker->numberBetween(0, ClinicFixture::CLINIC_COUNT - 1);
            $clinic = $this->getReference('clinic_' . $clinicIndex, Clinic::class);
            $doctor->setClinic($clinic);

            shuffle($specialtySlugs);
            foreach (array_slice($specialtySlugs, 0, $faker->numberBetween(1, 4)) as $slug) {
                $specialty = $this->getReference('specialty_' . $slug, Specialty::class);
                $doctor->addSpecialty($specialty);
            }

            $manager->persist($doctor);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SpecialtyFixture::class,
            ClinicFixture::class,
        ];
    }
}
