<?php

namespace App\Controller;

use App\Entity\Appointement;
use App\Service\AppointmentReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/appointments')]
class AppointmentReportController extends AbstractController
{
    public function __construct(
        private readonly AppointmentReportService $reportService
    ) {
    }

    #[Route('/{id}/report', name: 'appointment_report', methods: ['GET'])]
    public function generateReport(Appointement $appointment): Response
    {
        try {
            $pdfContent = $this->reportService->generateReport($appointment);
            
            return new Response(
                $pdfContent,
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="compte_rendu.pdf"'
                ]
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Erreur lors de la génération du rapport: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
} 