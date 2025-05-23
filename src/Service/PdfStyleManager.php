<?php

namespace App\Service;

use TCPDF;

/**
 * Gestionnaire de styles pour le PDF
 */
class PdfStyleManager
{
    private TCPDF $pdf;

    public function __construct(TCPDF $pdf)
    {
        $this->pdf = $pdf;
    }

    /**
     * Configure le style du texte
     */
    public function setStyle(string $style = '', int $size = PdfStyleConfig::DEFAULT_FONT_SIZE, ?array $color = null): void
    {
        $this->pdf->SetFont(PdfStyleConfig::DEFAULT_FONT, $style, $size);
        if ($color) {
            $this->pdf->SetTextColor(...$color);
        }
    }

    /**
     * Réinitialise le style aux valeurs par défaut
     */
    public function resetStyle(): void
    {
        $this->setStyle();
        $this->pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Configure le style pour un titre de section
     */
    public function setSectionTitleStyle(): void
    {
        $this->setStyle('B', 11, PdfStyleConfig::PRIMARY_COLOR);
    }

    /**
     * Configure le style pour le contenu d'une section
     */
    public function setSectionContentStyle(): void
    {
        $this->setStyle('', PdfStyleConfig::DEFAULT_FONT_SIZE, PdfStyleConfig::TEXT_COLOR);
    }

    /**
     * Configure le style pour l'en-tête
     */
    public function setHeaderStyle(): void
    {
        $this->setStyle('B', 16, [255, 255, 255]);
    }

    /**
     * Configure le style pour le pied de page
     */
    public function setFooterStyle(): void
    {
        $this->setStyle('I', 8, PdfStyleConfig::SECONDARY_COLOR);
    }
} 