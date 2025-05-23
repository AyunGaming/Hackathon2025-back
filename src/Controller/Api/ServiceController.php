<?php

namespace App\Controller\Api;

use App\Repository\ServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ServiceController extends AbstractController
{
    #[Route('/api/services', name: 'api_services_list', methods: ['GET'])]
    public function list(ServiceRepository $serviceRepository): JsonResponse
    {
        $services = $serviceRepository->findAll();

        $data = array_map(function ($service) {
            return [
                'id' => $service->getId(),
                'name' => $service->getName(),
                'category' => $service->getCategory(),
                'help' => $service->getHelp(),
                'commentary' => $service->getCommentary(),
                'time' => $service->getTime(),
                'price' => $service->getPrice(),
            ];
        }, $services);

        return $this->json($data);
    }
}