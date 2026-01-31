<?php

declare(strict_types=1);

namespace ExpoSDK\Drivers;

use ExpoSDK\Exceptions\FileDoesntExistException;
use ExpoSDK\Exceptions\InvalidFileException;
use ExpoSDK\Exceptions\UnableToReadFileException;
use ExpoSDK\Exceptions\UnableToWriteFileException;
use ExpoSDK\File;
use JsonException;

class FileDriver extends Driver
{
    /**
     * The path to the file
     *
     * @var string
     */
    private string $path = __DIR__ . '/../storage/expo.json';

    /**
     * The storage file object
     *
     * @var File
     */
    private File $file;

    /**
     * @param  array  $config
     * @throws FileDoesntExistException
     * @throws InvalidFileException
     * @throws UnableToReadFileException
     * @throws UnableToWriteFileException
     * @throws JsonException
     */
    public function __construct(array $config)
    {
        $this->build($config);
    }

    /**
     * Builds the driver instance
     *
     * @param  array  $config
     * @throws FileDoesntExistException
     * @throws InvalidFileException
     * @throws UnableToReadFileException
     * @throws UnableToWriteFileException
     * @throws JsonException
     */
    protected function build(array $config): void
    {
        $path = array_key_exists('path', $config) ? $config['path'] : $this->path;

        if (! is_string($path) || $path === '') {
            throw new FileDoesntExistException('The file  does not exist.');
        }

        $this->file = new File($path);
    }

    /**
     * Stores tokens for a channel
     *
     * @param  string  $channel
     * @param  array  $tokens
     * @return bool
     * @throws JsonException
     * @throws UnableToReadFileException
     * @throws UnableToWriteFileException
     */
    public function store(string $channel, array $tokens): bool
    {
        $store = $this->file->read();
        $subs = $store->{$channel} ?? null;

        $subs = $subs ? array_merge($subs, $tokens) : $tokens;
        $store->{$channel} = array_unique($subs);

        return $this->file->write($store);
    }

    /**
     * Retrieves a channels subscriptions
     *
     * @param  string  $channel
     * @return array|null
     * @throws UnableToReadFileException
     */
    public function retrieve(string $channel): ?array
    {
        $store = $this->file->read();

        return $store->{$channel} ?? null;
    }

    /**
     * Removes subscriptions from a channel\
     *
     * @param  string  $channel
     * @param  array  $tokens
     * @return bool
     * @throws JsonException
     * @throws UnableToReadFileException
     * @throws UnableToWriteFileException
     */
    public function forget(string $channel, array $tokens): bool
    {
        $store = $this->file->read();
        $subs = $store->{$channel} ?? null;
        $tokens = array_unique($tokens);

        if (is_null($subs)) {
            return true;
        }

        $subs = array_filter($subs, function ($token) use ($tokens) {
            return ! in_array($token, $tokens);
        });

        // delete the channel if there are no more subscriptions
        if (count($subs) === 0) {
            unset($store->{$channel});
        } else {
            $store->{$channel} = array_values($subs);
        }

        return $this->file->write($store);
    }
}
