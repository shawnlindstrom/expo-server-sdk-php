<?php

declare(strict_types=1);

namespace ExpoSDK;

use Psr\Http\Message\ResponseInterface;

class ExpoResponse
{
    /** @var array */
    private mixed $response;

    /** @var int */
    private int $statusCode;

    public function __construct(ResponseInterface $response)
    {
        $this->response = json_decode(
            (string) $response->getBody(),
            true
        );

        $this->statusCode = $response->getStatusCode();
    }

    /**
     * Checks if the request succeeded
     */
    public function ok(): bool
    {
        return $this->statusCode === 200 &&
            ! array_key_exists('errors', $this->response);
    }

    /**
     * Get the http response status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Gets the data from the expo response
     *
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->ok()
            ? $this->response['data']
            : null;
    }
}
