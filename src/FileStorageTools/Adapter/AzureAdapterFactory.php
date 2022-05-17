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

use BayWaReLusy\FileStorageTools\FileServiceToolsConfig;
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
        /** @var FileServiceToolsConfig $config */
        $config = $container->get(FileServiceToolsConfig::class);

        return new AzureAdapter(
            FileRestProxy::createFileService($config->getAzureStorageConnectionString()),
            $config->getAzureFileShareName()
        );
    }
}
