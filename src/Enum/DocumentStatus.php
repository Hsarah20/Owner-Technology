<?php

namespace App\Enum;

enum DocumentStatus: string
{
    case RECEIVED = 'received';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::RECEIVED => 'Reçu',
            self::PENDING => 'En attente d\'analyse',
            self::PROCESSING => 'Analyse en cours',
            self::COMPLETED => 'Analyse terminée',
            self::FAILED => 'Analyse en échec',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::RECEIVED => 'gray',
            self::PENDING => 'blue',
            self::PROCESSING => 'orange',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
        };
    }
}