<?php

namespace BayWaReLusy\FileStorageTools\Test\Adapter;

use BayWaReLusy\FileStorageTools\Adapter\AzureAdapter;
use BayWaReLusy\FileStorageTools\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;
use GuzzleHttp\Psr7\Response;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Common\Models\Range;
use MicrosoftAzure\Storage\File\Models\Directory;
use MicrosoftAzure\Storage\File\Models\File;
use MicrosoftAzure\Storage\File\Models\GetFileResult;
use MicrosoftAzure\Storage\File\Models\ListDirectoriesAndFilesResult;
use phpmock\functions\FixedValueFunction;
use phpmock\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use MicrosoftAzure\Storage\File\FileRestProxy as FileStorageClient;
use ReflectionClass;

class AzureAdapterTest extends TestCase
{
    protected AzureAdapter $instance;
    protected MockObject $fileStorageClientMock;

    public function setUp(): void
    {
        $this->fileStorageClientMock = $this->createMock(FileStorageClient::class);

        $this->instance = new AzureAdapter(
            $this->fileStorageClientMock,
            'file-share',
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
            ->method('createDirectory')
            ->with('file-share', 'test-directory');

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
            ->method('createDirectory')
            ->with('file-share', 'test-directory')
            ->will($this->throwException($e));

        if (!is_null($exceptionClassName)) {
            $this->expectException($exceptionClassName);
        }

        $this->instance->createDirectory('test-directory');
    }

    public function testUploadFile_SmallerThan4MB(): void
    {
        // We need to mock the built-in functions filesize() & fopen()
        $builderFilesize = new MockBuilder();
        $builderFilesize
            ->setNamespace('BayWaReLusy\FileStorageTools\Adapter')
            ->setName('filesize')
            ->setFunctionProvider(new FixedValueFunction(4096 * 4096));
        $mockFilesize = $builderFilesize->build();
        $mockFilesize->enable();

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
            ->method('putFileRange')
            ->with(
                'file-share',
                'dir1/dir2/test.txt',
                $filePointer,
                self::callback(function ($param): bool {
                    return
                        $param instanceof Range &&
                        $param->getStart() === 0 &&
                        $param->getEnd() === 4096 * 4096 - 1;
                })
            );

        $this->instance->uploadFile('dir1/dir2', __DIR__ . '/files/test.txt');

        $mockFilesize->disable();
        $mockFopen->disable();
    }

    public function testUploadFile_LargerThan4MB(): void
    {
        // We need to mock the built-in functions filesize() & fopen()
        $builderFilesize = new MockBuilder();
        $builderFilesize
            ->setNamespace('BayWaReLusy\FileStorageTools\Adapter')
            ->setName('filesize')
            ->setFunctionProvider(new FixedValueFunction(4096 * 4096 + 1));
        $mockFilesize = $builderFilesize->build();
        $mockFilesize->enable();

        $filePointer = fopen(__DIR__ . '/files/test.txt', 'r');
        $builderFopen = new MockBuilder();
        $builderFopen
            ->setNamespace('BayWaReLusy\FileStorageTools\Adapter')
            ->setName('fopen')
            ->setFunctionProvider(new FixedValueFunction($filePointer));
        $mockFopen = $builderFopen->build();
        $mockFopen->enable();

        $this->fileStorageClientMock
            ->expects($this->exactly(2))
            ->method('putFileRange')
            ->withConsecutive(
                [
                    'file-share',
                    'dir1/dir2/test.txt',
                    $filePointer,
                    self::callback(function ($param): bool {
                        return
                            $param instanceof Range &&
                            $param->getStart() === 0 &&
                            $param->getEnd() === 4096 * 4096 - 1;
                    })
                ],
                [
                    'file-share',
                    'dir1/dir2/test.txt',
                    $filePointer,
                    self::callback(function ($param): bool {
                        return
                            $param instanceof Range &&
                            $param->getStart() === (4096 * 4096) &&
                            $param->getEnd() === (4096 * 4096);
                    })
                ]
            );

        $this->instance->uploadFile('dir1/dir2', __DIR__ . '/files/test.txt');

        $mockFilesize->disable();
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
            ->method('deleteFile')
            ->with('file-share', 'dir/test.txt');

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
            ->method('deleteFile')
            ->with('file-share', 'dir/test.txt')
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
        $dir1 = new Directory();
        $dir1->setName('dir1');
        $dir2 = new Directory();
        $dir2->setName('dir2');

        $file1 = new File();
        $file1->setName('file1.jpg');
        $file2 = new File();
        $file2->setName('file2.jpg');

        $result         = new ListDirectoriesAndFilesResult();
        $class          = new ReflectionClass(ListDirectoriesAndFilesResult::class);
        $setDirectories = $class->getMethod('setDirectories');
        $setDirectories->setAccessible(true);
        $setDirectories->invoke($result, [$dir1, $dir2]);
        $setFiles = $class->getMethod('setFiles');
        $setFiles->setAccessible(true);
        $setFiles->invoke($result, [$file1, $file2]);

        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('listDirectoriesAndFiles')
            ->with('file-share', 'dirA/dirB')
            ->will($this->returnValue($result));

        $files = $this->instance->listFilesInDirectory($directory);

        $this->assertContains('dir1', $files);
        $this->assertContains('dir2', $files);
        $this->assertContains('file1.jpg', $files);
        $this->assertContains('file2.jpg', $files);
        $this->assertCount(4, $files);
    }

    public function testListFilesInDirectory_ExcludeDirectories(): void
    {
        $file1 = new File();
        $file1->setName('file1.jpg');
        $file2 = new File();
        $file2->setName('file2.jpg');

        $result         = new ListDirectoriesAndFilesResult();
        $class          = new ReflectionClass(ListDirectoriesAndFilesResult::class);
        $setFiles = $class->getMethod('setFiles');
        $setFiles->setAccessible(true);
        $setFiles->invoke($result, [$file1, $file2]);

        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('listDirectoriesAndFiles')
            ->with('file-share', 'dirA/dirB')
            ->will($this->returnValue($result));

        $files = $this->instance->listFilesInDirectory('dirA/dirB', false);

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
            ->method('listDirectoriesAndFiles')
            ->with('file-share', 'dirA/dirB')
            ->will($this->throwException($e));

        $this->expectException($exceptionClassName);

        $this->instance->listFilesInDirectory('dirA/dirB', false);
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
            ->method('getFile')
            ->with('file-share', 'dirA/dirB/file1.jpg')
            ->will($this->returnValue(new GetFileResult()));

        $this->assertEquals(
            'https://storage-account.file.core.windows.net/file-share/dirA/dirB/file1.jpgsas&time=',
            substr($this->instance->getPublicFileUrl($file), 0, -10)
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
            ->method('getFile')
            ->with('file-share', 'file.jpg')
            ->will($this->throwException($e));

        $this->expectException($exceptionClassName);

        $this->instance->getPublicFileUrl('file.jpg');
    }
}
