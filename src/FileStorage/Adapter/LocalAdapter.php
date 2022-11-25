<?php

namespace BayWaReLusy\FileStorage\Adapter;

use BayWaReLusy\FileStorage\Exception\DirectoryDoesntExistsException;
use BayWaReLusy\FileStorage\Exception\DirectoryNotEmptyException;
use BayWaReLusy\FileStorage\Exception\FileCouldNotBeOpenedException;
use BayWaReLusy\FileStorage\Exception\LocalFileNotFoundException;
use BayWaReLusy\FileStorage\Exception\ParentNotFoundException;
use BayWaReLusy\FileStorage\Exception\RemoteFileDoesntExistException;
use BayWaReLusy\FileStorage\Exception\UnknownErrorException;

class LocalAdapter implements FileStorageAdapterInterface
{
    /** @var string Base path of where to store the files */
    protected string $remotePath;

    /** @var string Local url for creation of public link */
    protected string $originUrl;

    /**
     * @param string $remotePath Base path of where to store the files
     */
    public function __construct(string $remotePath, string $originUrl)
    {
        $this->remotePath = rtrim($remotePath, '/');
        $this->originUrl = $originUrl;
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

            $path = trim($path, DIRECTORY_SEPARATOR);

            // Don't do anything if the directory already exists
            if (file_exists($this->remotePath . DIRECTORY_SEPARATOR . $path)) {
                return;
            }

            // Check if the parent directory exists
            if (!@mkdir($this->remotePath . DIRECTORY_SEPARATOR . $path)) {
                throw new ParentNotFoundException('The parent directory could not be found');
            }

            // Need to set the permissions with chmod because otherwise they could be altered through umask
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
            $path = trim($path, '/');

            if (!file_exists($this->remotePath . DIRECTORY_SEPARATOR . $path)) {
                throw new DirectoryDoesntExistsException("The directory doesn't exist.");
            }

            if (!@rmdir($this->remotePath . DIRECTORY_SEPARATOR . $path)) {
                throw new DirectoryNotEmptyException("The directory isn't empty.");
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
    public function uploadFile(string $remoteDirectory, string $pathToFile): void
    {
        $remoteDirectory = trim($remoteDirectory, DIRECTORY_SEPARATOR);

        // Check first if local file exists and can be opened
        if (!file_exists($pathToFile)) {
            throw new LocalFileNotFoundException('File not found.');
        }

        if (!is_readable($pathToFile)) {
            throw new FileCouldNotBeOpenedException("The file couldn't be opened.");
        }

        $destinationPath = $this->remotePath . DIRECTORY_SEPARATOR . $remoteDirectory;

        if (!file_exists($destinationPath)) {
            throw new ParentNotFoundException("Remote parent could not be found.");
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
        $pathToFile = ltrim($pathToFile, DIRECTORY_SEPARATOR);

        if (!file_exists($this->remotePath . DIRECTORY_SEPARATOR . dirname($pathToFile))) {
            throw new DirectoryDoesntExistsException("The remote directory doesn't exist.");
        }

        if (!file_exists($this->remotePath . DIRECTORY_SEPARATOR . $pathToFile)) {
            throw new RemoteFileDoesntExistException("The remote file couldn't be found.");
        }

        unlink($this->remotePath . DIRECTORY_SEPARATOR . $pathToFile);
    }

    /**
     * @inheritDoc
     */
    public function listFilesInDirectory(string $directory, bool $includeDirectories = true): array
    {
        $results   = [];
        $directory = $this->remotePath . DIRECTORY_SEPARATOR . trim($directory, DIRECTORY_SEPARATOR);

        if (!$files = scandir($directory)) {
            throw new DirectoryDoesntExistsException("The directory doesn't seem to exist.");
        }

        foreach ($files as $file) {
            if (!is_dir("{$directory}/{$file}") || $includeDirectories) {
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
        $pathToFile = ltrim($pathToFile, DIRECTORY_SEPARATOR);

        //we need to get the part of the url after public/
        $relativePath = explode('public', $this->remotePath);
        if (count($relativePath) < 2) {
            throw new UnknownErrorException("The path should contain the 'public' folder");
        }

        if (!file_exists($this->remotePath . DIRECTORY_SEPARATOR . $pathToFile)) {
            throw new LocalFileNotFoundException();
        }
        return ($this->originUrl . $relativePath[1] . '/' . $pathToFile);
    }
}
