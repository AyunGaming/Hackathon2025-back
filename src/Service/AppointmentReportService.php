<?php

namespace App\Service;

use App\Entity\Appointement;
use TCPDF;

/**
 * Service de génération de rapports PDF pour les rendez-vous
 * 
 * Ce service permet de générer un rapport PDF détaillé pour un rendez-vous,
 * incluant les informations du client, du véhicule, du garage et des prestations.
 */
class AppointmentReportService
{
    private TCPDF $pdf;
    private PdfStyleManager $styleManager;
    private PdfPositionManager $positionManager;

    /**
     * Génère le rapport PDF pour un rendez-vous
     * 
     * @param Appointement $appointment Le rendez-vous à documenter
     * @return string Le contenu du PDF en format binaire
     */
    public function generateReport(Appointement $appointment): string
    {
        $this->initializePdf($appointment);
        $this->setupPage();
        $this->addHeader();
        $this->addContent($appointment);
        $this->addFooter($appointment);

        return $this->pdf->Output('compte_rendu.pdf', 'S');
    }

    /**
     * Initialise l'instance TCPDF avec les métadonnées du document
     */
    private function initializePdf(Appointement $appointment): void
    {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PdfStyleConfig::PAGE_FORMAT, true, 'UTF-8', false);
        $this->pdf->SetCreator(PDF_CREATOR);
        $this->pdf->SetAuthor($appointment->getDealership()->getName());
        $this->pdf->SetTitle('Compte-rendu de rendez-vous');

