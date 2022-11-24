<?php

namespace BayWaReLusy\FileStorage\Adapter;

use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AzureBlobAdapterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
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
