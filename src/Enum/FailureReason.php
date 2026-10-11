<?php

namespace App\Enum;

enum FailureReason: string
{
    case UNREADABLE = 'unreadable';
    case UNSUPPORTED_FORMAT = 'unsupported_format';
    case PROCESSING_INTERRUPTED = 'processing_interrupted';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::UNREADABLE => 'Fichier illisible',
            self::UNSUPPORTED_FORMAT => 'Format non supporté',
            self::PROCESSING_INTERRUPTED => 'Traitement interrompu',
            self::UNKNOWN => 'Cause inconnue',
        };
    }

    public function explanation(): string
    {
        return match ($this) {
            self::UNREADABLE => 'Le fichier n\'a pas pu être ouvert. Il est peut-être corrompu ou protégé.',
            self::UNSUPPORTED_FORMAT => 'Le format de ce fichier n\'est pas pris en charge par notre outil d\'analyse.',
            self::PROCESSING_INTERRUPTED => 'Le traitement a été interrompu avant la fin. Une nouvelle tentative peut être lancée.',
            self::UNKNOWN => 'La cause de l\'échec n\'a pas pu être identifiée.',
        };
    }
}