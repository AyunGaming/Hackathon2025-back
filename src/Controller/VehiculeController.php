<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Repository\VehiculeRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/vehicules')]
class VehiculeController extends AbstractController
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    #[Route('/user/list', name: 'vehicule_user', methods: ['GET'])]
    public function getAllVehicules(VehiculeRepository $vehiculeRepository, ClientRepository $clientRepository): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();

        // Vérifie que le client existe
        $client = $user->getClient();
        if (!$client) {
            return $this->json(['error' => 'Client non trouvé'], 404);
        }

        // Récupère les véhicules du client
        $vehicules = $vehiculeRepository->findBy(['client' => $client->getId()]);

        $data = [];
        foreach ($vehicules as $vehicule) {
            $data[] = $this->serializeVehicule($vehicule);
        }

        return $this->json($data);
    }

    #[Route('/{id}', name: 'vehicule_show', methods: ['GET'])]
    public function show(Vehicule $vehicule): JsonResponse
    {
        return $this->json($this->serializeVehicule($vehicule));
    }

    #[Route('', name: 'vehicule_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ClientRepository $clientRepository, \App\Repository\UserRepository $userRepository): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $client = $user->getClient();
        if (!$client) {
            return $this->json(['error' => 'Client introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        $vehicule = new Vehicule();
        $vehicule->setClient($client)
            ->setBrand($data['brand'] ?? '')
            ->setModel($data['model'] ?? '')
            ->setRegistration($data['registration'] ?? '')
            ->setVin($data['vin'] ?? '')
            ->setCirculationDate(new \DateTime($data['circulation_date'] ?? 'now'))
            ->setMileage($data['mileage'] ?? 0)
            ->setDriver($data['driver'] ?? false)
            ->setDriverLastName($data['driver_last_name'] ?? null)
            ->setDriverFirstName($data['driver_first_name'] ?? null)
            ->setDriverPhone($data['driver_phone'] ?? null);

        $em->persist($vehicule);
        $em->flush();

        return $this->json($this->serializeVehicule($vehicule), 201);
    }

    #[Route('/{id}', name: 'vehicule_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Vehicule $vehicule, EntityManagerInterface $em): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();
        
        $data = json_decode($request->getContent(), true);

        if (isset($data['brand'])) $vehicule->setBrand($data['brand']);
        if (isset($data['model'])) $vehicule->setModel($data['model']);
        if (isset($data['registration'])) $vehicule->setRegistration($data['registration']);
        if (isset($data['vin'])) $vehicule->setVin($data['vin']);
        if (isset($data['circulation_date'])) $vehicule->setCirculationDate(new \DateTime($data['circulation_date']));
        if (isset($data['mileage'])) $vehicule->setMileage($data['mileage']);
        if (isset($data['driver'])) $vehicule->setDriver($data['driver']);
        if (isset($data['driver_last_name'])) $vehicule->setDriverLastName($data['driver_last_name']);
        if (isset($data['driver_first_name'])) $vehicule->setDriverFirstName($data['driver_first_name']);
        if (isset($data['driver_phone'])) $vehicule->setDriverPhone($data['driver_phone']);

        $em->flush();

        return $this->json($this->serializeVehicule($vehicule));
    }

    #[Route('/{id}', name: 'vehicule_delete', methods: ['DELETE'])]
    public function delete(Vehicule $vehicule, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($vehicule);
        $em->flush();

        return $this->json(['message' => 'Véhicule supprimé']);
    }

    private function serializeVehicule(Vehicule $vehicule): array
    {
        return [
            'id' => $vehicule->getId(),
            'brand' => $vehicule->getBrand(),
            'model' => $vehicule->getModel(),
            'registration' => $vehicule->getRegistration(),
            'vin' => $vehicule->getVin(),
            'circulation_date' => $vehicule->getCirculationDate()?->format('Y-m-d'),
            'mileage' => $vehicule->getMileage(),
            'driver' => $vehicule->isDriver(),
            'driver_last_name' => $vehicule->getDriverLastName(),
            'driver_first_name' => $vehicule->getDriverFirstName(),
            'driver_phone' => $vehicule->getDriverPhone(),
            'client_id' => $vehicule->getClient()?->getId(),
        ];
    }
}