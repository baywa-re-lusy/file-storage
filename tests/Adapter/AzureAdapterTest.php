<?php

namespace BayWaReLusy\FileStorageTools\Test\Adapter;

use BayWaReLusy\FileStorageTools\Adapter\AzureAdapter;
use BayWaReLusy\FileStorageTools\Exception\DirectoryAlreadyExistsException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;
use MicrosoftAzure\Storage\Common\Models\Range;
use phpmock\functions\FixedValueFunction;
use phpmock\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use MicrosoftAzure\Storage\File\FileRestProxy as FileStorageClient;

class AzureAdapterTest extends TestCase
{
    protected AzureAdapter $instance;
    protected MockObject $fileStorageClientMock;

    public function setUp(): void
    {
        $this->fileStorageClientMock = $this->createMock(FileStorageClient::class);

        $this->instance = new AzureAdapter(
            $this->fileStorageClientMock,
            'file-share'
        );
    }

    protected function dataProvider_testCreateDirectory(): array
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

    public function testCreateDirectory_DirectoryAlreadyExists(): void
    {
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('file-share', 'test-directory')
            ->will($this->throwException(new DirectoryAlreadyExistsException()));

        $this->expectException(DirectoryAlreadyExistsException::class);

        $this->instance->createDirectory('test-directory');
    }

    public function testCreateDirectory_ParentNotFound(): void
    {
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('file-share', 'parent-directory/sub-directory')
            ->will($this->throwException(new ParentNotFoundException()));

        $this->expectException(ParentNotFoundException::class);

        $this->instance->createDirectory('parent-directory/sub-directory');
    }

    public function testCreateDirectory_UnknownError(): void
    {
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('file-share', 'test-directory')
            ->will($this->throwException(new UnknownErrorException()));

        $this->expectException(UnknownErrorException::class);

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
                __DIR__ . '/files/test.txt',
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
                    __DIR__ . '/files/test.txt',
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
                    __DIR__ . '/files/test.txt',
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

    protected function dataProvider_testDeleteFile(): array
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

    public function testDeleteFile_RemoteFileDoesntExist(): void
    {
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('deleteFile')
            ->with('file-share', 'dir/test.txt')
            ->will($this->throwException(new RemoteFileDoesntExistException()));

        $this->expectException(RemoteFileDoesntExistException::class);

        $this->instance->deleteFile('dir/test.txt');
    }

    public function testDeleteFile_UnknownError(): void
    {
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('deleteFile')
            ->with('file-share', 'dir/test.txt')
            ->will($this->throwException(new UnknownErrorException()));

        $this->expectException(UnknownErrorException::class);

        $this->instance->deleteFile('dir/test.txt');
    }
}
