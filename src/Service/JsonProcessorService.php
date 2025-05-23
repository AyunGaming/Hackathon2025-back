<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Entity\Appointement;

class JsonProcessorService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityTypeDeterminer $typeDeterminer,
        private readonly EntityFieldMapper $fieldMapper,
        private readonly EntityValidator $validator,
        private readonly AppointmentCreator $appointmentCreator,
        #[Autowire('@monolog.logger.entity')]
        private readonly LoggerInterface $logger
    ) {}

    public function processJsonFromUrl(string $url): array
    {
        try {
            $this->logger->info('Début du traitement JSON depuis l\'URL: {url}', ['url' => $url]);
            
            $response = $this->httpClient->request('GET', $url);
            
            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new HttpException(
                    $response->getStatusCode(),
                    'Erreur lors de la récupération du fichier JSON'
                );
            }

            $data = json_decode($response->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new HttpException(
                    Response::HTTP_BAD_REQUEST,
                    'Le contenu n\'est pas un JSON valide: ' . json_last_error_msg()
                );
            }

            return $data;
        } catch (HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Erreur lors du traitement du JSON: ' . $e->getMessage()
            );
        }
    }

    public function processAndSaveData(array $data, string $entityClass, array $fieldMapping, array $options = []): array
    {
        $this->logger->info('Début du traitement des données JSON', ['data' => $data]);
        
        if (!isset($options['user_id'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'L\'ID de l\'utilisateur est requis');
        }

        try {
            $this->entityManager->beginTransaction();
            
            // Filtrer les données valides - s'assurer que nous ne traitons que des tableaux
            $validData = array_filter($data, function($item) {
                return is_array($item) && $this->validator->isValidEntityData($item);
            });
            
            if (empty($validData)) {
                throw new HttpException(
                    Response::HTTP_BAD_REQUEST,
                    'Aucune donnée valide trouvée dans le JSON'
                );
            }
            
            // Récupérer le client via l'utilisateur
            $client = $this->validator->validateUser($options['user_id']);
            $relatedEntities['client'] = $client;

            // Récupérer le véhicule
            if (!isset($data['car_immatriculation'])) {
                throw new HttpException(Response::HTTP_BAD_REQUEST, 'L\'immatriculation du véhicule est requise');
            }
            $relatedEntities['vehicle'] = $this->validator->validateVehicle($client, $data['car_immatriculation']);

            // Traiter les autres entités
            foreach ($validData as $item) {
                $entityType = $this->typeDeterminer->determineType($item);
                if (!$entityType || $entityType === 'client') {
                    continue;
                }

                $criteria = $this->getSearchCriteria($entityType, $item);
                $relatedEntities[$entityType] = $this->validator->validateExistingEntity($entityType, $criteria);
            }

            // Vérifier si un rendez-vous en attente existe déjà
            $existingAppointment = $this->entityManager->getRepository(Appointement::class)->findOneBy([
                'client' => $client,
                'vehicule' => $relatedEntities['vehicle'],
                'status' => Appointement::STATUS_PENDING
            ]);

            if ($existingAppointment) {
                $this->logger->info('Rendez-vous en attente trouvé, ajout du service', [
                    'appointment_id' => $existingAppointment->getId()
                ]);
                $existingAppointment->addService($relatedEntities['service']);
                $this->entityManager->persist($existingAppointment);
                $appointment = $existingAppointment;
            } else {
                $this->logger->info('Création d\'un nouveau rendez-vous');
                $appointment = $this->appointmentCreator->create($relatedEntities, $data);
            }
            
            $this->entityManager->flush();
            $this->entityManager->commit();

            return [$appointment];
        } catch (HttpException $e) {
            $this->rollbackTransaction();
            throw $e;
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw new HttpException(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Erreur lors de la sauvegarde des données: ' . $e->getMessage()
            );
        }
    }

    private function getSearchCriteria(string $entityType, array $item): array
    {
        return match($entityType) {
            'dealership' => ['name' => $item['dealership_name']],
            'service' => ['name' => $item['operation_name']],
            default => throw new \InvalidArgumentException("Type d'entité non supporté: $entityType")
        };
    }

    private function rollbackTransaction(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
    }
}