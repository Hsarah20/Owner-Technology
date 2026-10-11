<?php

namespace App\UseCase\Document\Analyze;

use App\Messenger\Command\CommandHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


final class AnalyzeDocumentHandler implements CommandHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(AnalyzeDocumentCommand $command): void
    {
        $this->logger->info('Analyse simulée démarrée', [
            'document_id' => $command->documentId,
        ]);

        // Simulation d'une analyse de document (par exemple, un traitement asynchrone)
        sleep(1);

        $this->logger->info('Analyse simulée terminée', [
            'document_id' => $command->documentId,
        ]);
    }
}