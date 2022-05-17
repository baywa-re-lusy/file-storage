<?php

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
                    //AwsSqsAdapter::class => AwsSqsAdapterFactory::class,
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
