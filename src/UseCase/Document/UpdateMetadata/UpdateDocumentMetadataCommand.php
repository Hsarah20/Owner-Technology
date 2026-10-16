<?php

namespace App\UseCase\Document\UpdateMetadata;

use App\Enum\DocumentNature;
use App\Messenger\Command\Command;

final class UpdateDocumentMetadataCommand implements Command
{
    public function __construct(
        public int $documentId,
        public ?DocumentNature $nature,
        public ?int $periodYear,
        public ?int $periodMonth,
    ) {}
}