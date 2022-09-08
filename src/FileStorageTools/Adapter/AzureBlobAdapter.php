<?php

namespace BayWaReLusy\FileStorageTools\Adapter;

use BayWaReLusy\FileStorageTools\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

class AzureBlobAdapter implements FileStorageAdapterInterface
{
    public function __construct(
        protected BlobRestProxy $blobStorageClient,
        protected string        $sharedAccessSignature,
        protected string        $storageAccountName,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function createDirectory(string $path): void
    {
        try {
            $this->blobStorageClient->createContainer($path);
        } catch (ServiceException $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $this->blobStorageClient->deleteContainer($path);
        } catch (ServiceException $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    public function uploadFile(string $remoteDirectory, string $pathToFile): void
    {
        try {
            // Check first if local file exists and can be opened
            if (!file_exists($pathToFile)) {
                throw new LocalFileNotFoundException("File not found.");
            }

            if (!$filePointer = fopen($pathToFile, 'r')) {
                throw new FileCouldNotBeOpenedException("File couldn't be opened.");
            }
            $this->blobStorageClient->createBlockBlob(
                $remoteDirectory,
                $pathToFile,
                $filePointer
            );
        } catch (ServiceException $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    public function deleteFile(string $pathToFile): void
    {
        try {
            $this->blobStorageClient->deleteBlob(
                dirname($pathToFile),
                basename($pathToFile)
            );
        } catch (ServiceException $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    public function listFilesInDirectory(string $directory, bool $includeDirectories = true): array
    {
        try {
            $results = [];
            $blobs = $this->blobStorageClient->listBlobs(
                $directory
            );
            foreach ($blobs->getBlobs() as $blob) {
                $results[] = $blob->getName();
            }
            return $results;
        } catch (ServiceException $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    public function getPublicFileUrl(string $pathToFile): string
    {
        try {
            return $this->blobStorageClient->getBlobUrl(
                dirname($pathToFile),
                basename($pathToFile)
            );
        } catch (ServiceException $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }
}