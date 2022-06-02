<?php

/**
 * FileStorageAdapterInterface.php
 *
 * @date      17.05.2022
 * @author    Pascal Paulis <pascal.paulis@baywa-re.com>
 * @file      FileStorageAdapterInterface.php
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

/**
 * FileStorageAdapterInterface
 *
 * @package     BayWaReLusy
 * @subpackage  FileStorageTools
 * @author      Pascal Paulis <pascal.paulis@baywa-re.com>
 * @copyright   Copyright (c) BayWa r.e. - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */
interface FileStorageAdapterInterface
{
    /**
     * Create a new directory. Examples:
     *   - test
     *   - test1/test2
     *   - ..
     *
     * @param string $path Path of the directory to create
     * @return void
     * @throws ParentNotFoundException
     * @throws UnknownErrorException
     */
    public function createDirectory(string $path): void;

    /**
     * Delete a directory. Examples:
     *   - test
     *   - test1/test2
     *   - ..
     *
     * @param string $path Path of the directory to delete
     * @return void
     * @throws DirectoryDoesntExistsException
     * @throws DirectoryNotEmptyException
     * @throws UnknownErrorException
     */
    public function deleteDirectory(string $path): void;

    /**
     * Upload a file to an existing directory.
     *
     * @param string $remoteDirectory Existing remote directory
     * @param string $pathToFile Path and name of the file to upload
     * @return void
     * @throws LocalFileNotFoundException
     * @throws FileCouldNotBeOpenedException
     */
    public function uploadFile(string $remoteDirectory, string $pathToFile): void;

    /**
     * Delete a file from the File Share.
     *
     * @param string $pathToFile Path and name of the file to delete
     * @return void
     * @throws RemoteFileDoesntExistException
     * @throws UnknownErrorException
     */
    public function deleteFile(string $pathToFile): void;

    /**
     * List the files in the given directory.
     *
     * @param string $directory The directory to list
     * @param bool $includeDirectories If true, directories are included in the result
     * @return string[] List of files in the given directory
     * @throws DirectoryDoesntExistsException
     * @throws UnknownErrorException
     */
    public function listFilesInDirectory(string $directory, bool $includeDirectories = true): array;

    /**
     * Get the publicly accessible URL of the given file.
     *
     * @param string $pathToFile Path to the file to list
     * @return string Publicly accessible URL of the given file
     * @throws DirectoryDoesntExistsException
     * @throws UnknownErrorException
     */
    public function getPublicFileUrl(string $pathToFile): string;
}
