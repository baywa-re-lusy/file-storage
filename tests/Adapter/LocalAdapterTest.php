<?php

namespace BayWaReLusy\FileStorageTools\Test\Adapter;

use BayWaReLusy\FileStorageTools\Adapter\LocalAdapter;
use BayWaReLusy\FileStorageTools\Exception\DirectoryAlreadyExistsException;
use BayWaReLusy\FileStorageTools\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorageTools\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\RemoteFileDoesntExistException;
use Cassandra\Exception\AlreadyExistsException;
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
        $this->instance = new LocalAdapter(__DIR__ . '/remote');
    }
    public function testCreateDirectory()
    {
        $builderMkDir = new MockBuilder();
        $builderMkDir
            ->setName('mkdir')
            ->setNamespace('BayWaReLusy\FileStorageTools\Adapter')
            ->setFunction(function ($directory) {
                $this->assertEquals(__DIR__ . '/remote' . '/testdir', $directory);
                return true;
            });

        $mkDirMock = $builderMkDir->build();
        $mkDirMock->enable();
        $this->instance->createDirectory("/testdir");
        $mkDirMock->disable();
    }
    public function testDeleteDirectory()
    {
        $builderFileExists = new MockBuilder();
        $builderFileExists->setName('file_exists')
            ->setNamespace('BayWaReLusy\FileStorageTools\Adapter')
            ->setFunction(function(){return true;});
        $fileExistsMock = $builderFileExists->build();
        $builderRmDir = new MockBuilder();
        $builderRmDir->setName('rmdir')
            ->setNamespace('BayWaReLusy\FileStorageTools\Adapter')
            ->setFunction(function($path) {
                $this->assertEquals(__DIR__ . '/remote' . "/testdir", $path);
                return true;
            });
        $rmDirMock = $builderRmDir->build();
        $fileExistsMock->enable();
        $rmDirMock->enable();
        $this->instance->deleteDirectory("/testdir");
        $fileExistsMock->disable();
        $rmDirMock->disable();
    }
    public function testFileUpload()
    {
        $this->instance->uploadFile(__DIR__ , '/files/test.txt');
        self::assertTrue(file_exists(  __DIR__ . '/remote' . '/files/test.txt'));
    }
    public function testDeleteFile()
    {
        $this->instance->deleteFile('/files/test.txt');
        $this->assertFalse(file_exists(__DIR__ . '/remote' . '/files/file.txt'));
    }
    public function testListFiles()
    {
        $result = $this->instance->listFilesInDirectory("/files", false);
        self::assertEquals(count($result), 1);
        $result = $this->instance->listFilesInDirectory("/files",);
        self::assertEquals(count($result), 3);
    }
    public function testPublicUrl()
    {
        $url = $this->instance->getPublicFileUrl(__DIR__ . '/files/test.txt');
        $this->assertEquals($url, "http://definitelynotavirus.ru". __DIR__ . '/files/test.txt');
    }
    public function dataProvider_testCreationException(): array
    {
        return
        [
          ['/file/file/file', ParentNotFoundException::class],
          ['/files', DirectoryAlreadyExistsException::class]
        ];
    }
    /** @dataProvider dataProvider_testCreationException
     * @param string $path
     * @param class-string<\Exception> $exceptionName
     */
    public function testCreationException(string $path, string $exceptionName)
    {
        $this->expectException($exceptionName);
        $this->instance->createDirectory($path);
    }
    public function dataProvider_testUploadException(): array
    {
        return
            [
                ['/donotexists', LocalFileNotFoundException::class],
                ['/files/test.jpg', FileCouldNotBeOpenedException::class],
                ['/wrongFiles/file.txt', ParentNotFoundException::class]
            ];
    }
    /** @dataProvider dataProvider_testUploadException
     * @param string $path
     */
    public function testUploadException(string $path, string $exceptionName)
    {
        $this->expectException($exceptionName);
        $this->instance->uploadFile(__DIR__, $path);
    }
    public function dataProvider_testDeleteException(): array
    {
        return
            [
                ['/notexist/files.txt', DirectoryDoesntExistsException::class],
                ['/files/noexist.txt', RemoteFileDoesntExistException::class]
            ];
    }
//    /** @dataProvider dataProvider_testDeleteException
//     * @param string $path
//     * @param class-string<\Exception> $exceptionName
//     */
//    public function testDeleteException(string $path, string $exceptionName)
//    {
//        $this->expectException($exceptionName);
//        $this->instance->deleteFile($path);
//    }
}