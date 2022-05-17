BayWa r.e. File Storage Tools
=============================

[![CircleCI](https://circleci.com/gh/baywa-re-lusy/file-storage/tree/main.svg?style=svg)](https://circleci.com/gh/baywa-re-lusy/file-storage/tree/main)

## Installation

To install the File Storage tools, you will need [Composer](http://getcomposer.org/) in your project:

```bash
composer require baywa-re-lusy/file-storage
```

## Usage

Currently, this library only supports Azure File Storage. However, it uses an Adapter pattern to allow adding other vendors easily.

```php
use BayWaReLusy\FileStorageTools\FileStorageToolsConfig;
use BayWaReLusy\FileStorageTools\FileStorageTools;
use BayWaReLusy\FileStorageTools\FileStorageService;
use BayWaReLusy\FileStorageTools\Adapter\AwsSqsAdapter;

$fileStorageToolsConfig = new FileStorageToolsConfig($azureStorageConnectionString, $azureFileShareName);
$fileStorageTools       = new FileStorageTools($fileStorageToolsConfig);
$fileStorageService     = $fileStorageTools->get(FileStorageService::class);
$fileStorageService->setAdapter($emailTools->get(AzureFileStorageAdapter::class));
```

Optionally, you can include then the FileStorage Client into your Service Manager:

```php
$sm->setService(FileStorageTools::class, $fileStorageTools);
```
