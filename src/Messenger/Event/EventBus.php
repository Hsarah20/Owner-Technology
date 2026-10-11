<?php

namespace App\Messenger\Event;

interface EventBus
{
    public function dispatch(Event $event): void;
}