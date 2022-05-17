<?php
/**
 * FileServiceToolsConfig.php
 *
 * @date        17.05.2022
 * @author      Pascal Paulis <pascal.paulis@baywa-re.com>
 * @file        FileServiceToolsConfig.php
 * @copyright   Copyright (c) BayWa r.e. - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace BayWaReLusy\FileStorageTools;

/**
 * Class FileServiceToolsConfig
 *
 * Config object for FileServiceTools
 *
 * @package     BayWaReLusy
 * @author      Pascal Paulis <pascal.paulis@baywa-re.com>
 * @copyright   Copyright (c) BayWa r.e. - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */
class FileServiceToolsConfig
{
    /**
     * @param string $azureStorageConnectionString Connection string of the Azure storage account
     * @param string $azureFileShareName Name of the file share
     */
    public function __construct(
        protected string $azureStorageConnectionString,
        protected string $azureFileShareName
    ) {
    }

    /**
     * @return string
     */
    public function getAzureStorageConnectionString(): string
    {
        return $this->azureStorageConnectionString;
    }

    /**
     * @return string
     */
    public function getAzureFileShareName(): string
    {
        return $this->azureFileShareName;
    }
}
