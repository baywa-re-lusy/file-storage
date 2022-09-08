<?php
/**
 * FileStorageToolsConfig.php
 *
 * @date        17.05.2022
 * @author      Pascal Paulis <pascal.paulis@baywa-re.com>
 * @file        FileStorageToolsConfig.php
 * @copyright   Copyright (c) BayWa r.e. - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */

namespace BayWaReLusy\FileStorageTools;

/**
 * Class FileStorageToolsConfig
 *
 * Config object for FileServiceTools
 *
 * @package     BayWaReLusy
 * @author      Pascal Paulis <pascal.paulis@baywa-re.com>
 * @copyright   Copyright (c) BayWa r.e. - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */
class FileStorageToolsConfig
{
    /**
     * @param string $azureSharedAccessSignature Shared Access Signature
     * @param string $azureStorageAccountName Name of the Storage account
     * @param string $azureFileShareName Name of the file share
     */
    public function __construct(
        protected string $azureSharedAccessSignature,
        protected string $azureStorageAccountName,
        protected string $azureFileShareName
    ) {
    }

    /**
     * @return string
     */
    public function getAzureSharedAccessSignature(): string
    {
        return $this->azureSharedAccessSignature;
    }

    /**
     * @return string
     */
    public function getAzureStorageAccountName(): string
    {
        return $this->azureStorageAccountName;
    }

    /**
     * @return string
     */
    public function getAzureFileShareName(): string
    {
        return $this->azureFileShareName;
    }
}
