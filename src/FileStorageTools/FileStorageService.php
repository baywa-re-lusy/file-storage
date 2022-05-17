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
}
