<?php

declare(strict_types=1);

namespace ExpoSDK;

use Closure;
use ExpoSDK\Exceptions\ExpoException;
use ExpoSDK\Exceptions\InvalidTokensException;
use ExpoSDK\Traits\Macroable;

class Expo
{
    use Macroable;

    /**
     * @var DriverManager
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
     */
    public static function addDevicesNotRegisteredHandler(Closure $callback): void
    {
        self::macro('devicesNotRegistered', $callback);
    }

    /**
     * Builds an expo instance
     */
    public static function driver(string $driver = 'file', array $config = []): self
    {
        $manager = new DriverManager($driver, $config);

        return new self($manager);
    }

    /**
     * Subscribes tokens to a channel
     *
     * @param  array|string|null  $tokens
     * @return bool
     * @throws ExpoException
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
     * @param  array|string|null  $tokens
     * @return bool
     * @throws ExpoException
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
     */
    public function toChannel(string $channel): self
    {
        $this->recipients = $this->getSubscriptions($channel);

        return $this;
    }

    /**
     * Retrieves a channels subscriptions
     *
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
     * @param mixed $value
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
     * @throws ExpoException
     */
    public function push(): ExpoResponse
    {
        if (empty($this->messages)) {
            throw new ExpoException('You must have at least one message to push');
        }

        $messages = [];

        /**
         * When a response ticket has DeviceNotRegistered it has no indication which push token produced this error.
         * However it is known the order of messages and response tickets are the same.
         * So the only way to keep track of invalid tokens is by their indices.
         * For this to work we need to flatten messages' recipients.
         */
        foreach ($this->messages as $message) {
            $message = $message->toArray();
            $tokens = $message['to'] ?? $this->recipients;

            if (empty($tokens)) {
                throw new ExpoException('A message must have at least one recipient to send');
            }

            foreach (Utils::arrayWrap($tokens) as $token) {
                $messages[] = array_merge($message, ['to' => $token]);
            }
        }

        $this->reset();

        $response = $this->client->sendPushNotifications($messages);

        if (self::hasMacro('devicesNotRegistered')) {
            $notRegisteredTokens = [];

            foreach ($response->getData() as $index => $ticket) {
                if (($ticket['details']['error'] ?? '') === 'DeviceNotRegistered') {
                    $notRegisteredTokens[] = $ticket['details']['expoPushToken'] ?? $messages[$index]['to'];
                }
            }

            if (! empty($notRegisteredTokens)) {
                $this->devicesNotRegistered(
                    array_unique($notRegisteredTokens)
                );
            }
        }

        return $response;
    }

    /**
     * Fetches the push notification receipts from the expo server
     *
     * @throws ExpoException
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
     */
    public function setAccessToken(string $accessToken): self
    {
        $this->client->setAccessToken($accessToken);

        return $this;
    }

    /**
     * Resets the instance data
     */
    public function reset(): void
    {
        $this->messages = [];
        $this->recipients = null;
    }
}
