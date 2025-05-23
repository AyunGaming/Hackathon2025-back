<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\User;
use App\Entity\Vehicule;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EntityValidator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('@monolog.logger.entity')]
        private readonly LoggerInterface $logger
    ) {}

    public function isValidEntityData(array $item): bool
    {
        // Liste des champs à ignorer car ils viennent de l'utilisateur connecté
        $ignoredFields = ['full_name', 'address', 'phone'];

        // Ignorer les données qui ne contiennent que des champs à ignorer
        $hasValidFields = false;
        foreach ($item as $key => $value) {
            if (!in_array($key, $ignoredFields)) {
                $hasValidFields = true;
                break;
            }
        }

        if (!$hasValidFields) {
            $this->logger->debug('Données ignorées car ne contiennent que des champs utilisateur: {data}', ['data' => $item]);
            return false;
        }

        return true;
    }

    public function validateUser(int $userId): Client
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Utilisateur non trouvé');
        }

        $client = $user->getClient();
        if (!$client) {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Client non trouvé pour cet utilisateur');
        }

        return $client;
    }

    public function validateVehicle(Client $client, string $immatriculation): Vehicule
    {
        $vehicles = $client->getVehicules();
        foreach ($vehicles as $vehicle) {
            if ($vehicle->getRegistration() === $immatriculation) {
                return $vehicle;
            }
        }

        throw new HttpException(
            Response::HTTP_NOT_FOUND,
            sprintf('Véhicule avec immatriculation %s non trouvé pour ce client', $immatriculation)
        );
    }

    public function validateExistingEntity(string $entityType, array $criteria): object
    {
        $entityClass = match($entityType) {
            'dealership' => 'App\\Entity\\Dealership',
            'service' => 'App\\Entity\\Service',
            default => throw new \InvalidArgumentException("Type d'entité non supporté: $entityType")
        };

        $entity = $this->entityManager->getRepository($entityClass)->findOneBy($criteria);
        if (!$entity) {
            throw new HttpException(
                Response::HTTP_NOT_FOUND,
                sprintf('Entité %s non trouvée avec les critères: %s', $entityType, json_encode($criteria))
            );
        }

        return $entity;
    }
} 