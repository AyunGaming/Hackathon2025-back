<?php

namespace App\Service;

class EntityFieldMapper
{
    private const FIELD_MAPPINGS = [
        'dealership' => [
            'dealership_name' => 'name',
            'city' => 'city',
            'address' => 'address',
            'zipcode' => 'zipcode',
            'latitude' => 'latitude',
            'longitude' => 'longitude'
        ],
        'vehicle' => [
            'brand' => 'brand',
            'model' => 'model',
            'year' => 'year',
            'price' => 'price'
        ],
        'service' => [
            'operation_name' => 'name',
            'category' => 'category',
            'additionnal_help' => 'help',
            'additionnal_comment' => 'comment',
            'time_unit' => 'time',
            'price' => 'price'
        ],
        'client' => [
            'full_name' => 'name',
            'address' => 'address',
            'phone' => 'phone'
        ]
    ];

    public function getMappingForType(string $entityType): array
    {
        return self::FIELD_MAPPINGS[$entityType] ?? [];
    }

    public function getTargetEntityClass(string $entityType): string
    {
        return match($entityType) {
            'dealership' => 'App\\Entity\\Dealership',
            'vehicle' => 'App\\Entity\\Vehicle',
            'service' => 'App\\Entity\\Service',
            'appointment' => 'App\\Entity\\Appointement',
            default => throw new \InvalidArgumentException("Type d'entité non supporté: $entityType")
        };
    }
} 