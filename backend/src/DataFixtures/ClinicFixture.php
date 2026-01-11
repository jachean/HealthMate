<?php

namespace App\DataFixtures;

use App\Entity\Clinic;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class ClinicFixture extends Fixture
{
    public const CLINIC_COUNT = 20;

    private const CLINICS = [
        ['Clinica Medicală Centralis', 'Craiova', 'Str. Alexandru Ioan Cuza, nr. 14'],
        ['Clinica Medicală Santerra', 'Craiova', 'Calea București, nr. 56'],
        ['Clinica Medicală NovaCare', 'Craiova', 'Str. Brestei, nr. 98'],
        ['Clinica Medicală MedAxis', 'Craiova', 'Str. Amaradia, nr. 32'],
        ['Clinica Medicală Vitalis', 'Craiova', 'Str. Râului, nr. 7'],
        ['Clinica Medicală PrimeCare', 'Craiova', 'Bd. Carol I, nr. 45'],
        ['Clinica Medicală Arcadia', 'Craiova', 'Str. Calea Severinului, nr. 12'],
        ['Clinica Medicală Helios', 'Craiova', 'Str. Traian, nr. 88'],
        ['Clinica Medicală MedNova', 'Craiova', 'Str. Popa Șapcă, nr. 19'],
        ['Clinica Medicală SanMed', 'Craiova', 'Bd. Decebal, nr. 101'],

        // Other cities (10)
        ['Clinica Medicală MedLifeX', 'București', 'Bd. Unirii, nr. 22'],
        ['Clinica Medicală Regina', 'București', 'Șos. Mihai Bravu, nr. 120'],
        ['Clinica Medicală TransMed', 'Cluj-Napoca', 'Str. Memorandumului, nr. 10'],
        ['Clinica Medicală Medis', 'Cluj-Napoca', 'Str. Horea, nr. 55'],
        ['Clinica Medicală Sanitas', 'Timișoara', 'Bd. Revoluției 1989, nr. 5'],
        ['Clinica Medicală WestCare', 'Timișoara', 'Str. Circumvalațiunii, nr. 44'],
        ['Clinica Medicală NordMed', 'Iași', 'Bd. Independenței, nr. 16'],
        ['Clinica Medicală EstMed', 'Iași', 'Str. Lăpușneanu, nr. 27'],
        ['Clinica Medicală BlueCross', 'Brașov', 'Str. Republicii, nr. 3'],
        ['Clinica Medicală GreenCare', 'Constanța', 'Bd. Tomis, nr. 89'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::CLINICS as $index => [$name, $city, $address]) {
            $clinic = new Clinic();
            $clinic->setName($name);
            $clinic->setDescription('Clinic medical privat (date demonstrative).');
            $clinic->setCity($city);
            $clinic->setAddress($address);

            $manager->persist($clinic);
            $this->addReference('clinic_' . $index, $clinic);
        }

        $manager->flush();
    }
}
