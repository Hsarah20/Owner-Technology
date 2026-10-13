<?php

namespace App\Messenger\Command;

interface CommandBus
{
    /**
     * Dispatch a command asynchronously (fire and forget).
     */
    public function dispatch(Command $command): void;

    /**
     * Dispatch a command synchronously and return the handler's value.
     * Only usable for commands NOT routed to an async transport.
     */
    public function dispatchSync(Command $command): mixed;
}