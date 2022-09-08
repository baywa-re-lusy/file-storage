<?php

namespace BayWaReLusy\FileStorageTools\Adapter;

use BayWaReLusy\FileStorageTools\FileStorageToolsConfig;
use Laminas\ServiceManager\Factory\FactoryInterface;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use Psr\Container\ContainerInterface;

class AzureBlobAdapterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /** @var FileStorageToolsConfig $config */
        $config = $container->get(FileStorageToolsConfig::class);

        return new AzureBlobAdapter(
            BlobRestProxy::createBlobService(sprintf(
                "BlobEndpoint=https://%s.blob.core.windows.net/;SharedAccessSignature=%s",
                $config->getAzureStorageAccountName(),
                $config->getAzureSharedAccessSignature()
            )),
            $config->getAzureSharedAccessSignature(),
            $config->getAzureStorageAccountName()
        );
    }
}
