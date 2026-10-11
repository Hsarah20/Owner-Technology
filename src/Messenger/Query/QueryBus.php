<?php

namespace App\Messenger\Query;

interface QueryBus
{
    public function ask(Query $query): mixed;
}