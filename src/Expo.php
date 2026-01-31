<?php

declare(strict_types=1);

namespace ExpoSDK;

use Closure;
use ExpoSDK\Exceptions\ExpoException;
use ExpoSDK\Exceptions\InvalidTokensException;
use ExpoSDK\Exceptions\UnsupportedDriverException;
use ExpoSDK\Traits\Macroable;
use GuzzleHttp\Exception\GuzzleException;

class Expo
{
    use Macroable;

    /**
     * @var DriverManager|null
     */
    private ?DriverManager $manager;

    /**
     * @var ExpoClient
     */
    private ExpoClient $client;

    /**
     * Messages to send
     *
     * @var ExpoMessage[]
     */
    private array $messages = [];

    /**
     * Default tokens to send the message to (if they don't have their own respective recipients)
     *
     * @var array|null
     */
    private ?array $recipients = null;

    public function __construct(?DriverManager $manager = null, array $clientOptions = [])
    {
        $this->manager = $manager;

        $this->client = new ExpoClient($clientOptions);
    }

    /**
     * Registers macro for handling all tokens with DeviceNotRegistered errors
     *
     * @param Closure $callback
     * @return void
     */
    public static function addDevicesNotRegisteredHandler(Closure $callback): void
    {
        self::macro('devicesNotRegistered', $callback);
    }

    /**
     * Builds an expo instance
     *
     * @param  string  $driver
     * @param  array  $config
     * @return Expo
     * @throws UnsupportedDriverException
     */
    public static function driver(string $driver = 'file', array $config = []): self
    {
        $manager = new DriverManager($driver, $config);

        return new self($manager);
    }

    /**
     * Subscribes tokens to a channel
     *
     * @param  string  $channel
     * @param  null|string|array  $tokens
     * @return bool
     * @throws ExpoException
     * @throws InvalidTokensException
     */
    public function subscribe(string $channel, array|string|null $tokens = null): bool
    {
        if ($this->manager) {
            return $this->manager->subscribe($channel, $tokens);
        }

        throw new ExpoException('You must provide a driver to interact with subscriptions.');
    }

    /**
     * Unsubscribes tokens from a channel
     *
     * @param  string  $channel
     * @param  array|string|null  $tokens
     * @return bool
     * @throws ExpoException|InvalidTokensException
     */
    public function unsubscribe(string $channel, array|string|null $tokens = null): bool
    {
        if ($this->manager) {
            return $this->manager->unsubscribe($channel, $tokens);
        }

        throw new ExpoException('You must provide a driver to interact with subscriptions.');
    }

    /**
     * Set the recipients from channel subscriptions to send the message to
     *
     * @param string $channel
     * @return $this
     * @throws ExpoException
     */
    public function toChannel(string $channel): self
    {
        $this->recipients = $this->getSubscriptions($channel);

        return $this;
    }

    /**
     * Retrieves a channels subscriptions
     *
     * @param  string  $channel
     * @return array|null
     * @throws ExpoException
     */
    public function getSubscriptions(string $channel): ?array
    {
        if ($this->manager) {
            return $this->manager->getSubscriptions($channel);
        }

        throw new ExpoException('You must provide a driver to interact with subscriptions.');
    }

    /**
     * Checks if a channel has subscriptions
     *
     * @param  string  $channel
     * @return bool
     * @throws ExpoException
     */
    public function hasSubscriptions(string $channel): bool
    {
        if ($this->manager) {
            return (bool) $this->manager->getSubscriptions($channel);
        }

        throw new ExpoException(
            'You must provide a driver to interact with subscriptions.'
        );
    }

    /**
     * Check if a value is a valid Expo push token
     *
     * @param  mixed  $value
     * @return bool
     */
    public function isExpoPushToken(mixed $value): bool
    {
        return Utils::isExpoPushToken($value);
    }

    /**
     * Get default recipients
     *
     * @return array|null
     */
    public function getRecipients(): ?array
    {
        return $this->recipients;
    }

    /**
     * Get messages
     *
     * @return array|null
     */
    public function getMessages(): ?array
    {
        return $this->messages;
    }