        $this->styleManager = new PdfStyleManager($this->pdf);
        $this->positionManager = new PdfPositionManager($this->pdf);
    }

    /**
     * Configure les paramètres de base de la page
     */
    private function setupPage(): void
    {
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(false);
        $this->pdf->SetMargins(PdfStyleConfig::MARGIN, PdfStyleConfig::MARGIN, PdfStyleConfig::MARGIN);
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->AddPage();
        $this->styleManager->resetStyle();
    }

    /**
     * Ajoute le contenu principal du rapport
     */
    private function addContent(Appointement $appointment): void
    {
        $contentStartY = $this->positionManager->getContentStartY();
        $contentWidth = $this->positionManager->getColumnWidth();

        // Colonne gauche - Informations client et véhicule
        $this->positionManager->setLeftColumnPosition($contentStartY);
        $this->addSection('INFORMATIONS CLIENT', $this->formatClientInfo($appointment->getClient()), $contentWidth);
        $this->pdf->Ln(5);
        $this->positionManager->setLeftColumnPosition();
        $this->addSection('INFORMATIONS VÉHICULE', $this->formatVehicleInfo($appointment->getVehicule()), $contentWidth);

        // Colonne droite - Informations garage et date
        $this->positionManager->setRightColumnPosition($contentStartY);
        $this->addSection('INFORMATIONS GARAGE', $this->formatDealershipInfo($appointment->getDealership()), $contentWidth, 'R');
        $this->pdf->Ln(5);
        $this->positionManager->setRightColumnPosition();
        $this->addSection('DATE ET HEURE', [$appointment->getDate()->format('d/m/Y H:i')], $contentWidth, 'R');

        // Tableau des prestations
        $this->pdf->SetY($contentStartY + 70 + 15);
        $this->addServicesTable($appointment);
    }   

    /**
     * Ajoute l'en-tête du document avec le titre
     */
    private function addHeader(): void
    {
        $this->pdf->SetFillColor(...PdfStyleConfig::PRIMARY_COLOR);
        $this->pdf->Rect(0, 0, $this->pdf->getPageWidth(), PdfStyleConfig::HEADER_HEIGHT, 'F');

        $this->styleManager->setHeaderStyle();
        $this->pdf->SetXY(PdfStyleConfig::MARGIN, 8);
        $this->pdf->Cell(0, 15, 'COMPTE-RENDU DE RENDEZ-VOUS', 0, 1, 'L');
        $this->styleManager->resetStyle();
    }

    /**
     * Ajoute une section avec un titre et son contenu
     * 
     * @param string $title Titre de la section
     * @param array $content Liste des lignes de contenu
     * @param float $width Largeur de la section
     * @param string $align Alignement du texte ('L' pour gauche, 'R' pour droite)
     */
    private function addSection(string $title, array $content, float $width, string $align = 'L'): void
    {
        $x = $align === 'R' ? $this->positionManager->getRightColumnX() : $this->positionManager->getLeftColumnX();
        
        $this->styleManager->setSectionTitleStyle();
        $this->pdf->SetX($x);
        $this->pdf->Cell($width, 8, $title, 0, 1, $align);
        
        $this->styleManager->setSectionContentStyle();
        foreach ($content as $line) {
            $this->pdf->SetX($x);
            $this->pdf->Cell($width, 6, $line, 0, 1, $align);
        }
    }

    /**
     * Ajoute le tableau des prestations avec leurs prix
     */
    private function addServicesTable(Appointement $appointment): void
    {
        // Titre de la section
        $this->styleManager->setSectionTitleStyle();
        $this->pdf->Cell(0, 8, 'PRESTATIONS', 0, 1, 'C');

        // Calcul des largeurs
        $tableWidth = $this->pdf->getPageWidth() - (2 * PdfStyleConfig::MARGIN);
        $serviceWidth = $tableWidth * 0.7;
        $priceWidth = $tableWidth * 0.3;

        // En-tête du tableau
        $this->pdf->SetFillColor(...PdfStyleConfig::PRIMARY_COLOR);
        $this->styleManager->setStyle('B', 10, [255, 255, 255]);
        $this->pdf->Cell($serviceWidth, 8, 'Prestation', 1, 0, 'L', true);
        $this->pdf->Cell($priceWidth, 8, 'Prix', 1, 1, 'C', true);

        // Réinitialisation de la couleur du texte pour le contenu
        $this->pdf->SetTextColor(0, 0, 0);
        $this->styleManager->setSectionContentStyle();
        $total = 0;

        $services = $appointment->getService();
        if (empty($services)) {
           throw new \RuntimeException('Aucune prestation trouvée pour le rendez-vous');
        } else {
            foreach ($services as $service) {
                $this->pdf->Cell($serviceWidth, 8, $service->getName(), 1, 0, 'L');
                $this->pdf->Cell($priceWidth, 8, $this->formatPrice($service->getPrice()), 1, 1, 'R');
                $total += $service->getPrice();
            }
        }

        // Ligne du total
        $this->pdf->SetFillColor(240, 240, 240);
        $this->styleManager->setStyle('B', 10);
        $this->pdf->Cell($serviceWidth, 8, 'Total', 1, 0, 'L', true);
        $this->pdf->Cell($priceWidth, 8, $this->formatPrice($total), 1, 1, 'R', true);
    }

    /**
     * Ajoute le pied de page avec la date de génération et le nom du garage
     */
    private function addFooter($appointment): void
    {
        $this->pdf->SetY(-20);
        $this->styleManager->setFooterStyle();
        
        $this->pdf->SetDrawColor(...PdfStyleConfig::SECONDARY_COLOR);
        $this->pdf->Line(PdfStyleConfig::MARGIN, $this->pdf->GetY(), $this->pdf->getPageWidth() - PdfStyleConfig::MARGIN, $this->pdf->GetY());
        $this->pdf->Ln(3);
        
        $this->pdf->Cell(0, 4, 'Document généré automatiquement - ' . date('d/m/Y H:i'), 0, 1, 'C');
        $this->pdf->Cell(0, 4, $appointment->getDealership()->getName() . ' - Tous droits réservés', 0, 1, 'C');
    }

    /**
     * Formate un prix en euros avec 2 décimales
     * 
     * @param int $price Prix en centimes
     * @return string Prix formaté (ex: "123,45 €")
     */
    private function formatPrice(int $price): string
    {
        return number_format($price / 100, 2) . ' €';
    }

    /**
     * Formate les informations du client
     * 
     * @param object $client Entité Client
     * @return array Liste des lignes d'information
     */
    private function formatClientInfo($client): array
    {
        return [
            $client->getCivilTitle() . ' ' . $client->getFirstName() . ' ' . $client->getLastName(),
            $client->getAddress(),
            $client->getZipCode()
        ];
    }

    /**
     * Formate les informations du véhicule
     * 
     * @param object $vehicule Entité Véhicule
     * @return array Liste des lignes d'information
     */
    private function formatVehicleInfo($vehicule): array
    {
        return [
            'Marque: ' . $vehicule->getBrand(),
            'Modèle: ' . $vehicule->getModel(),
            'Immatriculation: ' . $vehicule->getRegistration()
        ];
    }

    /**
     * Formate les informations du garage
     * 
     * @param object $dealership Entité Dealership
     * @return array Liste des lignes d'information
     */
    private function formatDealershipInfo($dealership): array
    {
        return [
            $dealership->getName(),
            $dealership->getAddress(),
            $dealership->getZipCode() . ' ' . $dealership->getCity()
        ];
    }
} 