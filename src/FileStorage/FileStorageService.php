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

namespace BayWaReLusy\FileStorage;

use BayWaReLusy\FileStorage\Adapter\FileStorageAdapterInterface;
use BayWaReLusy\FileStorage\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorage\Exception\DirectoryNotEmptyException;
use BayWaReLusy\FileStorage\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorage\Exception\InvalidDestinationException;
use BayWaReLusy\FileStorage\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorage\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorage\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorage\Exception\UnknownErrorException;

/**
 * Class FileStorageService
 *
 * @package     BayWaReLusy
 * @subpackage  FileStorage
 * @author      Pascal Paulis <pascal.paulis@baywa-re.com>
 * @copyright   Copyright (c) BayWa r.e. - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */
class FileStorageService
{
    /**
     * @param FileStorageAdapterInterface $adapter
     */
    public function __construct(
        protected FileStorageAdapterInterface $adapter
    ) {
    }

    /**
     * @param string $path
     * @return void
     * @throws ParentNotFoundException
     * @throws UnknownErrorException
     * @throws InvalidDestinationException
     */
    public function createDirectory(string $path): void
    {
        $this->adapter->createDirectory($path);
    }

    /**
     * @param string $path
     * @return void
     * @throws DirectoryDoesntExistsException
     * @throws DirectoryNotEmptyException
     * @throws UnknownErrorException
     * @throws InvalidDestinationException
     */
    public function deleteDirectory(string $path): void
    {
        $this->adapter->deleteDirectory($path);
    }

    /**
     * Upload a file to an existing directory.
     *
     * @param string $localFilename Absolute path to the file to upload
     * @param string $remoteFilename Path to the remote file
     * @return void
     * @throws FileCouldNotBeOpenedException
     * @throws LocalFileNotFoundException
     * @throws InvalidDestinationException
     */
    public function uploadFile(string $localFilename, string $remoteFilename): void
    {
        $this->adapter->uploadFile($localFilename, $remoteFilename);
    }

    /**
     * Delete the given file.
     *
     * @param string $pathToFile
     * @return void
     * @throws RemoteFileDoesntExistException
     * @throws UnknownErrorException
     * @throws InvalidDestinationException
     */
    public function deleteFile(string $pathToFile): void
    {
        $this->adapter->deleteFile($pathToFile);
    }

    /**
     * List all files in the given directory.
     *
     * @param string $directory
     * @param bool $includeDirectories If true, directories are included in the result
     * @return string[]
     * @throws DirectoryDoesntExistsException
     * @throws UnknownErrorException
     * @throws InvalidDestinationException
     */
    public function listFilesInDirectory(string $directory, bool $includeDirectories = true): array
    {
        return $this->adapter->listFilesInDirectory($directory, $includeDirectories);
    }

    /**
     * @param string $pathToFile
     * @return string
     * @throws DirectoryDoesntExistsException
     * @throws UnknownErrorException
     * @throws InvalidDestinationException
     * @throws RemoteFileDoesntExistException
     */
    public function getPublicFileUrl(string $pathToFile): string
    {
        return $this->adapter->getPublicFileUrl($pathToFile);
    }
}
