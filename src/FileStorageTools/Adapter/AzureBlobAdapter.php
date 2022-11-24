<?php

namespace BayWaReLusy\FileStorageTools\Adapter;

use BayWaReLusy\FileStorageTools\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorageTools\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
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
            if (str_contains($e->getMessage(), '<Code>ContainerAlreadyExists</Code>')) {
                return;
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
            if (str_contains($e->getMessage(), '<Code>ContainerNotFound</Code>')) {
                throw new DirectoryDoesntExistsException(sprintf("The container '%s' doesn't exist.", $path));
            }
            throw new UnknownErrorException('Unknown File Storage error');
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
            $mimeType = mime_content_type($filePointer);
            if ($mimeType) {
                $options->setContentType($mimeType);
            }
            $this->blobStorageClient->createBlockBlob(
                $remoteDirectory,
                basename($pathToFile),
                $filePointer,
                $options
            );
        } catch (ServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new UnknownErrorException('Unknown File Storage error');
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
            error_log($e->getMessage());
            if (str_contains($e->getMessage(), '<Code>BlobNotFound</Code>')) {
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
            if (str_contains($e->getMessage(), '<Code>ContainerNotFound</Code>')) {
                throw new DirectoryDoesntExistsException(sprintf("The container '%s' doesn't exist.", $directory));
            }
            throw new UnknownErrorException('Unknown File Storage error');
        }
    }

    /**
     * @inheritDoc
     * @throws RemoteFileDoesntExistException
     */
    public function getPublicFileUrl(string $pathToFile): string
    {
        try {
            //check that the file exists, getBlobUrl doesn't care
            $this->blobStorageClient->getBlob(dirname($pathToFile), basename($pathToFile));
            return $this->blobStorageClient->getBlobUrl(
                    ltrim(dirname($pathToFile), '/'),
                    basename($pathToFile)
                ) . $this->sharedAccessSignature;
        } catch (ServiceException $e) {
            if (str_contains($e->getMessage(), '<Code>BlobNotFound</Code>')) {
                throw new RemoteFileDoesntExistException(sprintf("The file '%s' doesn't exist.", basename($pathToFile)));
            } elseif (str_contains($e->getMessage(), '<Code>ContainerNotFound</Code>')) {
                throw new DirectoryDoesntExistsException(sprintf("The container '%s' doesn't exist.", dirname($pathToFile)));
            }
            throw new UnknownErrorException('Unknown File Storage error');
        }
    }
}
