<?php

namespace App\DataFixtures;

use App\Entity\MedicalService;
use App\Entity\Specialty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class MedicalServiceFixture extends Fixture implements DependentFixtureInterface
{
    /**
     * Map of service name => specialty slug (null = available to all specialties).
     */
    public const SERVICES = [
        // General (available to any specialty)
        'Consultation'              => null,
        'Follow-up'                 => null,

        // Cardiology
        'ECG'                       => 'cardiology',
        'Echocardiography'          => 'cardiology',
        'Holter ECG 24h'            => 'cardiology',
        'Blood Pressure Monitoring 24h' => 'cardiology',

        // Dermatology
        'Dermatoscopy'              => 'dermatology',
        'Cryotherapy'               => 'dermatology',
        'Skin Biopsy'               => 'dermatology',

        // Dentistry
        'Dental Scaling'            => 'dentistry',
        'Teeth Whitening'           => 'dentistry',
        'Dental Filling'            => 'dentistry',
        'Tooth Extraction'          => 'dentistry',
        'Root Canal Treatment'      => 'dentistry',
        'Dental Crown'              => 'dentistry',

        // Ophthalmology
        'Fundus Examination'        => 'ophthalmology',
        'Tonometry'                 => 'ophthalmology',
        'Perimetry'                 => 'ophthalmology',

        // ENT
        'Audiometry'                => 'ent',
        'Nasal Endoscopy'           => 'ent',

        // Gastroenterology
        'Upper Endoscopy'           => 'gastroenterology',
        'Colonoscopy'               => 'gastroenterology',
        'Abdominal Ultrasound'      => 'gastroenterology',

        // Orthopedics
        'Joint Injection'           => 'orthopedics',

        // Gynecology
        'Pelvic Ultrasound'         => 'gynecology',
        'Pap Smear'                 => 'gynecology',

        // Urology
        'Renal Ultrasound'          => 'urology',
        'Uroflowmetry'              => 'urology',

        // Neurology
        'EEG'                       => 'neurology',
        'Electromyography'          => 'neurology',

        // Endocrinology
        'Thyroid Ultrasound'        => 'endocrinology',

        // Pulmonology
        'Spirometry'                => 'pulmonology',

        // Pediatrics
        'Pediatric Consultation'    => 'pediatrics',
        'Vaccination'               => 'pediatrics',
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::SERVICES as $name => $specialtySlug) {
            $service = new MedicalService();
            $service->setName($name);
            $service->setSlug(strtolower(str_replace(' ', '-', $name)));

            if ($specialtySlug !== null) {
                $specialty = $this->getReference('specialty_' . $specialtySlug, Specialty::class);
                $service->setSpecialty($specialty);
            }

            $manager->persist($service);
            $this->addReference('medical_service_' . $service->getSlug(), $service);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SpecialtyFixture::class,
        ];
    }
}
