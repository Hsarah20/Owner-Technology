<?php

namespace App\Command;

use App\Entity\Document;
use App\Messenger\Command\CommandBus;
use App\UseCase\Document\Analyze\AnalyzeDocumentCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-analyze', description: 'Dispatch une analyse pour un document existant')]
class TestAnalyzeCommand extends Command
{
    public function __construct(
        private CommandBus $commandBus,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('documentId', InputArgument::REQUIRED, 'ID du document à analyser');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $documentId = (int) $input->getArgument('documentId');

        $document = $this->em->getRepository(Document::class)->find($documentId);
        if (!$document) {
            $output->writeln(sprintf('<error>Document %d introuvable</error>', $documentId));
            return Command::FAILURE;
        }

        $this->commandBus->dispatch(new AnalyzeDocumentCommand($documentId));
        $output->writeln(sprintf('<info>Analyse dispatchée pour le document %d</info>', $documentId));

        return Command::SUCCESS;
    }
}