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

use BayWaReLusy\FileStorageTools\Adapter\AzureAdapter\DirectoryAlreadyExistsException;

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
     * @param string $path
     * @return void
     * @throws DirectoryAlreadyExistsException
     */
    public function createDirectory(string $path): void;
}
