<?php

namespace App\Messenger\Event;

use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEventBus implements EventBus
{
    public function __construct(
        private MessageBusInterface $eventBus,
    ) {}

    public function dispatch(Event $event): void
    {
        $this->eventBus->dispatch($event);
    }
}