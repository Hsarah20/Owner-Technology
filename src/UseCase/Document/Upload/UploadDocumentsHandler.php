<?php

namespace App\UseCase\Document\Upload;

use App\Entity\Document;
use App\Entity\TimelineEvent;
use App\Entity\Upload;
use App\Enum\DocumentStatus;
use App\Enum\TimelineEventType;
use App\Messenger\Command\CommandHandler;
use App\Service\Storage\DocumentStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

final class UploadDocumentsHandler implements CommandHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private DocumentStorage $storage,
    ) {}

    /**
     * @return Document[]
     */
    public function __invoke(UploadDocumentsCommand $command): array
    {
        $upload = new Upload();
        $upload->setClient($command->client);
        $upload->setStatus(DocumentStatus::RECEIVED);
        $this->em->persist($upload);

        $documents = [];

        foreach ($command->files as $file) {
            $uuid = Uuid::v4()->toRfc4122();
            $extension = $file->guessExtension() ?? 'bin';

            $storedPath = $this->storage->store(
                $file,
                $command->client->getId(),
                $uuid,
                $extension,
            );

            $document = new Document();
            $document->setClient($command->client);
            $document->setUpload($upload);
            $document->setOriginalName($file->getClientOriginalName());
            $document->setStoredPath($storedPath);
            $document->setMimeType($file->getClientMimeType() ?? 'application/octet-stream');
            $document->setSize($file->getSize());
            $document->setStatus(DocumentStatus::RECEIVED);
            $this->em->persist($document);

            $event = new TimelineEvent();
            $event->setDocument($document);
            $event->setType(TimelineEventType::UPLOADED);
            $event->setMessage(sprintf('Fichier « %s » déposé', $file->getClientOriginalName()));
            $event->setActor($command->uploader);
            $this->em->persist($event);

            $documents[] = $document;
        }

        $this->em->flush();

        return $documents;
    }
}