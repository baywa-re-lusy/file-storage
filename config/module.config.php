<?php

use BayWaReLusy\FileStorageTools\Adapter\AzureAdapter;
use BayWaReLusy\FileStorageTools\Adapter\AzureAdapterFactory;
use BayWaReLusy\FileStorageTools\Adapter\AzureBlobAdapter;
use BayWaReLusy\FileStorageTools\Adapter\AzureBlobAdapterFactory;
use BayWaReLusy\FileStorageTools\FileStorageService;

return [
    'service_manager' =>
        [
            'invokables' =>
                [
                    FileStorageService::class
                ],
            'factories' =>
                [
                    AzureAdapter::class => AzureAdapterFactory::class,
                    AzureBlobAdapter::class => AzureBlobAdapterFactory::class
                ],
            'abstract_factories' =>
                [
                ],
            'initializers' =>
                [
                ],
            'shared' =>
                [
                ]
        ]
];
