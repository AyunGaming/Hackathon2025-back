<?php

namespace App\Service;

use App\Entity\Appointement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

class AppointmentCreator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    public function create(array $relatedEntities, array $data): Appointement
    {
        $this->validateRequiredEntities($relatedEntities);

        $appointment = new Appointement();
        
        $appointment->setDealership($relatedEntities['dealership']);
        $appointment->setVehicule($relatedEntities['vehicle']);
        $appointment->setClient($relatedEntities['client']);
        $appointment->addService($relatedEntities['service']);

        // Définir la date du rendez-vous
        if (!isset($data['preferred_datetime'])) {
            $this->logger->error('Date du rendez-vous manquante dans les données');
            throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                'La date du rendez-vous est requise'
            );
        }

        try {
            $date = new \DateTime($data['preferred_datetime']);
            $appointment->setDate($date);
            $this->logger->info('Date du rendez-vous définie: {date}', [
                'date' => $date->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Format de date invalide: {date}', [
                'date' => $data['preferred_datetime'],
                'error' => $e->getMessage()
            ]);
            throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                'Format de date invalide pour le rendez-vous'
            );
        }

        $this->entityManager->persist($appointment);
        $this->logger->info('Rendez-vous créé avec succès');

        return $appointment;
    }

    private function validateRequiredEntities(array $relatedEntities): void
    {
        $requiredEntities = ['dealership', 'vehicle', 'client', 'service'];
        $missingEntities = array_diff($requiredEntities, array_keys($relatedEntities));
        
        if (!empty($missingEntities)) {
            $this->logger->error('Entités requises manquantes pour le rendez-vous: {missing}', [
                'missing' => implode(', ', $missingEntities)
            ]);
            throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                sprintf('Entités requises manquantes: %s', implode(', ', $missingEntities))
            );
        }

        $this->logger->info('Toutes les entités requises sont présentes', [
            'dealership_id' => $relatedEntities['dealership']->getId(),
            'vehicle_id' => $relatedEntities['vehicle']->getId(),
            'client_id' => $relatedEntities['client']->getId(),
            'service_id' => $relatedEntities['service']->getId()
        ]);
    }
} 