<?php

namespace App\Command;

use App\Entity\Client;
use App\Entity\Document;
use App\Entity\TimelineEvent;
use App\Entity\Upload;
use App\Enum\DocumentStatus;
use App\Enum\TimelineEventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(name: 'app:seed-test-document', description: 'Crée un document factice pour tester l\'analyse')]
class SeedTestDocumentCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = $this->em->getRepository(Client::class)->findOneBy(['name' => 'ACME SARL']);
        if (!$client) {
            $output->writeln('<error>Lance doctrine:fixtures:load d\'abord</error>');
            return Command::FAILURE;
        }

        $upload = new Upload();
        $upload->setClient($client);
        $upload->setStatus(DocumentStatus::RECEIVED);
        $this->em->persist($upload);

        $document = new Document();
        $document->setClient($client);
        $document->setUpload($upload);
        $document->setOriginalName('bilan-test.pdf');
        $document->setStoredPath('client-' . $client->getId() . '/' . Uuid::v4() . '.pdf');
        $document->setMimeType('application/pdf');
        $document->setSize(102400);
        $document->setStatus(DocumentStatus::RECEIVED);
        $this->em->persist($document);

        $event = new TimelineEvent();
        $event->setDocument($document);
        $event->setType(TimelineEventType::UPLOADED);
        $event->setMessage('Fichier de test déposé');
        $this->em->persist($event);

        $this->em->flush();

        $output->writeln(sprintf('<info>Document créé : ID = %d</info>', $document->getId()));
        return Command::SUCCESS;
    }
}