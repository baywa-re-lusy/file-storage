<?php

/**
 * FileStorageService.php
 *
 * @date      17.05.2022
 * @author    Pascal Paulis <pascal.paulis@baywa-re.com>
 * @file      FileStorageService.php
 * @copyright Copyright (c) BayWa r.e. - All rights reserved
 * @license   Unauthorized copying of this source code, via any medium is strictly
 *            prohibited, proprietary and confidential.
 */

namespace BayWaReLusy\FileStorageTools;

use BayWaReLusy\FileStorageTools\Adapter\FileStorageAdapterInterface;
use BayWaReLusy\FileStorageTools\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\RemoteFileDoesntExistException;

/**
 * Class FileStorageService
 *
 * @package     BayWaReLusy
 * @subpackage  FileStorageTools
 * @author      Pascal Paulis <pascal.paulis@baywa-re.com>
 * @copyright   Copyright (c) BayWa r.e. - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */
class FileStorageService
{
    protected FileStorageAdapterInterface $adapter;

    /**
     * Set the adapter.
     *
     * @param FileStorageAdapterInterface $adapter The adapter.
     * @return self Provides a fluent interface.
     */
    public function setAdapter(FileStorageAdapterInterface $adapter): self
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Return the adapter.
     *
     * @return FileStorageAdapterInterface The adapter.
     */
    public function getAdapter(): FileStorageAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @param string $path
     * @return void
     * @throws Exception\DirectoryAlreadyExistsException
     * @throws Exception\ParentNotFoundException
     * @throws Exception\UnknownErrorException
     */
    public function createDirectory(string $path): void
    {
        $this->getAdapter()->createDirectory($path);
    }

    /**
     * Upload a file to an existing directory.
     *
     * @param string $directory Existing remote directory
     * @param string $pathToFile Path and name of the file to upload
     * @return void
     * @throws LocalFileNotFoundException
     * @throws FileCouldNotBeOpenedException
     */
    public function uploadFile(string $directory, string $pathToFile): void
    {
        $this->getAdapter()->uploadFile($directory, $pathToFile);
    }

    /**
     * @param string $directory
     * @param string $pathToFile
     * @return void
     * @throws RemoteFileDoesntExistException
     */
    public function deleteFile(string $directory, string $pathToFile): void
    {
        $this->getAdapter()->deleteFile($directory, $pathToFile);
    }
}
