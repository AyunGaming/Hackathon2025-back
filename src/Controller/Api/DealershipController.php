<?php

namespace App\Controller\Api;

use App\Repository\DealershipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DealershipController extends AbstractController
{
    #[Route('/api/dealerships', name: 'api_dealerships_list', methods: ['GET'])]
    public function list(DealershipRepository $dealershipRepository): JsonResponse
    {
        $dealerships = $dealershipRepository->findAll();

        $data = array_map(function ($dealership) {
            return [
                'id' => $dealership->getId(),
                'name' => $dealership->getName(),
                'city' => $dealership->getCity(),
                'address' => $dealership->getAddress(),
                'zip_code' => $dealership->getZipCode(),
                'longitude' => $dealership->getLongitude(),
                'latitude' => $dealership->getLatitude(),
            ];
        }, $dealerships);

        return $this->json($data);
    }
}