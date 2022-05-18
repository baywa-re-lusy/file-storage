<?php

namespace BayWaReLusy\FileStorageTools\Test;

use BayWaReLusy\FileStorageTools\Adapter\FileStorageAdapterInterface;
use BayWaReLusy\FileStorageTools\Exception\DirectoryAlreadyExistsException;
use BayWaReLusy\FileStorageTools\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorageTools\Exception\DirectoryNotEmptyException;
use BayWaReLusy\FileStorageTools\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;
use BayWaReLusy\FileStorageTools\FileStorageService;
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

        $this->instance = new FileStorageService();
        $this->instance->setAdapter($this->adapterMock);
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
                [DirectoryAlreadyExistsException::class],
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

    public function testUploadFile_LocalFileNotFound(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('uploadFile')
            ->with('/tmp/test-directory', 'file.txt')
            ->willThrowException(new LocalFileNotFoundException());

        $this->expectException(LocalFileNotFoundException::class);

        $this->instance->uploadFile('/tmp/test-directory', 'file.txt');
    }

    public function testUploadFile_FileCouldNotBeOpened(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('uploadFile')
            ->with('/tmp/test-directory', 'file.txt')
            ->willThrowException(new FileCouldNotBeOpenedException());

        $this->expectException(FileCouldNotBeOpenedException::class);

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

    public function testDeleteFile_RemoteFileDoesntExist(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('deleteFile')
            ->with('/tmp/test-directory/file.txt')
            ->willThrowException(new RemoteFileDoesntExistException());

        $this->expectException(RemoteFileDoesntExistException::class);

        $this->instance->deleteFile('/tmp/test-directory/file.txt');
    }

    public function testDeleteFile_UnknownError(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('deleteFile')
            ->with('/tmp/test-directory/file.txt')
            ->willThrowException(new UnknownErrorException());

        $this->expectException(UnknownErrorException::class);

        $this->instance->deleteFile('/tmp/test-directory/file.txt');
    }
}
