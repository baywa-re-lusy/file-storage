<?php

/**
 * AzureAdapterFactory.php
 *
 * @date      17.05.2022
 * @author    Pascal Paulis <pascal.paulis@baywa-re.com>
 * @file      AzureAdapterFactory.php
 * @copyright Copyright (c) BayWa r.e. - All rights reserved
 * @license   Unauthorized copying of this source code, via any medium is strictly
 *            prohibited, proprietary and confidential.
 */

namespace BayWaReLusy\FileStorageTools\Adapter;

use BayWaReLusy\FileStorageTools\FileStorageToolsConfig;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use MicrosoftAzure\Storage\File\FileRestProxy;

/**
 * Class AzureAdapterFactory
 *
 * @package     BayWaReLusy
 * @author      Pascal Paulis <pascal.paulis@baywa-re.com>
 * @copyright   Copyright (c) BayWa r.e. - All rights reserved
 * @license     Unauthorized copying of this source code, via any medium is strictly
 *              prohibited, proprietary and confidential.
 */
class AzureAdapterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var FileStorageToolsConfig $config */
        $config = $container->get(FileStorageToolsConfig::class);

        return new AzureAdapter(
            FileRestProxy::createFileService(sprintf(
                "FileEndpoint=https://%s.file.core.windows.net/;SharedAccessSignature=%s",
                $config->getAzureStorageAccountName(),
                $config->getAzureSharedAccessSignature()
            )),
            $config->getAzureFileShareName(),
            $config->getAzureSharedAccessSignature(),
            $config->getAzureStorageAccountName()
        );
    }
}
