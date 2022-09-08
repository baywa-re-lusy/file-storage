<?php

namespace BayWaReLusy\FileStorageTools\Adapter;

use BayWaReLusy\FileStorageTools\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorageTools\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

class AzureBlobAdapter implements FileStorageAdapterInterface
{
    public function __construct(
        protected BlobRestProxy $blobStorageClient,
        protected string $sharedAccessSignature,
        protected string $storageAccountName,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createDirectory(string $path): void
    {
        try {
            $this->blobStorageClient->createContainer(ltrim($path, '/'));
        } catch (ServiceException $e) {
            if (str_contains($e->getMessage(), '<Code>ResourceAlreadyExists</Code>')) {
                return;
            } elseif (str_contains($e->getMessage(), '<Code>ParentNotFound</Code>')) {
                throw new ParentNotFoundException("The parent of the directory you want to create doesn't exist.");
            }

            throw new UnknownErrorException('Unknown File Storage error');
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteDirectory(string $path): void
    {
        try {
            $this->blobStorageClient->deleteContainer($path);
        } catch (ServiceException $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
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
            $options = new CreateBlockBlobOptions();
            try {
                $mimeType = mime_content_type($filePointer);
                $options->setContentType($mimeType);
            } catch (\Throwable $e) {
                error_log("The Mime type of the file could not be determined");
            }
            $this->blobStorageClient->createBlockBlob(
                $remoteDirectory,
                basename($pathToFile),
                $filePointer,
                $options
            );
        } catch (ServiceException $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteFile(string $pathToFile): void
    {
        try {
            $this->blobStorageClient->deleteBlob(
                dirname($pathToFile),
                basename($pathToFile)
            );
        } catch (ServiceException $e) {
            if (str_contains($e->getMessage(), '<Code>ResourceNotFound</Code>')) {
                throw new RemoteFileDoesntExistException(sprintf("The file '%s' couldn't be found.", $pathToFile));
            }

            throw new UnknownErrorException('Unknown File Storage error');
        }
    }

    /**
     * @inheritDoc
     */
    public function listFilesInDirectory(string $directory, bool $includeDirectories = true): array
    {
        try {
            $results = [];
            $blobs = $this->blobStorageClient->listBlobs(ltrim($directory, '/'));
            foreach ($blobs->getBlobs() as $blob) {
                $results[] = $blob->getName();
            }
            return $results;
        } catch (ServiceException $e) {
            if (str_contains($e->getMessage(), '<Code>ResourceNotFound</Code>')) {
                throw new DirectoryDoesntExistsException(sprintf("The directory '%s' doesn't exist.", $directory));
            }

            throw new UnknownErrorException('Unknown File Storage error');
        }
    }

    /**
     * @inheritDoc
     */
    public function getPublicFileUrl(string $pathToFile): string
    {
        try {
            return $this->blobStorageClient->getBlobUrl(
                ltrim(dirname($pathToFile), '/'),
                basename($pathToFile)
            ) . $this->sharedAccessSignature;
        } catch (ServiceException $e) {
            if (str_contains($e->getMessage(), '<Code>ResourceNotFound</Code>')) {
                throw new RemoteFileDoesntExistException(sprintf("The file '%s' doesn't exist.", $pathToFile));
            }

            throw new UnknownErrorException('Unknown File Storage error');
        }
    }
}
