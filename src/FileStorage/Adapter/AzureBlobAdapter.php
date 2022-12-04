<?php

namespace BayWaReLusy\FileStorage\Adapter;

use BayWaReLusy\FileStorage\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorage\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorage\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorage\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorage\Exception\UnknownErrorException;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

class AzureBlobAdapter implements FileStorageAdapterInterface
{
    protected const CONNECTION_STRING = 'BlobEndpoint=https://%s.blob.core.windows.net/;SharedAccessSignature=%s';

    protected ?BlobRestProxy $blobStorageClient = null;

    public function __construct(
        protected string $storageAccountName,
        protected string $containerName,
        protected string $sharedAccessSignature
    ) {
    }

    protected function getBlobStorageClient(): BlobRestProxy
    {
        if (empty($this->containerName)) {
            throw new \Exception('Container name not set.');
        }

        if (!$this->blobStorageClient) {
            $this->blobStorageClient = BlobRestProxy::createBlobService(
                sprintf(self::CONNECTION_STRING, $this->storageAccountName, $this->sharedAccessSignature)
            );
        }

        return $this->blobStorageClient;
    }

    /**
     * @inheritDoc
     */
    public function createDirectory(string $path): void
    {
        try {
            $this->getBlobStorageClient()->createContainer(ltrim($path, '/'));
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
            $this->getBlobStorageClient()->deleteContainer($path);
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
    public function uploadFile(string $localFilename, string $remoteFilename): void
    {
        try {
            // Check first if local file exists and can be opened
            if (!file_exists($localFilename)) {
                throw new LocalFileNotFoundException("File not found.");
            }

            if (!$filePointer = fopen($localFilename, 'r')) {
                throw new FileCouldNotBeOpenedException("File couldn't be opened.");
            }
            $options = new CreateBlockBlobOptions();
            $mimeType = mime_content_type($filePointer);
            if ($mimeType) {
                $options->setContentType($mimeType);
            }
            $this->getBlobStorageClient()->createBlockBlob(
                $this->containerName,
                $remoteFilename,
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
            $this->getBlobStorageClient()->deleteBlob(
                dirname($pathToFile),
                basename($pathToFile)
            );
        } catch (ServiceException $e) {
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
            $blobs = $this->getBlobStorageClient()->listBlobs(ltrim($directory, '/'));
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
            $this->getBlobStorageClient()->getBlob(dirname($pathToFile), basename($pathToFile));
            return $this->getBlobStorageClient()->getBlobUrl(
                ltrim(dirname($pathToFile), '/'),
                basename($pathToFile)
            ) . $this->sharedAccessSignature;
        } catch (ServiceException $e) {
            if (str_contains($e->getMessage(), '<Code>BlobNotFound</Code>')) {
                throw new RemoteFileDoesntExistException(sprintf(
                    "The file '%s' doesn't exist.",
                    basename($pathToFile)
                ));
            } elseif (str_contains($e->getMessage(), '<Code>ContainerNotFound</Code>')) {
                throw new DirectoryDoesntExistsException(sprintf(
                    "The container '%s' doesn't exist.",
                    dirname($pathToFile)
                ));
            }
            throw new UnknownErrorException('Unknown File Storage error');
        }
    }
}
