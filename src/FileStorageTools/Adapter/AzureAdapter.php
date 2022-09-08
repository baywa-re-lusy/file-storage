<?php

/**
 * AzureAdapter.php
 *
 * @date      17.05.2022
 * @author    Pascal Paulis <pascal.paulis@baywa-re.com>
 * @file      AzureAdapter.php
 * @copyright Copyright (c) BayWa r.e. - All rights reserved
 * @license   Unauthorized copying of this source code, via any medium is strictly
 *            prohibited, proprietary and confidential.
 */

namespace BayWaReLusy\FileStorageTools\Adapter;

use BayWaReLusy\FileStorageTools\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorageTools\Exception\DirectoryNotEmptyException;
use BayWaReLusy\FileStorageTools\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Models\Range;
use MicrosoftAzure\Storage\File\FileRestProxy as FileStorageClient;
use MicrosoftAzure\Storage\File\Models\CreateFileOptions;

/**
 * AzureAdapter
 *
 * @package    BayWaReLusy
 * @author     Pascal Paulis <pascal.paulis@baywa-re.com>
 * @copyright  Copyright (c) BayWa r.e. - All rights reserved
 * @license    Unauthorized copying of this source code, via any medium is strictly
 *             prohibited, proprietary and confidential.
 */
class AzureAdapter implements FileStorageAdapterInterface
{
    /**
     * AzureAdapter constructor.
     * @param FileStorageClient $fileStorageClient
     * @param string $fileShare
     * @param string $sharedAccessSignature
     * @param string $storageAccountName
     */
    public function __construct(
        protected FileStorageClient $fileStorageClient,
        protected string $fileShare,
        protected string $sharedAccessSignature,
        protected string $storageAccountName
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createDirectory(string $path): void
    {
        try {
            $this->fileStorageClient->createDirectory($this->fileShare, ltrim($path, '/'));
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
            $this->fileStorageClient->deleteDirectory($this->fileShare, ltrim($path, '/'));
        } catch (ServiceException $e) {
            if (str_contains($e->getMessage(), '<Code>ResourceNotFound</Code>')) {
                throw new DirectoryDoesntExistsException(sprintf("The directory '%s' doesn't exist.", $path));
            } elseif (str_contains($e->getMessage(), '<Code>DirectoryNotEmpty</Code>')) {
                throw new DirectoryNotEmptyException(sprintf("The directory '%s' isn't empty.", $path));
            }

            throw new UnknownErrorException('Unknown File Storage error');
        }
    }

    /**
     * @inheritDoc
     */
    public function uploadFile(string $remoteDirectory, string $pathToFile): void
    {
        // Check first if local file exists and can be opened
        if (!file_exists($pathToFile)) {
            throw new LocalFileNotFoundException("File not found.");
        }

        if (!$filePointer = fopen($pathToFile, 'r')) {
            throw new FileCouldNotBeOpenedException("File couldn't be opened.");
        }

        // Calculate filesize to determine the number of chunks to be uploaded. Azure is limited to 4MB per chunk.
        $filesize        = (int)filesize($pathToFile);
        $currentPosition = 0;

        // Create path to remote file
        $remoteFileName = trim($remoteDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($pathToFile);

        // Create the empty file first
        $createFileOptions = new CreateFileOptions();
        $createFileOptions->setContentLength($filesize);
        $this->fileStorageClient->createFile($this->fileShare, $remoteFileName, $filesize, $createFileOptions);

        // Upload chunks of maximum 4MB
        while ($currentPosition < $filesize) {
            $this->fileStorageClient->putFileRange(
                $this->fileShare,
                $remoteFileName,
                $filePointer,
                new Range($currentPosition, min($currentPosition + (4096 * 4096 - 1), $filesize - 1))
            );

            $currentPosition += 4096 * 4096;
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteFile(string $pathToFile): void
    {
        try {
            $this->fileStorageClient->deleteFile($this->fileShare, ltrim($pathToFile, '/'));
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
            $result   = [];
            $response = $this->fileStorageClient->listDirectoriesAndFiles($this->fileShare, ltrim($directory, '/'));

            // Optionally, include the directories
            if ($includeDirectories) {
                foreach ($response->getDirectories() as $dir) {
                    $result[] = $dir->getName();
                }
            }

            // Add the files
            foreach ($response->getFiles() as $file) {
                $result[] = $file->getName();
            }

            return $result;
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
            @$this->fileStorageClient->getFile($this->fileShare, ltrim($pathToFile, '/'));
        } catch (ServiceException $e) {
            if (str_contains($e->getMessage(), '<Code>ResourceNotFound</Code>')) {
                throw new RemoteFileDoesntExistException(sprintf("The file '%s' doesn't exist.", $pathToFile));
            }

            throw new UnknownErrorException('Unknown File Storage error');
        }
        // Adding a timestamp to avoid conditional headers client side if more than one request
        return sprintf(
            "https://%s.file.core.windows.net/%s/%s%s&time=%s",
            $this->storageAccountName,
            $this->fileShare,
            ltrim($pathToFile, '/'),
            $this->sharedAccessSignature,
            time()
        );
    }
}
