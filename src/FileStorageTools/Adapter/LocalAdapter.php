<?php

namespace BayWaReLusy\FileStorageTools\Adapter;

use BayWaReLusy\FileStorageTools\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorageTools\Exception\DirectoryNotEmptyException;
use BayWaReLusy\FileStorageTools\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorageTools\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorageTools\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorageTools\Exception\UnknownErrorException;

class LocalAdapter implements FileStorageAdapterInterface
{
    protected string $remotePath;
    public function __construct(
        string $remotePath
    ) {
        $this->remotePath = rtrim($remotePath, '/');
    }

    /**
     * @inheritDoc
     */
    public function createDirectory(string $path): void
    {
        try {
            if (!file_exists($this->remotePath)) {
                mkdir($this->remotePath);
                chmod($this->remotePath, 0777);
            }
            $path = ltrim($path, '/');
            if (file_exists($this->remotePath . DIRECTORY_SEPARATOR . $path)) {
                return;
            }
            if (!@mkdir($this->remotePath . DIRECTORY_SEPARATOR . $path)) {
                throw new ParentNotFoundException("The parent directory could not be found");
            }
            //Need to put the permissions with chmod, the default perm of mkdir does not work because of umask
            if (!chmod($this->remotePath . DIRECTORY_SEPARATOR . $path, 0777)) {
                throw new UnknownErrorException("Unexpected error");
            }
        } catch (ParentNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new UnknownErrorException("Unexpected error");
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteDirectory(string $path): void
    {
        try {
            $path = ltrim($path, '/');
            if (!file_exists($this->remotePath . DIRECTORY_SEPARATOR . $path)) {
                throw new DirectoryDoesntExistsException("the directory doesn't exists");
            }
            if (!@rmdir($this->remotePath . DIRECTORY_SEPARATOR . $path)) {
                throw new DirectoryNotEmptyException("The directory isn't empty");
            }
        } catch (DirectoryDoesntExistsException | DirectoryNotEmptyException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new UnknownErrorException("Unknown exception");
        }
    }

    /**
     * @inheritDoc
     */
    public function uploadFile(string $directory, string $pathToFile): void
    {
        $directory = ltrim($directory, '/');
        // Check first if local file exists and can be opened
        if (!file_exists($pathToFile)) {
            throw new LocalFileNotFoundException("File not found.");
        }
        $destinationPath = $this->remotePath . DIRECTORY_SEPARATOR . $directory;
        if (!file_exists($destinationPath)) {
            throw new ParentNotFoundException("Remote parent could not be found");
        }
        if (!is_readable($pathToFile)) {
            throw new FileCouldNotBeOpenedException("The file could not be open");
        }
        if (!copy($pathToFile, $destinationPath . DIRECTORY_SEPARATOR . basename($pathToFile))) {
            throw new UnknownErrorException("Unknown error");
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteFile(string $pathToFile): void
    {
        $pathToFile = ltrim($pathToFile, '/');
        if (!file_exists($this->remotePath . DIRECTORY_SEPARATOR . dirname($pathToFile))) {
            throw new DirectoryDoesntExistsException("The remote directory does not exists");
        }
        if (!file_exists($this->remotePath . DIRECTORY_SEPARATOR . $pathToFile)) {
            throw new RemoteFileDoesntExistException("The remote file could not be found");
        }
        unlink($this->remotePath . DIRECTORY_SEPARATOR . $pathToFile);
    }

    /**
     * @inheritDoc
     */
    public function listFilesInDirectory(string $directory, bool $includeDirectories = true): array
    {

        $results = [];
        $directory = ltrim($directory, '/');
        $truePath = $this->remotePath . DIRECTORY_SEPARATOR . $directory;
        if (!$files = scandir($truePath)) {
            throw new DirectoryDoesntExistsException("The directory doesn't seem to exist");
        }
        foreach ($files as $file) {
            if (is_dir("{$truePath}/{$file}")) {
                if ($includeDirectories) {
                    $results[] = $file;
                }
            } else {
                $results[] = $file;
            }
        }
        return $results;
    }

    /**
     * @inheritDoc
     */
    public function getPublicFileUrl(string $pathToFile): string
    {
        $pathToFile = ltrim($pathToFile, '/');
        if (!file_exists($this->remotePath . DIRECTORY_SEPARATOR . $pathToFile)) {
            throw new LocalFileNotFoundException();
        }
        return sprintf("http://definitelynotavirus.ru/%s", $pathToFile);
    }
}
