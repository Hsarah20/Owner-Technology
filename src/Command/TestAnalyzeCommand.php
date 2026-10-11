<?php

namespace App\Command;

use App\Messenger\Command\CommandBus;
use App\UseCase\Document\Analyze\AnalyzeDocumentCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-analyze', description: 'Test dispatch du bus de commandes')]
class TestAnalyzeCommand extends Command
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->commandBus->dispatch(new AnalyzeDocumentCommand(42));
        $output->writeln('<info>Dispatch OK</info>');

        return Command::SUCCESS;
    }
}