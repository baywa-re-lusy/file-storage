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
        } catch () {

        }
    }
}
