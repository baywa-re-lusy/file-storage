<?php

namespace BayWaReLusy\FileStorage\Test\Adapter;

use BayWaReLusy\FileStorage\Adapter\LocalAdapter;
use BayWaReLusy\FileStorage\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorage\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorage\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorage\Exception\RemoteFileDoesntExistException;
use Exception;
use phpmock\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalAdapterTest extends TestCase
{
    protected LocalAdapter $instance;
    protected MockObject $fileStorageClientMock;

    public function setUp(): void
    {
        $this->instance = new LocalAdapter(__DIR__ . '/public/', 'testUrl');
    }

    public function dataProvider_testCreationDirectory(): array
    {
        return
            [
                ['/testdir'],
                ['testdir'],
            ];
    }

    /**
     * @dataProvider dataProvider_testCreationDirectory
     * @param string $path
     */
    public function testCreateDirectory(string $path)
    {
        $builderMkDir = new MockBuilder();
        $builderMkDir
            ->setName('mkdir')
            ->setNamespace('BayWaReLusy\FileStorage\Adapter')
            ->setFunction(function ($directory) {
                $this->assertEquals(__DIR__ . '/public' . '/testdir', $directory);
                return true;
            });
        $builderChmod = new MockBuilder();
        $builderChmod
            ->setName('chmod')
            ->setNamespace('BayWaReLusy\FileStorage\Adapter')
            ->setFunction(function () {
                return true;
            });

        $chmodMock = $builderChmod->build();
        $mkDirMock = $builderMkDir->build();
        $mkDirMock->enable();
        $chmodMock->enable();

        $this->instance->createDirectory($path);

        $chmodMock->disable();
        $mkDirMock->disable();
    }

    public function dataProvider_testDeleteDirectory(): array
    {
        return
            [
                ['/testdir'],
                ['testdir']
            ];
    }

    /**
     * @dataProvider dataProvider_testDeleteDirectory
     * @param string $path
     */
    public function testDeleteDirectory(string $path)
    {
        $builderFileExists = new MockBuilder();
        $builderFileExists->setName('file_exists')
            ->setNamespace('BayWaReLusy\FileStorage\Adapter')
            ->setFunction(function () {
                return true;
            });
        $fileExistsMock = $builderFileExists->build();

        $builderRmDir = new MockBuilder();
        $builderRmDir->setName('rmdir')
            ->setNamespace('BayWaReLusy\FileStorage\Adapter')
            ->setFunction(function ($path) {
                $this->assertEquals(__DIR__ . '/public' . "/testdir", $path);
                return true;
            });

        $rmDirMock = $builderRmDir->build();
        $fileExistsMock->enable();
        $rmDirMock->enable();

        $this->instance->deleteDirectory($path);

        $fileExistsMock->disable();
        $rmDirMock->disable();
    }

    public function testFileUpload()
    {
        $this->instance->uploadFile('/files', __DIR__ .'/files/test.txt');
        self::assertTrue(file_exists(__DIR__ . '/public' . '/files/test.txt'));
    }

    public function testPublicUrl()
    {
        $url = $this->instance->getPublicFileUrl('/files/test.txt');
        $this->assertEquals($url, "testUrl" . '/files/test.txt');
    }

    public function testDeleteFile()
    {
        $this->instance->deleteFile('/files/test.txt');
        $this->assertFalse(file_exists(__DIR__ . '/public' . '/files/test.txt'));
    }

    public function testListFilesWithDirectory()
    {
        $result = $this->instance->listFilesInDirectory('/files');
        self::assertEquals(3, count($result));
    }

    public function testListFilesWithoutDirectory()
    {
        $result = $this->instance->listFilesInDirectory("/files", false);
        self::assertEquals(1, count($result));
    }

    public function dataProvider_testCreationException(): array
    {
        return
        [
          ['/file/file/file', ParentNotFoundException::class],
          ['/file/file.txt', ParentNotFoundException::class],
        ];
    }

    /**
     * @dataProvider dataProvider_testCreationException
     * @param string $path
     * @param class-string<Exception> $exceptionName
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
                ['/donotexists', '/files', LocalFileNotFoundException::class],
                ['/wrongFiles/file.txt', '/wrongFiles' , ParentNotFoundException::class]
            ];
    }

    /**
     * @dataProvider dataProvider_testUploadException
     * @param string $path
     */
    public function testUploadException(string $path, string $dir, string $exceptionName)
    {
        $this->expectException($exceptionName);
        $this->instance->uploadFile($dir, __DIR__ . $path);
    }

    public function dataProvider_testDeleteException(): array
    {
        return
            [
                ['/notexist/files.txt', DirectoryDoesntExistsException::class],
                ['/files/noexist.txt', RemoteFileDoesntExistException::class]
            ];
    }

    /**
     * @dataProvider dataProvider_testDeleteException
     * @param string $path
     * @param class-string<Exception> $exceptionName
     */
    public function testDeleteException(string $path, string $exceptionName)
    {
        $this->expectException($exceptionName);
        $this->instance->deleteFile($path);
    }
}
