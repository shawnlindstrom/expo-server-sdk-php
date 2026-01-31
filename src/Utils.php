<?php

declare(strict_types=1);

namespace ExpoSDK;

use ExpoSDK\Exceptions\ExpoException;
use ExpoSDK\Exceptions\InvalidTokensException;

final class Utils
{
    /**
     * Prevent instantiation of utility class
     */
    private function __construct()
    {
        // Utility class should not be instantiated
    }

    /**
     * Check if a value is a valid Expo push token
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function isExpoPushToken(mixed $value): bool
    {
        if (! is_string($value) || strlen($value) < 15) {
            return false;
        }

        return (self::strStartsWith($value, 'ExponentPushToken[') ||
            self::strStartsWith($value, 'ExpoPushToken[')) &&
            self::strEndsWith($value, ']');
    }

    /**
     * Determine if an array is an associative array
     *
     * The check determines if the array has sequential numeric
     * keys. If it does not, it is considered an associative array.
     *
     * @param array $arr
     * @return bool
     */
    public static function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Check if a string starts with another
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function strStartsWith(string $haystack, string $needle): bool
    {
        return $needle !== '' && str_starts_with($haystack, $needle);
    }

    /**
     * Check if a string ends with another
     *
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function strEndsWith(string $haystack, string $needle): bool
    {
        return $needle !== '' && str_ends_with($haystack, $needle);
    }

    /**
     * Wrap data in the array if data is not an array
     *
     * @param mixed $data
     * @return array
     */
    public static function arrayWrap(mixed $data): array
    {
        return is_array($data) ? $data : [$data];
    }

    /**
     * Validates and filters tokens for later use
     *
     * @param string|string[] $tokens
     * @return string[]
     * @throws ExpoException
     * @throws InvalidTokensException
     *
     */
    public static function validateTokens(array|string|null $tokens): array
    {
        if (! is_array($tokens) && ! is_string($tokens)) {
            throw new InvalidTokensException(sprintf(
                'Tokens must be a string or non empty array, %s given.',
                gettype($tokens)
            ));
        }

        $tokens = array_filter(
            self::arrayWrap($tokens),
            static fn (mixed $token): bool => self::isExpoPushToken($token)
        );

        if (count($tokens) === 0) {
            throw new ExpoException('No valid expo tokens provided.');
        }

        return array_values($tokens);
    }
}
