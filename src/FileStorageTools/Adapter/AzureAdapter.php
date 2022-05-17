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
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\File\FileRestProxy as FileStorageClient;

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
            $this->fileStorageClient->createDirectory($this->fileShare, $path);
        } catch (ServiceException $e) {
            if (str_contains($e->getMessage(), '<Code>ResourceAlreadyExists</Code>')) {
                throw new DirectoryAlreadyExistsException(sprintf("The directory '%s' already exists.", $path));
            } elseif (str_contains($e->getMessage(), '<Code>ParentNotFound</Code>')) {
                throw new ParentNotFoundException("The parent of the directory you want to create doesn't exist.");
            }

            throw new UnknownErrorException('Unknown File Storage error');
        }
    }
}
