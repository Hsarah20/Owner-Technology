<?php

namespace App\UseCase\Document\Upload;

use App\Entity\Client;
use App\Entity\User;
use App\Messenger\Command\Command;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadDocumentsCommand implements Command
{
    /**
     * @param UploadedFile[] $files
     */
    public function __construct(
        public Client $client,
        public User $uploader,
        public array $files,
    ) {}
}