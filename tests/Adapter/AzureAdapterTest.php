<?php

namespace BayWaReLusy\FileStorageTools\Test\Adapter;

use BayWaReLusy\FileStorageTools\Adapter\AzureAdapter;
use BayWaReLusy\FileStorageTools\Exception\DirectoryAlreadyExistsException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;
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

    public function testCreateDirectory(): void
    {
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('file-share', '/test-directory');

        $this->instance->createDirectory('/test-directory');
    }

    public function testCreateDirectory_DirectoryAlreadyExists(): void
    {
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('file-share', '/test-directory')
            ->will($this->throwException(new DirectoryAlreadyExistsException()));

        $this->expectException(DirectoryAlreadyExistsException::class);

        $this->instance->createDirectory('/test-directory');
    }

    public function testCreateDirectory_ParentNotFound(): void
    {
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('file-share', '/parent-directory/sub-directory')
            ->will($this->throwException(new ParentNotFoundException()));

        $this->expectException(ParentNotFoundException::class);

        $this->instance->createDirectory('/parent-directory/sub-directory');
    }

    public function testCreateDirectory_UnknownError(): void
    {
        $this->fileStorageClientMock
            ->expects($this->once())
            ->method('createDirectory')
            ->with('file-share', '/test-directory')
            ->will($this->throwException(new UnknownErrorException()));

        $this->expectException(UnknownErrorException::class);

        $this->instance->createDirectory('/test-directory');
    }
}
