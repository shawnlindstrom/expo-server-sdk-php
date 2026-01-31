<?php

declare(strict_types=1);

namespace ExpoSDK;

use ExpoSDK\Exceptions\ExpoException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

class ExpoErrorManager
{
    /**
     * Parses an error response from expo
     */
    public function parseErrorResponse(ResponseInterface $response): ExpoException
    {
        $statusCode = $response->getStatusCode();
        $textBody = (string) $response->getBody();

        try {
            $result = json_decode($textBody, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->getTextResponseError($textBody, $statusCode);
        }

        if (! is_array($result) || ! $this->responseHasErrors($result)) {
            return $this->getTextResponseError($textBody, $statusCode);
        }

        return $this->getErrorFromResult($result, $statusCode);
    }

    /**
     * Constructs an exception from the response text
     */
    public function getTextResponseError(string $errorText, int $statusCode): ExpoException
    {
        return new ExpoException(sprintf(
            "Expo responded with an error with status code: %s: %s",
            $statusCode,
            $errorText
        ), $statusCode);
    }

    /**
     * Returns an exception for the first API error from the expo response
     */
    public function getErrorFromResult(array $response, int $statusCode): ExpoException
    {
        if (! $this->responseHasErrors($response)) {
            return new ExpoException(
                'Expected at least one error from Expo. Found none',
                $statusCode
            );
        }

        $error = $response['errors'][0];
        $message = $error['message'];
        $code = $error['code'];

        if (is_string($code)) {
            $message = "{$code}: {$message}";
            $code = $statusCode;
        }

        return new ExpoException($message, $code);
    }

    /**
     * Determine if the json decoded response has errors present
     */
    public function responseHasErrors(array $response): bool
    {
        return array_key_exists('errors', $response) &&
            is_array($response['errors']) &&
            count($response['errors']) > 0;
    }
}
