<?php

namespace App\UseCase\Document\UpdateMetadata;

use App\Entity\Document;
use App\Messenger\Command\CommandHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


final class UpdateDocumentMetadataHandler implements CommandHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(UpdateDocumentMetadataCommand $command): void
    {
        $document = $this->em->getRepository(Document::class)->find($command->documentId);

        if (!$document) {
            throw new \RuntimeException(sprintf('Document %d introuvable', $command->documentId));
        }

        $document->setNature($command->nature);
        $document->setPeriodYear($command->periodYear);
        $document->setPeriodMonth($command->periodMonth);

        $this->em->flush();
    }
}