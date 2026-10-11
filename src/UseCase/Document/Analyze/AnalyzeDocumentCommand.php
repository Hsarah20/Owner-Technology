<?php

namespace App\UseCase\Document\Analyze;

use App\Messenger\Command\Command;

final class AnalyzeDocumentCommand implements Command
{
    public function __construct(
        public int $documentId,
    ) {}
}