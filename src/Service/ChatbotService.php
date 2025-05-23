<?php

namespace App\Service;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ChatbotService
{
    private string $chatbotUrl;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly JsonProcessorService $jsonProcessor,
        #[Autowire('@monolog.logger.chatbot')]
        private readonly LoggerInterface $logger,
        private readonly TokenStorageInterface $tokenStorage,
        string $chatbotUrl
    ) {
        $this->chatbotUrl = rtrim($chatbotUrl, '/');
    }

    /**
     * Initialise une nouvelle conversation avec le chatbot
     */
    public function initializeChat(array $userInfo): array
    {
        try {
            $this->logger->info('Initialisation du chat', ['user_info' => $userInfo]);
            
            $response = $this->httpClient->request('GET', $this->chatbotUrl . '/initialize_chat', [
                'query' => $userInfo
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new HttpException(
                    $response->getStatusCode(),
                    'Erreur lors de l\'initialisation du chat: ' . $response->getContent(false)
                );
            }

            $data = $response->toArray();
            $this->logger->info('Chat initialisé avec succès', ['session_id' => $data['session_id'] ?? null]);
            
            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'initialisation du chat: {error}', [
                'error' => $e->getMessage(),
                'user_info' => $userInfo
            ]);
            throw $e;
        }
    }

    /**
     * Envoie un message au chatbot et récupère sa réponse
     */
    public function sendMessage(string $message): array
    {
        try {
            $this->logger->info('Envoi d\'un message', [
                'message' => $message
            ]);

            $response = $this->httpClient->request('POST', $this->chatbotUrl . '/chat', [
                'json' => [
                    'message' => $message
                ]
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new HttpException(
                    $response->getStatusCode(),
                    'Erreur lors de l\'envoi du message: ' . $response->getContent(false)
                );
            }

            $data = $response->toArray();

            // Si le chatbot a terminé et renvoie des données de rendez-vous
            if (isset($data['data'])) {
                $this->logger->info('Traitement des données de rendez-vous', [
                    'appointment_data' => $data['data']
                ]);

                // Récupérer l'utilisateur connecté

                $token = $this->tokenStorage->getToken();
                $user = $token->getUser();
                $this->logger->info('Utilisateur connecté', ['user' => $user, 'email' => $user->getEmail()]);
                if (!$user) {
                    throw new HttpException(
                        Response::HTTP_UNAUTHORIZED,
                        'Utilisateur non connecté'
                    );
                }

                $this->jsonProcessor->processAndSaveData(
                    $data['data'],
                    'App\Entity\Appointement',
                    [
                        'closest_dealer.dealership_name' => 'dealership.name',
                        'closest_dealer.city' => 'dealership.city',
                        'closest_dealer.address' => 'dealership.address',
                        'closest_dealer.zipcode' => 'dealership.zipcode',
                        'closest_dealer.latitude' => 'dealership.latitude',
                        'closest_dealer.longitude' => 'dealership.longitude',
                        'car_model' => 'vehicule.model',
                        'issue_description' => 'vehicule.description',
                        'matched_operation.operation_name' => 'service.name',
                        'matched_operation.category' => 'service.category',
                        'matched_operation.price' => 'service.price',
                        'preferred_datetime' => 'date'
                    ],
                    ['user_id' => $user->getId()]
                );
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du message: {error}', [
                'error' => $e->getMessage(),
                'message' => $message
            ]);
            throw $e;
        }
    }

    /**
     * Termine la conversation avec le chatbot
     */
    public function resetChat(): void
    {
        try {
            $this->logger->info('Réinitialisation du chat');

            $response = $this->httpClient->request('POST', $this->chatbotUrl . '/reset_chat');

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new HttpException(
                    $response->getStatusCode(),
                    'Erreur lors de la réinitialisation du chat: ' . $response->getContent(false)
                );
            }

            $this->logger->info('Chat réinitialisé avec succès');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la réinitialisation du chat: {error}', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 