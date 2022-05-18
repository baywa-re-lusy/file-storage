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
data "azurerm_storage_account_sas" "sas" {
  connection_string = azurerm_storage_account.file_storage.primary_connection_string
  https_only        = true
  signed_version    = "2021-06-08"

  resource_types {
    service   = true
    container = false
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

output "file_storage_sas_connection_string" {
  value = "FileEndpoint=https://${azurerm_storage_account.file_storage.name}.file.core.windows.net/;SharedAccessSignature=${data.azurerm_storage_account_sas.sas.sas}"
  sensitive = true
}
```

Once the infrastructure is updated, retrieve the SAS Connection string with:
```shell
terraform output file_storage_sas_connection_string
```

## Usage

Currently, this library only supports Azure File Storage. However, it uses an Adapter pattern to allow adding other vendors easily.

```php
use BayWaReLusy\FileStorageTools\FileStorageToolsConfig;
use BayWaReLusy\FileStorageTools\FileStorageTools;
use BayWaReLusy\FileStorageTools\FileStorageService;
use BayWaReLusy\FileStorageTools\Adapter\AwsSqsAdapter;

$fileStorageToolsConfig = new FileStorageToolsConfig($azureSasConnectionString, $azureFileShareName);
$fileStorageTools       = new FileStorageTools($fileStorageToolsConfig);
$fileStorageService     = $fileStorageTools->get(FileStorageService::class);
$fileStorageService->setAdapter($emailTools->get(AzureFileStorageAdapter::class));
```

Optionally, you can include then the FileStorage Client into your Service Manager:

```php
$sm->setService(FileStorageTools::class, $fileStorageTools);
```
