<?php

namespace BayWaReLusy\FileStorageTools\Test\Adapter;

use BayWaReLusy\FileStorageTools\Adapter\AzureBlobAdapter;
use BayWaReLusy\FileStorageTools\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;
use GuzzleHttp\Psr7\Response;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsResult;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\File\Models\File;
use phpmock\functions\FixedValueFunction;
use phpmock\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use MicrosoftAzure\Storage\Blob\BlobRestProxy as FileStorageClient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AzureBlobAdapterTest extends TestCase
{
    protected AzureBlobAdapter $instance;
    protected MockObject $fileStorageClientMock;

    public function setUp(): void
    {
        $this->fileStorageClientMock = $this->createMock(FileStorageClient::class);

        $this->instance = new AzureBlobAdapter(
            $this->fileStorageClientMock,
            'sas',
            'storage-account'
        );
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
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('createContainer')
            ->with('test-directory');

        $this->instance->createDirectory($directory);
    }
    public function dataProvider_testCreateDirectory_Exceptions(): array
    {
        $directoryAlreadyExistsException = new ServiceException(new Response());
        $reflectionException             = new ReflectionClass(ServiceException::class);
        $message                         = $reflectionException->getProperty('message');
        $message->setAccessible(true);
        $message->setValue($directoryAlreadyExistsException, '...<Code>ResourceAlreadyExists</Code>...');

        $parentNotFoundException = new ServiceException(new Response());
        $reflectionException     = new ReflectionClass(ServiceException::class);
        $message                 = $reflectionException->getProperty('message');
        $message->setAccessible(true);
        $message->setValue($parentNotFoundException, '...<Code>ParentNotFound</Code>...');

        return
            [
                [$directoryAlreadyExistsException],
                [$parentNotFoundException, ParentNotFoundException::class],
                [$this->createMock(ServiceException::class), UnknownErrorException::class],
            ];
    }
    /** @dataProvider dataProvider_testCreateDirectory_Exceptions */
    public function testCreateDirectory_Exceptions(ServiceException $e, string $exceptionClassName = null): void
    {
        $this->fileStorageClientMock
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
            ->setNamespace('BayWaReLusy\FileStorageTools\Adapter')
            ->setName('fopen')
            ->setFunctionProvider(new FixedValueFunction($filePointer));
        $mockFopen = $builderFopen->build();
        $mockFopen->enable();
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('createBlockBlob')
            ->with('dir1/dir2', 'test.txt', $filePointer);
        $this->instance->uploadFile('dir1/dir2', __DIR__ . '/files/test.txt');
        $mockFopen->disable();
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
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('deleteBlob')
            ->with(dirname($file), basename($file));

        $this->instance->deleteFile($file);
    }

    public function dataProvider_testDeleteFile_Exceptions(): array
    {
        $serviceException    = new ServiceException(new Response());
        $reflectionException = new ReflectionClass(ServiceException::class);
        $message             = $reflectionException->getProperty('message');
        $message->setAccessible(true);
        $message->setValue($serviceException, '...<Code>ResourceNotFound</Code>...');

        return
            [
                [$serviceException, RemoteFileDoesntExistException::class],
                [$this->createMock(ServiceException::class), UnknownErrorException::class],
            ];
    }
    /** @dataProvider dataProvider_testDeleteFile_Exceptions */
    public function testDeleteFile_Exceptions(ServiceException $e, string $exceptionClassName): void
    {
        $this->fileStorageClientMock
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
        $file1 = new File();
        $file1->setName('file1.jpg');
        $file2 = new File();
        $file2->setName('file2.jpg');

        $result         = new ListBlobsResult();
        $class          = new ReflectionClass(ListBlobsResult::class);
        $setFiles = $class->getMethod('setBlobs');
        $setFiles->setAccessible(true);
        $setFiles->invoke($result, [$file1, $file2]);

        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('listBlobs')
            ->with('dirA/dirB')
            ->will($this->returnValue($result));

        $files = $this->instance->listFilesInDirectory('dirA/dirB');

        $this->assertContains('file1.jpg', $files);
        $this->assertContains('file2.jpg', $files);
        $this->assertCount(2, $files);
    }

    public function dataProvider_testListFilesInDirectory_Exceptions(): array
    {
        $serviceException    = new ServiceException(new Response());
        $reflectionException = new ReflectionClass(ServiceException::class);
        $message             = $reflectionException->getProperty('message');
        $message->setAccessible(true);
        $message->setValue($serviceException, '...<Code>ResourceNotFound</Code>...');

        return
            [
                [$serviceException, DirectoryDoesntExistsException::class],
                [$this->createMock(ServiceException::class), UnknownErrorException::class],
            ];
    }

    /** @dataProvider dataProvider_testListFilesInDirectory_Exceptions */
    public function testListFilesInDirectory_Exceptions(ServiceException $e, string $exceptionClassName): void
    {
        $this->fileStorageClientMock
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
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('getBlobUrl')
            ->with('dirA/dirB', 'file1.jpg')
            ->will($this->returnValue("https://storage-account.blob.core.windows.net/dirA/dirB/file1.jpg"));

        $this->assertEquals(
            'https://storage-account.blob.core.windows.net/dirA/dirB/file1.jpgsas',
            $this->instance->getPublicFileUrl($file)
        );
    }

    public function dataProvider_testGetPublicFileUrl_Exceptions(): array
    {
        $serviceException    = new ServiceException(new Response());
        $reflectionException = new ReflectionClass(ServiceException::class);
        $message             = $reflectionException->getProperty('message');
        $message->setAccessible(true);
        $message->setValue($serviceException, '...<Code>ResourceNotFound</Code>...');

        return
            [
                [$serviceException, RemoteFileDoesntExistException::class],
                [$this->createMock(ServiceException::class), UnknownErrorException::class],
            ];
    }

    /** @dataProvider dataProvider_testGetPublicFileUrl_Exceptions */
    public function testGetPublicFileUrl_Exceptions(ServiceException $e, string $exceptionClassName): void
    {
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('getBlobUrl')
            ->with('.', 'file.jpg')
            ->will($this->throwException($e));

        $this->expectException($exceptionClassName);

        $this->instance->getPublicFileUrl('file.jpg');
    }
}