<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EntityTypeDeterminer
{
    public function __construct(
        #[Autowire('@monolog.logger.entity')]
        private readonly LoggerInterface $logger
    ) {}

    public function determineType(array $item): ?string
    {
        // Ignorer la clé car_immatriculation car elle est traitée séparément
        if (isset($item['car_immatriculation'])) {
            unset($item['car_immatriculation']);
        }

        // Définition des champs caractéristiques pour chaque type d'entité
        $entitySignatures = [
            'dealership' => ['dealership_name', 'city', 'address', 'zipcode', 'latitude', 'longitude'],
            'vehicle' => ['brand', 'model', 'year', 'price'],
            'service' => ['operation_name', 'category', 'time_unit', 'price'],
            'appointment' => ['preferred_datetime']
        ];

        // Vérifier chaque type d'entité
        foreach ($entitySignatures as $type => $signatureFields) {
            $matchCount = 0;
            foreach ($signatureFields as $field) {
                if (isset($item[$field])) {
                    $matchCount++;
                }
            }
            
            // Si plus de 50% des champs caractéristiques sont présents, on considère que c'est ce type d'entité
            if ($matchCount >= count($signatureFields) * 0.7) {
                $this->logger->debug('Type d\'entité déterminé: {type} avec {count} champs correspondants', [
                    'type' => $type,
                    'count' => $matchCount,
                    'fields' => array_keys($item)
                ]);
                return $type;
            }
        }

        $this->logger->warning('Type d\'entité non déterminé pour: {data}', [
            'data' => $item,
            'available_fields' => array_keys($item)
        ]);
        return null;
    }
} 