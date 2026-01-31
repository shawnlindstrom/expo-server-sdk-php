<?php

declare(strict_types=1);

namespace ExpoSDK\Drivers;

abstract class Driver
{
    /**
     * Builds the driver instance
     */
    abstract protected function build(array $config): void;

    /**
     * Stores subscriptions for a channel
     */
    abstract public function store(string $channel, array $tokens): bool;

    /**
     * Retrieves a channels subscriptions
     *
     */
    abstract public function retrieve(string $channel): ?array;

    /**
     * Removes subscriptions from channels
     */
    abstract public function forget(string $channel, array $tokens): bool;
}
