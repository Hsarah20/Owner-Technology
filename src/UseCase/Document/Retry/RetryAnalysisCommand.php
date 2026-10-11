<?php

namespace App\UseCase\Document\Retry;

use App\Entity\User;
use App\Messenger\Command\Command;

final class RetryAnalysisCommand implements Command
{
    public function __construct(
        public int $documentId,
        public User $admin,
    ) {}
}