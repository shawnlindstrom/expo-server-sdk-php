<?php

declare(strict_types=1);

namespace ExpoSDK;

use ExpoSDK\Drivers\Driver;
use ExpoSDK\Drivers\FileDriver;
use ExpoSDK\Exceptions\FileDoesntExistException;
use ExpoSDK\Exceptions\InvalidFileException;
use ExpoSDK\Exceptions\InvalidTokensException;
use ExpoSDK\Exceptions\UnsupportedDriverException;

class DriverManager
{
    /**
     * Map of driver keys to their implementation classes
     *
     * @var array<string, class-string<Driver>>
     */
    private array $driverMap = [
        'file' => FileDriver::class,
    ];

    private string $driverKey;

    /**
     * @var Driver
     */
    private Driver $driver;

    /**
     * @throws FileDoesntExistException
     * @throws InvalidFileException
     * @throws UnsupportedDriverException
     */
    public function __construct(string $driver, array $config = [])
    {
        $this->validateDriver($driver)
            ->buildDriver($config);
    }

    /**
     * Validates the driver against supported drivers
     *
     * @throws UnsupportedDriverException
     */
    private function validateDriver(string $driver): self
    {
        $this->driverKey = strtolower($driver);

        if (! array_key_exists($this->driverKey, $this->driverMap)) {
            throw new UnsupportedDriverException(sprintf(
                'Driver %s is not supported. Supported drivers: %s',
                $driver,
                implode(', ', array_keys($this->driverMap))
            ));
        }

        return $this;
    }

    /**
     * Builds the driver instance using the driver registry
     *
     * @throws FileDoesntExistException
     * @throws InvalidFileException
     */
    private function buildDriver(array $config): void
    {
        $driverClass = $this->driverMap[$this->driverKey];
        $this->driver = new $driverClass($config);
    }

    /**
     * Subscribes tokens to a channel
     *
     * @param  string  $channel
     * @param  null|string|array  $tokens
     * @return bool
     * @throws InvalidTokensException
     */
    public function subscribe(string $channel, array|string|null $tokens): bool
    {
        return $this->driver->store(
            $this->normalizeChannel($channel),
            $this->normalizeTokens($tokens)
        );
    }

    /**
     * Get a channels tokens
     *
     * @param  string  $channel
     * @return array|null
     */
    public function getSubscriptions(string $channel): ?array
    {
        return $this->driver->retrieve(
            $this->normalizeChannel($channel)
        );
    }

    /**
     * Unsubscribes tokens from a channel
     *
     * @param  string  $channel
     * @param  array|string|null  $tokens
     * @return bool
     * @throws InvalidTokensException
     */
    public function unsubscribe(string $channel, array|string|null $tokens): bool
    {
        return $this->driver->forget(
            $this->normalizeChannel($channel),
            $this->normalizeTokens($tokens)
        );
    }

    /**
     * Normalizes the channel name
     */
    private function normalizeChannel(string $channel): string
    {
        return trim(strtolower($channel));
    }

    /**
     * Normalizes tokens to be an array
     *
     * @param  array|string|null  $tokens
     * @return array
     * @throws InvalidTokensException
     */
    private function normalizeTokens(array|string|null $tokens): array
    {
        if (is_array($tokens) && count($tokens) > 0) {
            return $tokens;
        }

        if (is_string($tokens)) {
            return [$tokens];
        }

        throw new InvalidTokensException(
            'Tokens must be a string or non empty array'
        );
    }
}
