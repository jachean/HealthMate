<?php

namespace App\DataFixtures;

use App\Entity\Doctor;
use App\Entity\DoctorService;
use App\Entity\MedicalService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

final class DoctorServiceFixture extends Fixture implements DependentFixtureInterface
{
    /**
     * Base prices (RON) and durations (minutes) per service slug.
     * Actual doctor prices will vary ±30% around the base.
     */
    private const SERVICE_DEFAULTS = [
        // General
        'consultation'               => ['price' => 250, 'duration' => 30],
        'follow-up'                  => ['price' => 150, 'duration' => 20],

        // Cardiology
        'ecg'                        => ['price' => 150, 'duration' => 20],
        'echocardiography'           => ['price' => 350, 'duration' => 40],
        'holter-ecg-24h'             => ['price' => 400, 'duration' => 30],
        'blood-pressure-monitoring-24h' => ['price' => 300, 'duration' => 30],

        // Dermatology
        'dermatoscopy'               => ['price' => 200, 'duration' => 20],
        'cryotherapy'                => ['price' => 180, 'duration' => 15],
        'skin-biopsy'                => ['price' => 350, 'duration' => 30],

        // Dentistry
        'dental-scaling'             => ['price' => 200, 'duration' => 45],
        'teeth-whitening'            => ['price' => 800, 'duration' => 60],
        'dental-filling'             => ['price' => 250, 'duration' => 30],
        'tooth-extraction'           => ['price' => 200, 'duration' => 30],
        'root-canal-treatment'       => ['price' => 600, 'duration' => 60],
        'dental-crown'               => ['price' => 1200, 'duration' => 60],

        // Ophthalmology
        'fundus-examination'         => ['price' => 180, 'duration' => 20],
        'tonometry'                  => ['price' => 100, 'duration' => 15],
        'perimetry'                  => ['price' => 200, 'duration' => 30],

        // ENT
        'audiometry'                 => ['price' => 150, 'duration' => 20],
        'nasal-endoscopy'            => ['price' => 300, 'duration' => 30],

        // Gastroenterology
        'upper-endoscopy'            => ['price' => 500, 'duration' => 45],
        'colonoscopy'                => ['price' => 700, 'duration' => 60],
        'abdominal-ultrasound'       => ['price' => 250, 'duration' => 30],

        // Orthopedics
        'joint-injection'            => ['price' => 300, 'duration' => 20],

        // Gynecology
        'pelvic-ultrasound'          => ['price' => 250, 'duration' => 30],
        'pap-smear'                  => ['price' => 200, 'duration' => 20],

        // Urology
        'renal-ultrasound'           => ['price' => 250, 'duration' => 30],
        'uroflowmetry'               => ['price' => 200, 'duration' => 20],

        // Neurology
        'eeg'                        => ['price' => 300, 'duration' => 40],
        'electromyography'           => ['price' => 400, 'duration' => 45],

        // Endocrinology
        'thyroid-ultrasound'         => ['price' => 200, 'duration' => 20],

        // Pulmonology
        'spirometry'                 => ['price' => 150, 'duration' => 20],

        // Pediatrics
        'pediatric-consultation'     => ['price' => 200, 'duration' => 30],
        'vaccination'                => ['price' => 100, 'duration' => 15],
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('ro_RO');

        // Build a lookup: specialty slug => array of medical service slugs
        $servicesBySpecialty = [];
        $generalServiceSlugs = [];

        foreach (MedicalServiceFixture::SERVICES as $name => $specialtySlug) {
            $serviceSlug = strtolower(str_replace(' ', '-', $name));

            if ($specialtySlug === null) {
                $generalServiceSlugs[] = $serviceSlug;
            } else {
                $servicesBySpecialty[$specialtySlug][] = $serviceSlug;
            }
        }

        // For each doctor, assign services based on their specialties
        $doctorRepo = $manager->getRepository(Doctor::class);
        $doctors = $doctorRepo->findAll();

        foreach ($doctors as $doctor) {
            // Every doctor gets the general services (Consultation, Follow-up)
            foreach ($generalServiceSlugs as $slug) {
                $this->createDoctorService($manager, $faker, $doctor, $slug);
            }

            // Add specialty-specific services
            foreach ($doctor->getSpecialties() as $specialty) {
                $specSlug = $specialty->getSlug();

                if (!isset($servicesBySpecialty[$specSlug])) {
                    continue;
                }

                foreach ($servicesBySpecialty[$specSlug] as $serviceSlug) {
                    $this->createDoctorService($manager, $faker, $doctor, $serviceSlug);
                }
            }
        }

        $manager->flush();
    }

    private function createDoctorService(
        ObjectManager $manager,
        \Faker\Generator $faker,
        Doctor $doctor,
        string $serviceSlug
    ): void {
        $defaults = self::SERVICE_DEFAULTS[$serviceSlug] ?? ['price' => 200, 'duration' => 30];

        // Vary price ±30% around base, rounded to nearest 10
        $minPrice = (int) ($defaults['price'] * 0.7);
        $maxPrice = (int) ($defaults['price'] * 1.3);
        $price = round($faker->numberBetween($minPrice, $maxPrice) / 10) * 10;

        $medicalService = $this->getReference('medical_service_' . $serviceSlug, MedicalService::class);

        $ds = new DoctorService();
        $ds->setDoctor($doctor);
        $ds->setMedicalService($medicalService);
        $ds->setPrice((string) $price);
        $ds->setDurationMinutes($defaults['duration']);

        $manager->persist($ds);
    }

    public function getDependencies(): array
    {
        return [
            DoctorFixture::class,
            MedicalServiceFixture::class,
        ];
    }
}
