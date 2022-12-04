BayWa r.e. File Storage Tools
=============================

[![CircleCI](https://circleci.com/gh/baywa-re-lusy/file-storage/tree/main.svg?style=svg)](https://circleci.com/gh/baywa-re-lusy/file-storage/tree/main)

## Installation

To install the File Storage tools, you will need [Composer](http://getcomposer.org/) in your project:

```bash
composer require baywa-re-lusy/file-storage
```

## Azure Blob Storage

A configured Azure Blob Storage is needed to use the `AzureBlobAdapter`. It can be created using `terraform`:
```hcl
# Create the Storage Account
resource "azurerm_storage_account" "blob_storage" {
  name                     = "storage-name"
  resource_group_name      = "resourge-group-name"
  location                 = "location"
  account_tier             = "Standard"
  account_replication_type = "GRS"
}

# Create a Blob Share inside the Storage Account
resource "azurerm_storage_container" "container" {
  name                  = "content"
  storage_account_name  = azurerm_storage_account.blob_storage.name
  container_access_type = "private"
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
    blob  = true
    queue = false
    table = false
    file  = false
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

Currently, this library supports Azure Blob Storage. However, it uses an Adapter pattern to allow adding other
vendors easily.

### Azure Blob
```php
use BayWaReLusy\FileStorage\FileStorageService;
use BayWaReLusy\FileStorage\Adapter\AzureBlobAdapter;

$adapter            = new AzureBlobAdapter('<storage-account-name>', '<container name>', '<shared-access-signature>');
$fileStorageService = new FileStorageService($adapter);
```

### Local Storage

This library also includes a Local adapter for testing purposes.

You have to supply the adapter with the path to the remote directory (it has to contain the 'public' folder)
created beforehand and given the appropriate rights for writing as well as the API's URL.

```php
use BayWaReLusy\FileStorage\FileStorageService;
use BayWaReLusy\FileStorage\Adapter\LocalAdapter;

$adapter            = new LocalAdapter('/var/www/html/public/remote', 'https://my-api.api-url.com');
$fileStorageService = new FileStorageService($adapter);
```
