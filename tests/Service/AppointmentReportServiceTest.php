<?php

namespace App\Tests\Service;

use App\Entity\Appointement;
use App\Entity\Client;
use App\Entity\Dealership;
use App\Entity\Service;
use App\Entity\Vehicule;
use App\Service\AppointmentReportService;
use PHPUnit\Framework\TestCase;

class AppointmentReportServiceTest extends TestCase
{
    private AppointmentReportService $service;

    protected function setUp(): void
    {
        $this->service = new AppointmentReportService();
    }

    public function testGenerateReport(): void
    {
        // Créer un client
        $client = new Client();
        $client->setCivilTitle('M.');
        $client->setFirstName('John');
        $client->setLastName('Doe');
        $client->setAddress('123 rue Example');
        $client->setZipCode('75000');

        // Créer un véhicule
        $vehicule = new Vehicule();
        $vehicule->setBrand('Renault');
        $vehicule->setModel('Clio');
        $vehicule->setRegistration('AB-123-CD');

        // Créer un garage
        $dealership = new Dealership();
        $dealership->setName('Garage Auto');
        $dealership->setAddress('456 rue du Garage');
        $dealership->setCity('Paris');
        $dealership->setZipCode('75001');

        // Créer des services
        $service1 = new Service();
        $service1->setName('Vidange');
        $service1->setPrice(5000); // 50.00 €

        $service2 = new Service();
        $service2->setName('Filtre à air');
        $service2->setPrice(2000); // 20.00 €

        // Créer un rendez-vous
        $appointment = new Appointement();
        $appointment->setClient($client);
        $appointment->setVehicule($vehicule);
        $appointment->setDealership($dealership);
        $appointment->setDate(new \DateTime('2024-03-20 14:30:00'));
        $appointment->addService($service1);
        $appointment->addService($service2);

        // Générer le PDF
        $pdfContent = $this->service->generateReport($appointment);

        // Vérifier que le contenu n'est pas vide
        $this->assertNotEmpty($pdfContent);

        // Vérifier que c'est bien un PDF (commence par %PDF-)
        $this->assertStringStartsWith('%PDF-', $pdfContent);

        // Sauvegarder le PDF pour inspection manuelle
        file_put_contents(__DIR__ . '/../../var/test_report.pdf', $pdfContent);
    }
} 