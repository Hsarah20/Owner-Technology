<?php

namespace App\UseCase\Document\Analyze;

use App\Entity\AnalysisAttempt;
use App\Entity\Document;
use App\Entity\TimelineEvent;
use App\Enum\DocumentStatus;
use App\Enum\FailureReason;
use App\Enum\TimelineEventType;
use App\Messenger\Command\CommandHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class AnalyzeDocumentHandler implements CommandHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(AnalyzeDocumentCommand $command): void
    {
        $document = $this->em->getRepository(Document::class)->find($command->documentId);

        if (!$document) {
            $this->logger->warning('AnalyzeDocument : document introuvable', [
                'document_id' => $command->documentId,
            ]);
            return;
        }

        $attemptNumber = $this->em->getRepository(AnalysisAttempt::class)
            ->count(['document' => $document]) + 1;

        $attempt = new AnalysisAttempt();
        $attempt->setDocument($document);
        $attempt->setAttemptNumber($attemptNumber);
        $attempt->setStatus(DocumentStatus::PROCESSING);
        $attempt->setTriggeredBy($command->triggeredBy);
        $this->em->persist($attempt);

        $document->setStatus(DocumentStatus::PROCESSING);

        $event = new TimelineEvent();
        $event->setDocument($document);
        $event->setType(TimelineEventType::ANALYSIS_STARTED);
        $event->setMessage(sprintf('Analyse n°%d démarrée', $attemptNumber));
        $event->setActor($command->triggeredBy);
        $this->em->persist($event);
        $this->em->flush();

        $seconds = random_int(3, 8);
        $this->logger->info('Analyse simulée en cours', [
            'document_id' => $document->getId(),
            'duration' => $seconds,
        ]);
        sleep($seconds);

        $isSuccess = random_int(1, 100) <= 70;

        if ($isSuccess) {
            $attempt->setStatus(DocumentStatus::COMPLETED);
            $attempt->setFinishedAt(new \DateTimeImmutable());
            $attempt->setResult([
                'summary' => 'Document analysé avec succès',
                'pages' => random_int(1, 30),
            ]);
            $document->setStatus(DocumentStatus::COMPLETED);

            $event = new TimelineEvent();
            $event->setDocument($document);
            $event->setType(TimelineEventType::ANALYSIS_COMPLETED);
            $event->setMessage(sprintf('Analyse n°%d réussie', $attemptNumber));
            $this->em->persist($event);
        } else {
            $reason = $this->pickRandomFailureReason();
            $attempt->setStatus(DocumentStatus::FAILED);
            $attempt->setFinishedAt(new \DateTimeImmutable());
            $attempt->setFailureReason($reason);
            $document->setStatus(DocumentStatus::FAILED);

            $event = new TimelineEvent();
            $event->setDocument($document);
            $event->setType(TimelineEventType::ANALYSIS_FAILED);
            $event->setMessage(sprintf('Analyse n°%d en échec : %s', $attemptNumber, $reason->label()));
            $event->setMetadata(['reason' => $reason->value]);
            $this->em->persist($event);
        }

        $this->em->flush();
    }

    private function pickRandomFailureReason(): FailureReason
    {
        $reasons = [
            FailureReason::UNREADABLE,
            FailureReason::UNSUPPORTED_FORMAT,
            FailureReason::PROCESSING_INTERRUPTED,
            FailureReason::UNKNOWN,
        ];
        return $reasons[array_rand($reasons)];
    }
}