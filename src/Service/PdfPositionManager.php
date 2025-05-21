<?php

namespace App\Service;

use TCPDF;

/**
 * Gestionnaire de positionnement pour le PDF
 */
class PdfPositionManager
{
    private TCPDF $pdf;
    private float $columnWidth;
    private float $rightX;

    public function __construct(TCPDF $pdf)
    {
        $this->pdf = $pdf;
        $this->calculateColumnDimensions();
    }

    /**
     * Calcule les dimensions des colonnes
     */
    private function calculateColumnDimensions(): void
    {
        $pageWidth = $this->pdf->getPageWidth() - (2 * PdfStyleConfig::MARGIN);
        $this->columnWidth = ($pageWidth - PdfStyleConfig::COLUMN_GAP) / 2;
        $this->rightX = PdfStyleConfig::MARGIN + $this->columnWidth + PdfStyleConfig::COLUMN_GAP;
    }

    /**
     * Positionne le curseur au début de la colonne droite
     */
    public function setRightColumnPosition(?float $y = null): void
    {
        if ($y !== null) {
            $this->pdf->SetY($y);
        }
        $this->pdf->SetX($this->getRightColumnX());
    }

    /**
     * Positionne le curseur au début de la colonne gauche
     */
    public function setLeftColumnPosition(?float $y = null): void
    {
        if ($y !== null) {
            $this->pdf->SetY($y);
        }
        $this->pdf->SetX($this->getLeftColumnX());
    }

    /**
     * Retourne la position X de la colonne gauche
     */
    public function getLeftColumnX(): float
    {
        return PdfStyleConfig::MARGIN + PdfStyleConfig::INNER_PADDING;
    }

    /**
     * Retourne la position X de la colonne droite
     */
    public function getRightColumnX(): float
    {
        return $this->rightX + PdfStyleConfig::INNER_PADDING;
    }

    /**
     * Retourne la largeur d'une colonne
     */
    public function getColumnWidth(): float
    {
        return $this->columnWidth - (2 * PdfStyleConfig::INNER_PADDING);
    }

    /**
     * Retourne la position Y de départ du contenu
     */
    public function getContentStartY(): float
    {
        return PdfStyleConfig::HEADER_HEIGHT + 15 + PdfStyleConfig::INNER_PADDING;
    }
} 