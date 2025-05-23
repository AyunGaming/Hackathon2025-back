<?php

namespace App\Controller;

use App\Entity\Appointement;
use App\Repository\AppointementRepository;
use App\Repository\UserRepository;
use App\Repository\VehiculeRepository;
use App\Repository\DealershipRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api/appointements')]
class AppointementController extends AbstractController
{
    public function __construct(
        #[Autowire('@monolog.logger.entity')]
        private readonly LoggerInterface $logger,
        private readonly TokenStorageInterface $tokenStorage,
    )
    {
    }

    #[Route('/user/list', name: 'get_all_appointements', methods: ['GET'])]
    public function getAllAppointements(Request $request, AppointementRepository $appointementRepository, UserRepository $userRepository): JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        if(is_null($token)) return $this->json(['error' => 'Pas de token trouvé']);

        $user = $token->getUser();
        $this->logger->info('Token de connexion depuis le controller de rdv: ', ['user' => $user->getEmail()]);

        $client = $user->getClient();

        try{
            $appointements = $appointementRepository->findBy(['client' => $client]);

            // Transforme les entités en tableaux simples
            $data = [];
            foreach ($appointements as $rdv) {
                $data[] = [
                    'id' => $rdv->getId(),
                    'client' => $rdv->getClient() ? $rdv->getClient()->getLastname() . ' ' . $rdv->getClient()->getFirstName() : null,
                    'vehicule' => $rdv->getVehicule() ? $rdv->getVehicule()->getBrand() . ' ' . $rdv->getVehicule()->getModel() : null,
                    'dealership' => $rdv->getDealership()?->getName(),
                    'date' => $rdv->getDate()?->format('Y-m-d H:i:s'),
                ];
            }
        } catch (\Exception $e)
        {
            $this->logger->error('Erreur lors de la récupération des rdv: ',['error' => $e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], 500);
        } finally {
            return $this->json($data);
        }


    }

    #[Route('/{id}', name: 'get_appointement', methods: ['GET'])]
    public function getAppointement(
        int $id,
        Request $request,
        AppointementRepository $appointementRepository
    ): JsonResponse {
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        $appointement = $appointementRepository->find($id);
        if (!$appointement || $appointement->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Appointement introuvable ou non autorisé'], 404);
        }
        return $this->json($appointement);
    }

    #[Route('', name: 'create_appointement', methods: ['POST'])]
    public function createAppointement(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        VehiculeRepository $vehiculeRepository,
        DealershipRepository $dealershipRepository,
        ServiceRepository $serviceRepository
    ): JsonResponse {

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

        // Vérification des champs obligatoires
        if (
            empty($data['vehicule_id']) ||
            empty($data['dealership_id']) ||
            empty($data['service_ids']) ||
            empty($data['date']) ||
            empty($data['time'])
        ) {
            return $this->json(['error' => 'Champs manquants'], 400);
        }

        // Vérification véhicule
        $vehicule = $vehiculeRepository->find($data['vehicule_id']);
        if (!$vehicule || $vehicule->getClient()->getId() !== $client->getId()) {
            return $this->json(['error' => 'Véhicule introuvable ou non autorisé'], 400);
        }

        // Vérification garage
        $dealership = $dealershipRepository->find($data['dealership_id']);
        if (!$dealership) {
            return $this->json(['error' => 'Garage introuvable'], 400);
        }

        // Création de la date/heure
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i', $data['date'] . ' ' . $data['time']);
        if (!$dateTime) {
            return $this->json(['error' => 'Date ou heure invalide'], 400);
        }

        // Création du rendez-vous
        $appointement = new Appointement();
        $appointement->setClient($client);
        $appointement->setVehicule($vehicule);
        $appointement->setDealership($dealership);
        $appointement->setDate($dateTime);

        // Ajout des services
        if (is_array($data['service_ids'])) {
            foreach ($data['service_ids'] as $serviceId) {
                $service = $serviceRepository->find($serviceId);
                if ($service) {
                    $appointement->addService($service);
                }
            }
        }

        $em->persist($appointement);
        $em->flush();

        // Réponse simple (tu peux adapter selon le front)
        return $this->json([
            'success' => true,
            'id' => $appointement->getId(),
            'date' => $appointement->getDate()->format('Y-m-d H:i'),
        ], 201);
    }

    #[Route('/{id}', name: 'update_appointement', methods: ['PUT'])]
    public function updateAppointement(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        AppointementRepository $appointementRepository,
        UserRepository $userRepository
    ): JsonResponse {

        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        $appointement = $appointementRepository->find($id);
        if (!$appointement || $appointement->getClient()->getId() !== $user->getClient()->getId()) {
            return $this->json(['error' => 'Appointement introuvable ou non autorisé'], 404);
        }
        $data = json_decode($request->getContent(), true);
        if (isset($data['date'])) {
            $appointement->setDate(new \DateTime($data['date']));
        }
        if (isset($data['description'])) {
            $appointement->setDescription($data['description']);
        }
        $em->flush();

        return $this->json($appointement);
    }

    #[Route('/{id}', name: 'delete_appointement', methods: ['DELETE'])]
    public function deleteAppointement(
        int $id,
        EntityManagerInterface $em,
        AppointementRepository $appointementRepository,
        Request $request,
        UserRepository $userRepository
    ): JsonResponse {

        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $client = $user->getClient();
        if (!$client) {
            return $this->json(['error' => 'Client introuvable'], 404);
        }
        $appointement = $appointementRepository->find($id);
        if (!$appointement || $appointement->getClient()->getId() !== $client->getId()) {
           return $this->json(['error' => 'Erreur lors de la suppression : '], 500);
        }

        $em->remove($appointement);
        $em->flush();

        return $this->json(['success' => true]);
    }
}