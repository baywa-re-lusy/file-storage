<?php

/**
 * FileStorageTools.php
 *
 * @date        17.05.2022
 * @author      Pascal Paulis <pascal.paulis@baywa-re.com>
 * @file        FileStorageTools.php
 * @copyright   Copyright (c) BayWa r.e. - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace BayWaReLusy\FileStorageTools;

use Laminas\ServiceManager\ServiceManager;

/**
 * Class FileStorageTools
 *
 * Entry-point to use the tool-set
 *
 * @package     BayWaReLusy
 * @subpackage  FileStorageTools
 * @author      Pascal Paulis <pascal.paulis@baywa-re.com>
 * @copyright   Copyright (c) BayWa r.e. - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */
class FileStorageTools extends ServiceManager
{
    public function __construct(FileStorageToolsConfig $config)
    {
        $services = require __DIR__ . '/../../config/module.config.php';
        parent::__construct($services['service_manager']);

        $this->setService(FileStorageToolsConfig::class, $config);
    }
}
