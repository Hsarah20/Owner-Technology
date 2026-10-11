<?php

namespace App\Enum;

enum TimelineEventType: string
{
    case UPLOADED = 'uploaded';
    case ANALYSIS_STARTED = 'analysis_started';
    case ANALYSIS_COMPLETED = 'analysis_completed';
    case ANALYSIS_FAILED = 'analysis_failed';
    case RETRY_REQUESTED = 'retry_requested';

    public function label(): string
    {
        return match ($this) {
            self::UPLOADED => 'Dépôt du fichier',
            self::ANALYSIS_STARTED => 'Analyse démarrée',
            self::ANALYSIS_COMPLETED => 'Analyse réussie',
            self::ANALYSIS_FAILED => 'Analyse en échec',
            self::RETRY_REQUESTED => 'Relance demandée',
        };
    }
}