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

use BayWaReLusy\FileStorageTools\Exception\DirectoryAlreadyExistsException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
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
     * @throws DirectoryAlreadyExistsException
     * @throws ParentNotFoundException
     * @throws UnknownErrorException
     */
    public function createDirectory(string $path): void;

    /**
     * Upload a file to an existing directory.
     *
     * @param string $directory Existing remote directory
     * @param string $pathToFile Path and name of the file to upload
     * @return void
     * @throws LocalFileNotFoundException
     */
    public function uploadFile(string $directory, string $pathToFile): void;
}
