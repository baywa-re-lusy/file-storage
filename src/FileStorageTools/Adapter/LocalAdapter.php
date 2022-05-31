<?php

namespace BayWaReLusy\FileStorageTools\Adapter;

use BayWaReLusy\FileStorageTools\Exception\DirectoryAlreadyExistsException;
use BayWaReLusy\FileStorageTools\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorageTools\Exception\DirectoryNotEmptyException;
use BayWaReLusy\FileStorageTools\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;

class LocalAdapter implements FileStorageAdapterInterface
{
    public function createDirectory(string $path): void
    {
        try {
            mkdir($path);
        } catch (\Throwable $e)
        {
            error_log($e->getMessage());
            throw $e;
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            rmdir($path);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    public function uploadFile(string $directory, string $pathToFile): void
    {
        // TODO: Implement uploadFile() method.
    }

    public function deleteFile(string $pathToFile): void
    {
        // TODO: Implement deleteFile() method.
    }

    public function listFilesInDirectory(string $directory, bool $includeDirectories = true): array
    {
        // TODO: Implement listFilesInDirectory() method.
    }

    public function getPublicFileUrl(string $pathToFile): string
    {
        // TODO: Implement getPublicFileUrl() method.
    }
}
