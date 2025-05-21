<?php

namespace App\Service;

/**
 * Configuration des styles pour la génération de PDF
 */
class PdfStyleConfig
{
    public const PRIMARY_COLOR = [0, 51, 102];    // Bleu foncé
    public const SECONDARY_COLOR = [128, 128, 128]; // Gris
    public const HEADER_HEIGHT = 30;              // Hauteur de l'en-tête en mm
    public const MARGIN = 15;                     // Marge de la page en mm
    public const INNER_PADDING = 5;               // Padding intérieur en mm
    public const PAGE_FORMAT = 'A4';              // Format de la page
    public const COLUMN_GAP = 20;                 // Espacement entre les colonnes en mm
    public const DEFAULT_FONT = 'helvetica';
    public const DEFAULT_FONT_SIZE = 10;
}