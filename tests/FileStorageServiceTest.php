<?php

namespace BayWaReLusy\FileStorage\Test;

use BayWaReLusy\FileStorage\Adapter\FileStorageAdapterInterface;
use BayWaReLusy\FileStorage\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorage\Exception\DirectoryNotEmptyException;
use BayWaReLusy\FileStorage\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorage\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorage\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorage\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorage\Exception\UnknownErrorException;
use BayWaReLusy\FileStorage\FileStorageService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Throwable;

class FileStorageServiceTest extends TestCase
{
    protected FileStorageService $instance;
    protected MockObject $adapterMock;

    public function setUp(): void
    {
        $this->adapterMock = $this->createMock(FileStorageAdapterInterface::class);

        $this->instance = new FileStorageService($this->adapterMock);
    }

    public function testCreateDirectory(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('/test-directory');

        $this->instance->createDirectory('/test-directory');
    }

    public function dataProvider_testCreateDirectory_Exceptions(): array
    {
        return
            [
                [ParentNotFoundException::class],
                [UnknownErrorException::class],
            ];
    }

    /**
     * @dataProvider dataProvider_testCreateDirectory_Exceptions
     */
    public function testCreateDirectory_Exceptions(string $exceptionClassName): void
    {
        /** @var Throwable $exception */
        $exception = new $exceptionClassName();

        $this->adapterMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('/test-directory')
            ->will($this->throwException($exception));

        $this->expectException($exceptionClassName);

        $this->instance->createDirectory('/test-directory');
    }

    public function testDeleteDirectory(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('deleteDirectory')
            ->with('/test-directory');

        $this->instance->deleteDirectory('/test-directory');
    }

    public function dataProvider_testDeleteDirectory_Exceptions(): array
    {
        return
            [
                [DirectoryDoesntExistsException::class],
                [DirectoryNotEmptyException::class],
                [UnknownErrorException::class],
            ];
    }

    /**
     * @dataProvider dataProvider_testDeleteDirectory_Exceptions
     */
    public function testDeleteDirectory_Exceptions(string $exceptionClassName): void
    {
        /** @var Throwable $exception */
        $exception = new $exceptionClassName();

        $this->adapterMock
            ->expects($this->once())
            ->method('deleteDirectory')
            ->with('/test-directory')
            ->will($this->throwException($exception));

        $this->expectException($exceptionClassName);

        $this->instance->deleteDirectory('/test-directory');
    }

    public function testUploadFile(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('uploadFile')
            ->with('/tmp/test-directory', 'file.txt');

        $this->instance->uploadFile('/tmp/test-directory', 'file.txt');
    }

    public function dataProvider_testUploadFile_Exceptions(): array
    {
        return
            [
                [LocalFileNotFoundException::class],
                [FileCouldNotBeOpenedException::class],
            ];
    }

    /** @dataProvider dataProvider_testUploadFile_Exceptions */
    public function testUploadFile_Exceptions(string $exceptionClassName): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('uploadFile')
            ->with('/tmp/test-directory', 'file.txt')
            ->willThrowException(new $exceptionClassName());

        $this->expectException($exceptionClassName);

        $this->instance->uploadFile('/tmp/test-directory', 'file.txt');
    }

    public function testDeleteFile(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('deleteFile')
            ->with('/tmp/test-directory/file.txt');

        $this->instance->deleteFile('/tmp/test-directory/file.txt');
    }

    public function dataProvider_testDeleteFile_Exceptions(): array
    {
        return
            [
                [RemoteFileDoesntExistException::class],
                [UnknownErrorException::class],
            ];
    }

    /** @dataProvider dataProvider_testDeleteFile_Exceptions */
    public function testDeleteFile_Exceptions(string $exceptionClassName): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('deleteFile')
            ->with('/tmp/test-directory/file.txt')
            ->willThrowException(new $exceptionClassName());

        $this->expectException($exceptionClassName);

        $this->instance->deleteFile('/tmp/test-directory/file.txt');
    }

    public function testListFilesInDirectory(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('listFilesInDirectory')
            ->with('/tmp/test-directory', true);

        $this->instance->listFilesInDirectory('/tmp/test-directory');
    }

    public function dataProvider_testListFilesInDirectory_Exceptions(): array
    {
        return
            [
                [DirectoryDoesntExistsException::class],
                [UnknownErrorException::class],
            ];
    }

    /** @dataProvider dataProvider_testListFilesInDirectory_Exceptions */
    public function testListFilesInDirectory_Exceptions($exceptionClassName): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('listFilesInDirectory')
            ->with('/tmp/test-directory', true)
            ->will($this->throwException(new $exceptionClassName()));

        $this->expectException($exceptionClassName);

        $this->instance->listFilesInDirectory('/tmp/test-directory');
    }

    public function testGetPublicFileUrl(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('getPublicFileUrl')
            ->with('file.jpg');

        $this->instance->getPublicFileUrl('file.jpg');
    }

    public function dataProvider_testGetPublicFileUrl_Exceptions(): array
    {
        return
            [
                [RemoteFileDoesntExistException::class],
                [UnknownErrorException::class],
            ];
    }

    /** @dataProvider dataProvider_testGetPublicFileUrl_Exceptions */
    public function testGetPublicFileUrl_Exceptions(string $exceptionClassName): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('getPublicFileUrl')
            ->with('file.jpg')
            ->will($this->throwException(new $exceptionClassName()));

        $this->expectException($exceptionClassName);

        $this->instance->getPublicFileUrl('file.jpg');
    }
}
