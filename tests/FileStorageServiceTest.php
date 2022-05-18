<?php

namespace BayWaReLusy\FileStorageTools\Test;

use BayWaReLusy\FileStorageTools\Adapter\FileStorageAdapterInterface;
use BayWaReLusy\FileStorageTools\Exception\DirectoryAlreadyExistsException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;
use BayWaReLusy\FileStorageTools\FileStorageService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    public function testCreateDirectory_DirectoryAlreadyExists(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('/test-directory')
            ->will($this->throwException(new DirectoryAlreadyExistsException()));

        $this->expectException(DirectoryAlreadyExistsException::class);

        $this->instance->createDirectory('/test-directory');
    }

    public function testCreateDirectory_ParentNotFound(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('/parent-directory/sub-directory')
            ->will($this->throwException(new ParentNotFoundException()));

        $this->expectException(ParentNotFoundException::class);

        $this->instance->createDirectory('/parent-directory/sub-directory');
    }

    public function testCreateDirectory_UnknownError(): void
    {
        $this->adapterMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('/test-directory')
            ->will($this->throwException(new UnknownErrorException()));

        $this->expectException(UnknownErrorException::class);

        $this->instance->createDirectory('/test-directory');
    }
}
