<?php

namespace App\DataFixtures;

use App\Entity\Specialty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class SpecialtyFixture extends Fixture
{
    public const SPECIALTIES = [
        'Cardiology',
        'Dermatology',
        'Neurology',
        'Orthopedics',
        'Pediatrics',
        'Psychiatry',
        'Ophthalmology',
        'ENT',
        'General Medicine',
        'Endocrinology',
        'Gastroenterology',
        'Urology',
        'Gynecology',
        'Pulmonology',
        'Rheumatology',
        'Oncology',
        'Nephrology',
        'Infectious Diseases',
        'Allergy and Immunology',
        'Diabetology',
        'Sports Medicine',
        'Geriatrics',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::SPECIALTIES as $name) {
            $specialty = new Specialty();
            $specialty->setName($name);
            $specialty->setSlug(strtolower(str_replace(' ', '-', $name)));

            $manager->persist($specialty);
            $this->addReference('specialty_' . $specialty->getSlug(), $specialty);
        }

        $manager->flush();
    }
}