    /**
     * Sets the messages to send
     *
     * @param  array|ExpoMessage|ExpoMessage[]  $message
     * @return $this
     * @throws ExpoException
     */
    public function send(array|ExpoMessage $message): self
    {
        $messages = Utils::arrayWrap($message);

        if (Utils::isAssoc($messages)) {
            throw new ExpoException(
                'You can only send an ExpoMessage instance or an array of messages'
            );
        }

        foreach ($messages as $index => $message) {
            if (! $message instanceof ExpoMessage) {
                $messages[$index] = new ExpoMessage($message);
            }
        }

        $this->messages = $messages;

        return $this;
    }

    /**
     * Sets the default recipients
     *
     * @param  array|string|null  $recipients
     * @return $this
     * @throws InvalidTokensException
     * @throws ExpoException
     */
    public function to(array|string|null $recipients = null): self
    {
        $this->recipients = Utils::validateTokens($recipients);

        return $this;
    }

    /**
     * Send the messages to the expo server
     *
     * @return ExpoResponse
     * @throws ExpoException
     * @throws GuzzleException
     */
    public function push(): ExpoResponse
    {
        if (count($this->messages) === 0) {
            throw new ExpoException('You must have at least one message to push');
        }

        $defaultTokens = null;

        if ($this->recipients !== null) {
            // Recipients are pre-validated in to(), but we re-validate defensively here
            // in case of future modifications to internal state or direct property access
            try {
                $defaultTokens = Utils::validateTokens($this->recipients);
            } catch (InvalidTokensException $e) {
                throw new ExpoException('Default recipients are invalid', 0, $e);
            } catch (ExpoException $e) {
                throw new ExpoException('No valid default recipients provided.', 0, $e);
            }
        }

        $messages = [];

        foreach ($this->messages as $message) {
            $messageArray = $message->toArray();

            $hasExplicitTo = array_key_exists('to', $messageArray);
            $tokensSource = $hasExplicitTo ? $messageArray['to'] : $defaultTokens;

            if ($tokensSource === null || $tokensSource === []) {
                throw new ExpoException('A message must have at least one recipient to send');
            }

            try {
                $validatedTokens = Utils::validateTokens($tokensSource);
            } catch (InvalidTokensException|ExpoException $e) {
                throw new ExpoException('A message must have at least one valid recipient to send', 0, $e);
            }

            foreach ($validatedTokens as $token) {
                $payload = $messageArray;
                $payload['to'] = $token;
                $messages[] = $payload;
            }
        }

        $this->reset();

        $response = $this->client->sendPushNotifications($messages);

        if (self::hasMacro('devicesNotRegistered')) {
            $data = $response->getData();
            if (is_array($data)) {
                $notRegisteredTokens = [];

                foreach ($data as $index => $ticket) {
                    if (($ticket['details']['error'] ?? '') === 'DeviceNotRegistered') {
                        $notRegisteredTokens[] = $ticket['details']['expoPushToken'] ?? ($messages[$index]['to'] ?? null);
                    }
                }

                $notRegisteredTokens = array_values(array_unique(array_filter($notRegisteredTokens, 'is_string')));

                if (count($notRegisteredTokens) > 0) {
                    $this->devicesNotRegistered($notRegisteredTokens);
                }
            }
        }

        return $response;
    }

    /**
     * Fetches the push notification receipts from the expo server
     *
     * @param  array  $ticketIds
     * @return ExpoResponse
     * @throws ExpoException|GuzzleException
     */
    public function getReceipts(array $ticketIds): ExpoResponse
    {
        $ticketIds = array_filter($ticketIds, function ($id) {
            return is_string($id);
        });

        return $this->client->getPushNotificationReceipts($ticketIds);
    }

    /**
     * Set the Expo access token
     *
     * @param string $accessToken
     * @return $this
     */
    public function setAccessToken(string $accessToken): self
    {
        $this->client->setAccessToken($accessToken);

        return $this;
    }

    /**
     * Resets the instance data
     *
     * @return void
     */
    public function reset(): void
    {
        $this->messages = [];
        $this->recipients = null;
    }
}
