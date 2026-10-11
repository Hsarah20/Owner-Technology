<?php

namespace App\UseCase\Document\Retry;

use App\Entity\Document;
use App\Entity\TimelineEvent;
use App\Enum\DocumentStatus;
use App\Enum\TimelineEventType;
use App\Messenger\Command\CommandBus;
use App\Messenger\Command\CommandHandler;
use App\UseCase\Document\Analyze\AnalyzeDocumentCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


final class RetryAnalysisHandler implements CommandHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private CommandBus $commandBus,
    ) {}

    public function __invoke(RetryAnalysisCommand $command): void
    {
        $document = $this->em->getRepository(Document::class)->find($command->documentId);

        if (!$document) {
            throw new \RuntimeException(sprintf('Document %d introuvable', $command->documentId));
        }

        if ($document->getStatus() !== DocumentStatus::FAILED) {
            throw new \RuntimeException('Seul un document en échec peut être relancé.');
        }

        $event = new TimelineEvent();
        $event->setDocument($document);
        $event->setType(TimelineEventType::RETRY_REQUESTED);
        $event->setMessage(sprintf('Relance demandée par %s', $command->admin->getEmail()));
        $event->setActor($command->admin);
        $this->em->persist($event);

        $document->setStatus(DocumentStatus::PENDING);
        $this->em->flush();

        $this->commandBus->dispatch(new AnalyzeDocumentCommand(
            documentId: $document->getId(),
            triggeredBy: $command->admin,
        ));
    }
}