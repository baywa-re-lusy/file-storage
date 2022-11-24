BayWa r.e. File Storage Tools
=============================

[![CircleCI](https://circleci.com/gh/baywa-re-lusy/file-storage/tree/main.svg?style=svg)](https://circleci.com/gh/baywa-re-lusy/file-storage/tree/main)

## Installation

To install the File Storage tools, you will need [Composer](http://getcomposer.org/) in your project:

```bash
composer require baywa-re-lusy/file-storage
```

## Azure File Storage

A configured Azure File Storage is needed to use the `AzureAdapter`. It can be created using `terraform`:
```hcl
# Create the Storage Account
resource "azurerm_storage_account" "file_storage" {
  name                     = "storage-name"
  resource_group_name      = "resourge-group-name"
  location                 = "location"
  account_tier             = "Standard"
  account_replication_type = "GRS"
}

# Create a File Share inside the Storage Account
resource "azurerm_storage_share" "file_storage_share" {
  name                 = "my-file-share"
  storage_account_name = azurerm_storage_account.file_storage.name
  quota                = 1

  acl {
    id = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" # <=========== Generate a unique ID here

    access_policy {
      permissions = "rwdl"
      start       = "2022-05-17T00:00:00.0000000Z"
      expiry      = "2100-05-17T00:00:00.0000000Z"
    }
  }
}

# Generate Shared Access Signature
# the property "signed_version" can't be set (or must be an old one) and "tag" and "filter" must be set to false
# see https://github.com/hashicorp/terraform-provider-azurerm/issues/17558
data "azurerm_storage_account_sas" "sas" {
  connection_string = azurerm_storage_account.file_storage.primary_connection_string
  https_only        = true

  resource_types {
    service   = true
    container = true
    object    = true
  }

  services {
    blob  = false
    queue = false
    table = false
    file  = true
  }

  start  = "2022-05-17T00:00:00Z"
  expiry = "2024-05-17T00:00:00Z"

  permissions {
    read    = true
    write   = true
    delete  = true
    list    = true
    add     = false
    create  = true
    update  = false
    process = false
    tag     = false
    filter  = false
  }
}

output "azure_shared_access_signature" {
  value = data.azurerm_storage_account_sas.sas.sas
  sensitive = true
}
```

Once the infrastructure is updated, retrieve the SAS Connection string with:
```shell
terraform output azure_shared_access_signature
```

## Usage

Currently, this library supports Azure File Storage. However, it uses an Adapter pattern to allow adding other vendors easily.

```php
use BayWaReLusy\FileStorageTools\FileStorageToolsConfig;
use BayWaReLusy\FileStorageTools\FileStorageTools;
use BayWaReLusy\FileStorageTools\FileStorageService;
use BayWaReLusy\FileStorageTools\Adapter\AzureAdapter;

$fileStorageToolsConfig = new FileStorageToolsConfig(
    $azureSharedAccessSignature,
    $azureStorageAccountName,
    $azureFileShareName
);
$fileStorageTools   = new FileStorageTools($fileStorageToolsConfig);
$fileStorageService = $fileStorageTools->get(FileStorageService::class);
$fileStorageService->setAdapter($fileStorageTools->get(AzureAdapter::class));
```

It also includes an adapter for Blob storage on azure, it works with the same signature

```php
use BayWaReLusy\FileStorageTools\FileStorageToolsConfig;
use BayWaReLusy\FileStorageTools\FileStorageTools;
use BayWaReLusy\FileStorageTools\FileStorageService;
use BayWaReLusy\FileStorageTools\Adapter\AzureAdapter;

$fileStorageToolsConfig = new FileStorageToolsConfig(
    $azureSharedAccessSignature,
    $azureStorageAccountName,
    $azureFileShareName
);
$fileStorageTools   = new FileStorageTools($fileStorageToolsConfig);
$fileStorageService = $fileStorageTools->get(FileStorageService::class);
$fileStorageService->setAdapter($fileStorageTools->get(AzureBlobAdapter::class));
```

Optionally, you can include then the FileStorage Client into your Service Manager:

```php
$sm->setService(FileStorageTools::class, $fileStorageTools);
```

## Local adapter

This library also includes a Local adapter for testing purposes.

You have to supply the adapter with the path to the remote directory (it has to contain the 'public' folder) 
created beforehand and given the appropriate rights for writing as well as the API's URL.

```php
(new FileStorageService())->setAdapter(new LocalAdapter('/var/www/html/public/remote', 'https://my-api.api-url.com'));
```

