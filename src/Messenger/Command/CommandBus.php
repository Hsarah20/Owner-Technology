<?php

namespace App\Messenger\Command;

interface CommandBus
{
    public function dispatch(Command $command): mixed;
}