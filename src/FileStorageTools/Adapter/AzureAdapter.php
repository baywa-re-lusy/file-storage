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

use BayWaReLusy\FileStorageTools\Exception\DirectoryAlreadyExistsException;
use BayWaReLusy\FileStorageTools\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
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
     */
    public function __construct(
        protected FileStorageClient $fileStorageClient,
        protected string $fileShare
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function createDirectory(string $path): void
    {
        try {
            $this->fileStorageClient->createDirectory($this->fileShare, ltrim($path, '/'));
        } catch (ServiceException $e) {
            if (str_contains($e->getMessage(), '<Code>ResourceAlreadyExists</Code>')) {
                throw new DirectoryAlreadyExistsException(sprintf("The directory '%s' already exists.", $path));
            } elseif (str_contains($e->getMessage(), '<Code>ParentNotFound</Code>')) {
                throw new ParentNotFoundException("The parent of the directory you want to create doesn't exist.");
            }

            throw new UnknownErrorException('Unknown File Storage error');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function uploadFile(string $directory, string $pathToFile): void
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

        // Create the empty file first
        $createFileOptions = new CreateFileOptions();
        $createFileOptions->setContentLength($filesize);
        $this->fileStorageClient->createFile($this->fileShare, $pathToFile, $filesize, $createFileOptions);

        // Upload chunks of maximum 4MB
        while ($currentPosition < $filesize) {
            $this->fileStorageClient->putFileRange(
                $this->fileShare,
                $pathToFile,
                $filePointer,
                new Range($currentPosition, min($currentPosition + (4096 * 4096 - 1), $filesize - 1))
            );

            $currentPosition += 4096 * 4096;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deleteFile(string $pathToFile): void
    {
        $this->fileStorageClient->deleteFile($this->fileShare, ltrim($pathToFile, '/'));
    }
}
