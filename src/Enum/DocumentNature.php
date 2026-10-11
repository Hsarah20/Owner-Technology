<?php

namespace App\Enum;

enum DocumentNature: string
{
    case BILAN = 'bilan';
    case LIASSE_FISCALE = 'liasse_fiscale';
    case AVIS_IMPOT = 'avis_impot';
    case KBIS = 'kbis';
    case STATUTS = 'statuts';
    case PIECE_IDENTITE = 'piece_identite';
    case JUSTIFICATIF_DOMICILE = 'justificatif_domicile';
    case BULLETIN_SALAIRE = 'bulletin_salaire';
    case CONTRAT_TRAVAIL = 'contrat_travail';
    case DSN = 'dsn';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::BILAN => 'Bilan comptable',
            self::LIASSE_FISCALE => 'Liasse fiscale',
            self::AVIS_IMPOT => 'Avis d\'imposition',
            self::KBIS => 'Extrait Kbis',
            self::STATUTS => 'Statuts de la société',
            self::PIECE_IDENTITE => 'Pièce d\'identité',
            self::JUSTIFICATIF_DOMICILE => 'Justificatif de domicile',
            self::BULLETIN_SALAIRE => 'Bulletin de salaire',
            self::CONTRAT_TRAVAIL => 'Contrat de travail',
            self::DSN => 'Déclaration sociale nominative',
            self::AUTRE => 'Autre',
        };
    }
}