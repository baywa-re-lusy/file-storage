<?php

namespace BayWaReLusy\FileStorageTools\Test\Adapter;

use BayWaReLusy\FileStorageTools\Adapter\LocalAdapter;
use phpmock\functions\FixedValueFunction;
use phpmock\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalAdapterTest extends TestCase
{
    protected LocalAdapter $instance;
    protected MockObject $fileStorageClientMock;

    public function setUp(): void
    {
        $this->instance = new LocalAdapter();
    }
    public function testCreateDirectory()
    {
        $builderMkDir = new MockBuilder();
        $builderMkDir
            ->setName('mkdir')
            ->setNamespace('BayWaReLusy\FileStorageTools\Adapter')
            ->setFunction(function ($directory) {
                $this->assertEquals('testdir', $directory);
                return true;
            });

        $mkDirMock = $builderMkDir->build();
        $mkDirMock->enable();
        $this->instance->createDirectory("testdir");
        $mkDirMock->disable();
    }
    public function testDeleteDirectory()
    {
        $builderRmDir = new MockBuilder();
        $builderRmDir->setName('rmdir')
            ->setNamespace('BayWaReLusy\FileStorageTools\Adapter')
            ->setFunction(function($path) {
                $this->assertEquals("testdir", $path);
                return true;
            });
        $rmDirMock = $builderRmDir->build();
        $rmDirMock->enable();
        $this->instance->deleteDirectory("testdir");
        $rmDirMock->disable();
    }
    public function testFileUpload()
    {
        $this->instance->uploadFile(__DIR__ , '/files/test.txt');
        self::assertTrue(file_exists(LocalAdapter::REMOTE_DIRECTORY . '/files/test.txt'));
    }
    public function testListFiles()
    {
        $result = $this->instance->listFilesInDirectory(__DIR__ . "/files");
        self::assertEquals(count($result), 2);
    }
    public function testPublicUrl()
    {
        $url = $this->instance->getPublicFileUrl(__DIR__ . '/files/test.txt');
        $this->assertEquals($url, "http://definitelynotavirus.ru". __DIR__ . '/files/test.txt');
    }
}