<?php

namespace BayWaReLusy\FileStorage\Test\Adapter;

use BayWaReLusy\FileStorage\Adapter\AzureBlobAdapter;
use BayWaReLusy\FileStorage\Exception\ContainerNotSetException;
use BayWaReLusy\FileStorage\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorage\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorage\Exception\UnknownErrorException;
use GuzzleHttp\Psr7\Response;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsResult;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use phpmock\functions\FixedValueFunction;
use phpmock\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use MicrosoftAzure\Storage\Blob\BlobRestProxy as BlobStorageClient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AzureBlobAdapterTest extends TestCase
{
    protected AzureBlobAdapter $instance;
    protected MockObject $blobStorageClientMock;

    public function setUp(): void
    {
        $this->blobStorageClientMock = $this->createMock(BlobStorageClient::class);
        $this->instance = new AzureBlobAdapter('storage-account', 'container', 'sas');

        $adapter = new \ReflectionClass(AzureBlobAdapter::class);
        $blobStorageClient = $adapter->getProperty('blobStorageClient');
        $blobStorageClient->setAccessible(true);
        $blobStorageClient->setValue($this->instance, $this->blobStorageClientMock);
    }

    public function dataProvider_testCreateDirectory(): array
    {
        return
            [
                ['/test-directory'],
                ['test-directory'],
            ];
    }

    /** @dataProvider dataProvider_testCreateDirectory */
    public function testCreateDirectory(string $directory): void
    {
        $this->blobStorageClientMock
            ->expects($this->once())
            ->method('createContainer')
            ->with('test-directory');

        $this->instance->createDirectory($directory);
    }

    public function testCreateDirectory_ContainerNotSet(): void
    {
        $adapter = new \ReflectionClass(AzureBlobAdapter::class);
        $containerName = $adapter->getProperty('containerName');
        $containerName->setAccessible(true);
        $containerName->setValue($this->instance, '');

        $this->expectException(ContainerNotSetException::class);
        $this->instance->createDirectory('test-directory');
    }

    public function dataProvider_testCreateDirectory_Exceptions(): array
    {
        $directoryAlreadyExistsException = new ServiceException(new Response());
        $reflectionException             = new ReflectionClass(ServiceException::class);
        $message                         = $reflectionException->getProperty('message');
        $message->setAccessible(true);
        $message->setValue($directoryAlreadyExistsException, '...<Code>ContainerAlreadyExists</Code>...');

        $parentNotFoundException = new ServiceException(new Response());
        $reflectionException     = new ReflectionClass(ServiceException::class);
        $message                 = $reflectionException->getProperty('message');
        $message->setAccessible(true);

        return
            [
                [$directoryAlreadyExistsException],
                [$this->createMock(ServiceException::class), UnknownErrorException::class],
            ];
    }
    /** @dataProvider dataProvider_testCreateDirectory_Exceptions */
    public function testCreateDirectory_Exceptions(ServiceException $e, string $exceptionClassName = null): void
    {
        $this->blobStorageClientMock
            ->expects($this->once())
            ->method('createContainer')
            ->with('test-directory')
            ->will($this->throwException($e));

        if (!is_null($exceptionClassName)) {
            $this->expectException($exceptionClassName);
        }

        $this->instance->createDirectory('test-directory');
    }

    public function testUploadFile(): void
    {
        $filePointer = fopen(__DIR__ . '/files/test.txt', 'r');
        $builderFopen = new MockBuilder();
        $builderFopen
            ->setNamespace('BayWaReLusy\FileStorage\Adapter')
            ->setName('fopen')
            ->setFunctionProvider(new FixedValueFunction($filePointer));
        $mockFopen = $builderFopen->build();
        $mockFopen->enable();
        $this->blobStorageClientMock
            ->expects($this->once())
            ->method('createBlockBlob')
            ->with('container', 'dir/test2.txt', $filePointer);
        $this->instance->uploadFile(__DIR__ . '/files/test.txt', 'dir/test2.txt');
        $mockFopen->disable();
    }

    public function testUploadFile_ContainerNotSet(): void
    {
        $adapter = new \ReflectionClass(AzureBlobAdapter::class);
        $containerName = $adapter->getProperty('containerName');
        $containerName->setAccessible(true);
        $containerName->setValue($this->instance, '');

        $this->expectException(ContainerNotSetException::class);
        $this->instance->uploadFile(__DIR__ . '/files/test.txt', 'dir/test2.txt');
    }

    public function dataProvider_testDeleteFile(): array
    {
        return
            [
                ['/dir/test.txt'],
                ['dir/test.txt'],
            ];
    }

    /** @dataProvider dataProvider_testDeleteFile */
    public function testDeleteFile($file): void
    {
        $this->blobStorageClientMock
            ->expects($this->once())
            ->method('deleteBlob')
            ->with(dirname($file), basename($file));

        $this->instance->deleteFile($file);
    }

    public function testDeleteFile_ContainerNotSet(): void
    {
        $adapter = new \ReflectionClass(AzureBlobAdapter::class);
        $containerName = $adapter->getProperty('containerName');
        $containerName->setAccessible(true);
        $containerName->setValue($this->instance, '');

        $this->expectException(ContainerNotSetException::class);
        $this->instance->deleteFile('file-to-delete');
    }

    public function dataProvider_testDeleteFile_Exceptions(): array
    {
        $serviceException    = new ServiceException(new Response());
        $reflectionException = new ReflectionClass(ServiceException::class);
        $message             = $reflectionException->getProperty('message');
        $message->setAccessible(true);
        $message->setValue($serviceException, '...<Code>BlobNotFound</Code>...');

        return
            [
                [$serviceException, RemoteFileDoesntExistException::class],
                [$this->createMock(ServiceException::class), UnknownErrorException::class],
            ];
    }
    /** @dataProvider dataProvider_testDeleteFile_Exceptions */
    public function testDeleteFile_Exceptions(ServiceException $e, string $exceptionClassName): void
    {
        $this->blobStorageClientMock
            ->expects($this->once())
            ->method('deleteBlob')
            ->with( 'dir', 'test.txt')
            ->will($this->throwException($e));

        $this->expectException($exceptionClassName);

        $this->instance->deleteFile('dir/test.txt');
    }

    public function dataProvider_testListFilesInDirectory(): array
    {
        return
            [
                ['dirA/dirB'],
                ['/dirA/dirB'],
            ];
    }

    /** @dataProvider dataProvider_testListFilesInDirectory */
    public function testListFilesInDirectory_IncludeDirectories(string $directory): void
    {
        $file1 = new Blob();
        $file1->setName('file1.jpg');
        $file2 = new Blob();
        $file2->setName('file2.jpg');

        $result         = new ListBlobsResult();
        $class          = new ReflectionClass(ListBlobsResult::class);
        $setFiles = $class->getMethod('setBlobs');
        $setFiles->setAccessible(true);
        $setFiles->invoke($result, [$file1, $file2]);

        $this->blobStorageClientMock
            ->expects($this->once())
            ->method('listBlobs')
            ->with('dirA/dirB')
            ->will($this->returnValue($result));

        $files = $this->instance->listFilesInDirectory('dirA/dirB');

        $this->assertContains('file1.jpg', $files);
        $this->assertContains('file2.jpg', $files);
        $this->assertCount(2, $files);
    }

    public function testListFilesInDirectory_ContainerNotSet(): void
    {
        $adapter = new \ReflectionClass(AzureBlobAdapter::class);
        $containerName = $adapter->getProperty('containerName');
        $containerName->setAccessible(true);
        $containerName->setValue($this->instance, '');

        $this->expectException(ContainerNotSetException::class);
        $this->instance->listFilesInDirectory('directory');
    }

    public function dataProvider_testListFilesInDirectory_Exceptions(): array
    {
        $serviceException    = new ServiceException(new Response());
        $reflectionException = new ReflectionClass(ServiceException::class);
        $message             = $reflectionException->getProperty('message');
        $message->setAccessible(true);
        $message->setValue($serviceException, '...<Code>ContainerNotFound</Code>...');

        return
            [
                [$serviceException, DirectoryDoesntExistsException::class],
                [$this->createMock(ServiceException::class), UnknownErrorException::class],
            ];
    }

    /** @dataProvider dataProvider_testListFilesInDirectory_Exceptions */
    public function testListFilesInDirectory_Exceptions(ServiceException $e, string $exceptionClassName): void
    {
        $this->blobStorageClientMock
            ->expects($this->once())
            ->method('listBlobs')
            ->with('dirA/dirB')
            ->will($this->throwException($e));

        $this->expectException($exceptionClassName);

        $this->instance->listFilesInDirectory('dirA/dirB');
    }

    public function dataProvider_testGetPublicFileUrl(): array
    {
        return
            [
                ['dirA/dirB/file1.jpg'],
                ['/dirA/dirB/file1.jpg'],
            ];
    }

    /** @dataProvider dataProvider_testGetPublicFileUrl */
    public function testGetPublicFileUrl($file): void
    {
        $this->blobStorageClientMock
            ->expects($this->once())
            ->method('getBlobUrl')
            ->with('dirA/dirB', 'file1.jpg')
            ->will($this->returnValue("https://storage-account.blob.core.windows.net/dirA/dirB/file1.jpg"));

        $this->assertEquals(
            'https://storage-account.blob.core.windows.net/dirA/dirB/file1.jpgsas',
            $this->instance->getPublicFileUrl($file)
        );
    }

    public function testGetPublicFileUrl_ContainerNotSet(): void
    {
        $adapter = new \ReflectionClass(AzureBlobAdapter::class);
        $containerName = $adapter->getProperty('containerName');
        $containerName->setAccessible(true);
        $containerName->setValue($this->instance, '');

        $this->expectException(ContainerNotSetException::class);
        $this->instance->getPublicFileUrl('path/to/file');
    }

    public function dataProvider_testGetPublicFileUrl_Exceptions(): array
    {
        $serviceException    = new ServiceException(new Response());
        $reflectionException = new ReflectionClass(ServiceException::class);
        $message             = $reflectionException->getProperty('message');
        $message->setAccessible(true);
        $message->setValue($serviceException, '...<Code>BlobNotFound</Code>...');
        $containerNotFound = new ServiceException(new Response());
        $reflectionException = new ReflectionClass(ServiceException::class);
        $message                 = $reflectionException->getProperty('message');
        $message->setAccessible(true);
        $message->setValue($containerNotFound, '...<Code>ContainerNotFound</Code>...');


        return
            [
                [$serviceException, RemoteFileDoesntExistException::class],
                [$containerNotFound, DirectoryDoesntExistsException::class],
                [$this->createMock(ServiceException::class), UnknownErrorException::class],
            ];
    }

    /** @dataProvider dataProvider_testGetPublicFileUrl_Exceptions */
    public function testGetPublicFileUrl_Exceptions(ServiceException $e, string $exceptionClassName): void
    {
        $this->blobStorageClientMock
            ->expects($this->once())
            ->method('getBlobUrl')
            ->with('.', 'file.jpg')
            ->will($this->throwException($e));

        $this->expectException($exceptionClassName);

        $this->instance->getPublicFileUrl('file.jpg');
    }
}
