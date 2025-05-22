<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Entity\Client;
use App\Entity\Vehicule;


class SecurityController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);

            // Vérifie les champs obligatoires
            if (empty($data['email']) || empty($data['password'])) {
                return $this->json(['error' => 'Champs obligatoires manquants.'], 400);
            }

            // Vérifie si l'utilisateur existe déjà
            if ($userRepository->findOneBy(['email' => $data['email']])) {
                return $this->json(['error' => 'Cet email est déjà utilisé.'], 409);
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword(
                $passwordHasher->hashPassword($user, $data['password'])
            );

            // Création du client lié à l'utilisateur
            $client = new Client();
            $client->setCivilTitle($data['civil_title'] ?? '');
            $client->setLastName($data['last_name'] ?? '');
            $client->setFirstName($data['first_name'] ?? '');
            $client->setAddress($data['address'] ?? '');
            $client->setZipCode($data['zip_code'] ?? '');
            $client->setPhone($data['telephone'] ?? '');
            $em->persist($client);

            $user->setClient($client);

            // Création du véhicule si les infos sont présentes
            if (!empty($data['vehicule'])) {
                $vehiculeData = $data['vehicule'];
                $vehicule = new Vehicule();
                $vehicule->setBrand($vehiculeData['brand'] ?? '');
                $vehicule->setModel($vehiculeData['model'] ?? '');
                $vehicule->setRegistration($vehiculeData['registration'] ?? '');
                $vehicule->setVin($vehiculeData['vin'] ?? '');
                if (!empty($vehiculeData['circulation_date'])) {
                    $vehicule->setCirculationDate(new \DateTime($vehiculeData['circulation_date']));
                }
                $vehicule->setMileage($vehiculeData['mileage'] ?? 0);

                // Gestion conducteur différent
                if (!empty($vehiculeData['driver']) && $vehiculeData['driver'] === true) {
                    $vehicule->setDriver(true);
                    $vehicule->setDriverLastName($vehiculeData['driver_last_name'] ?? null);
                    $vehicule->setDriverFirstName($vehiculeData['driver_first_name'] ?? null);
                    $vehicule->setDriverPhone($vehiculeData['driver_phone'] ?? null);
                } else {
                    $vehicule->setDriver(false);
                    $vehicule->setDriverLastName($data['last_name'] ?? null);
                    $vehicule->setDriverFirstName($data['first_name'] ?? null);
                    $vehicule->setDriverPhone($data['telephone'] ?? null);
                }

                $vehicule->setClient($client);
                $em->persist($vehicule);
            }

            $em->persist($user);
            $em->flush();

            // Stocke toutes les infos du user dans la session
            $session = $request->getSession();
            $session->set('user_id', $user->getId());
            $session->set('user_email', $user->getEmail());
            $session->set('user_roles', $user->getRoles());
            $session->set('user_client', $user->getClient() ? $user->getClient()->getId() : null);

            return $this->json([
                'success' => true,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'client' => $user->getClient() ? $user->getClient()->getId() : null,
                ]
            ], 201);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur serveur : ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $JWTManager
    ): JsonResponse {
        try {
            // Récupérer les données envoyées dans la requête (par exemple, email et mot de passe)
            $data = json_decode($request->getContent(), true);
            
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            if (!$email || !$password) {
                return new JsonResponse(['error' => 'Email and password are required'], 400);
            }

            // Trouver l'utilisateur en base de données
            $user = $userRepository->findOneByEmail($email);
            if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
                return new JsonResponse(['error' => 'Invalid credentials'], 401);
            }

            // Générer le JWT
            $token = $JWTManager->create($user);

            // Retourner le token
            return new JsonResponse(['token' => $token]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur serveur : ' . $e->getMessage()
            ], 500);
        }
    }
    

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        // Invalide la session et supprime toutes les données utilisateur
        $session = $request->getSession();
        $session->clear();
        $session->invalidate();

        return $this->json([
            'success' => true,
            'message' => 'Déconnexion réussie, session supprimée.'
        ]);
    }
}