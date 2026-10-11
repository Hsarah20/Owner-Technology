<?php

namespace App\Service\Storage;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocumentStorage
{
    public function __construct(
        private FilesystemOperator $documentsStorage,
    ) {}

    public function store(UploadedFile $file, int $clientId, string $documentUuid, string $extension): string
    {
        $path = sprintf('client-%d/%s.%s', $clientId, $documentUuid, $extension);
        $stream = fopen($file->getRealPath(), 'r');
        $this->documentsStorage->writeStream($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }
        return $path;
    }

    public function readStream(string $path): mixed
    {
        return $this->documentsStorage->readStream($path);
    }

    public function delete(string $path): void
    {
        if ($this->documentsStorage->fileExists($path)) {
            $this->documentsStorage->delete($path);
        }
    }

    public function exists(string $path): bool
    {
        return $this->documentsStorage->fileExists($path);
    }
}