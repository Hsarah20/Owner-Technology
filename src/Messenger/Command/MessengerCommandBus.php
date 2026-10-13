<?php

namespace App\Messenger\Command;

use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerCommandBus implements CommandBus
{
    use HandleTrait;

    public function __construct(
        private MessageBusInterface $innerBus,
    ) {
        $this->messageBus = $innerBus;
    }

    public function dispatch(Command $command): void
    {
        $this->innerBus->dispatch($command);
    }

    public function dispatchSync(Command $command): mixed
    {
        return $this->handle($command);
    }
}