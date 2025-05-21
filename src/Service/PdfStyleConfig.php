<?php

namespace App\Service;

/**
 * Configuration des styles pour la génération de PDF
 */
class PdfStyleConfig
{
    // Couleurs principales
    public const PRIMARY_COLOR = [25, 97, 159];    // Bleu moyen-foncé pour les titres et accents
    public const SECONDARY_COLOR = [128, 128, 128]; // Gris pour le footer
    public const TEXT_COLOR = [0, 0, 0];           // Noir pour le texte normal
    public const HEADER_HEIGHT = 30;              // Hauteur de l'en-tête en mm
    public const MARGIN = 15;                     // Marge de la page en mm
    public const INNER_PADDING = 5;               // Padding intérieur en mm
    public const PAGE_FORMAT = 'A4';              // Format de la page
    public const COLUMN_GAP = 20;                 // Espacement entre les colonnes en mm
    public const DEFAULT_FONT = 'helvetica';
    public const DEFAULT_FONT_SIZE = 10;
}