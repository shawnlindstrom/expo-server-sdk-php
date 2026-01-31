<?php

declare(strict_types=1);

namespace ExpoSDK;

use ExpoSDK\Exceptions\FileDoesntExistException;
use ExpoSDK\Exceptions\InvalidFileException;
use ExpoSDK\Exceptions\UnableToReadFileException;
use ExpoSDK\Exceptions\UnableToWriteFileException;
use JsonException;
use stdClass;

class File
{
    /** @var string */
    private string $path;

    /**
     * @param  string  $path
     * @throws FileDoesntExistException
     * @throws InvalidFileException
     * @throws JsonException
     * @throws UnableToReadFileException
     * @throws UnableToWriteFileException
     */
    public function __construct(string $path)
    {
        $this->path = $path;

        if (! $this->isValidPath($path)) {
            throw new FileDoesntExistException(sprintf(
                'The file %s does not exist.',
                $path
            ));
        }

        if (! $this->isJson($path)) {
            throw new InvalidFileException('The storage file must have a .json extension.');
        }

        $this->validateContents();
    }

    /**
     * Check if the file path is valid and exists
     */
    private function isValidPath(string $path): bool
    {
        return strlen($path) > 0 && file_exists($path);
    }

    /**
     * Check if the file has a JSON extension
     */
    private function isJson(string $path): bool
    {
        return Utils::strEndsWith($path, '.json');
    }

    /**
     * Ensures the file contains an object
     * @throws JsonException
     * @throws UnableToReadFileException
     * @throws UnableToWriteFileException
     */
    private function validateContents(): void
    {
        if (! is_file($this->path) || ! is_readable($this->path)) {
            throw new UnableToReadFileException(sprintf(
                'Unable to read file at %s.',
                $this->path
            ));
        }

        $contents = file_get_contents($this->path);

        if ($contents === false) {
            throw new UnableToReadFileException(sprintf(
                'Unable to read file at %s.',
                $this->path
            ));
        }

        if (trim($contents) === '') {
            $this->write(new stdClass());
            return;
        }

        try {
            $decoded = json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new UnableToReadFileException(sprintf(
                'Unable to read file at %s.',
                $this->path
            ), 0, $e);
        }

        if (! is_object($decoded)) {
            $this->write(new stdClass());
        }
    }

    /**
     * Reads the files contents
     *
     * @return object|null
     * @throws UnableToReadFileException
     */
    public function read(): ?object
    {
        if (! is_file($this->path) || ! is_readable($this->path)) {
            throw new UnableToReadFileException(sprintf(
                'Unable to read file at %s.',
                $this->path
            ));
        }

        $contents = file_get_contents($this->path);

        if ($contents === false) {
            throw new UnableToReadFileException(sprintf(
                'Unable to read file at %s.',
                $this->path
            ));
        }

        if (trim($contents) === '') {
            return new stdClass();
        }

        try {
            return json_decode($contents, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new UnableToReadFileException(sprintf(
                'Unable to read file at %s.',
                $this->path
            ), 0, $e);
        }
    }

    /**
     * Writes content to the file
     *
     * @throws UnableToWriteFileException
     * @throws JsonException
     */
    public function write(object $contents): bool
    {
        $exception = new UnableToWriteFileException(sprintf(
            'Unable to write file at %s.',
            $this->path
        ));

        if (! file_exists($this->path)) {
            throw $exception;
        }

        $json = json_encode($contents, JSON_THROW_ON_ERROR);
        $result = file_put_contents($this->path, $json, LOCK_EX);

        if ($result === false) {
            throw $exception;
        }

        return true;
    }

    /**
     * Empties the files contents
     *
     * @throws UnableToWriteFileException
     * @throws JsonException
     */
    public function empty(): void
    {
        $this->write(new stdClass());
    }
}
