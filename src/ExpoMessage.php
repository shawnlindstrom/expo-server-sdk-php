<?php

declare(strict_types=1);

namespace ExpoSDK;

use ExpoSDK\Exceptions\ExpoException;
use ExpoSDK\Exceptions\ExpoMessageException;
use ExpoSDK\Exceptions\InvalidTokensException;
use stdClass;

/**
 * Implementation of Expo message request format
 *
 * @link https://docs.expo.dev/push-notifications/sending-notifications/#message-request-format Expo Message request format
 */
class ExpoMessage
{
    /**
     * An Expo push token or an array of Expo push tokens specifying the recipient(s) of this message.
     *
     * @var string[]|null
     */
    private string|array|null $to = null;

    /**
     * A JSON object delivered to your app.
     * It may be up to about 4KiB; the total notification payload sent to Apple and Google must be at most 4KiB,
     * or else you will get a "Message Too Big" error.
     *
     * @var object|array|null
     */
    private array|null|object $data = null;

    /**
     * The title to display in the notification.
     * Often displayed above the notification body.
     *
     * @var string|null
     */
    private ?string $title = null;

    /**
     * The message to display in the notification.
     *
     * @var string|null
     */
    private ?string $body = null;

    /**
     * Time to Live: the number of seconds for which the message may be kept around for redelivery if it hasn't been
     * delivered yet. Defaults to null to use the respective defaults of each provider (0 for iOS/APNs and 2,419,200
     * (4 weeks) for Android/FCM).
     *
     * @var int|null
     */
    private ?int $ttl = null;

    /**
     * Timestamp since the UNIX epoch specifying when the message expires.
     * Same effect as ttl (ttl takes precedence over expiration).
     *
     * @var int|null
     */
    private ?int $expiration = null;

    /**
     * The delivery priority of the message.
     * Specify "default" or omit this field to use the default priority on each platform ("normal" on Android and
     * "high" on iOS).
     *
     * Supported: 'default', 'normal', 'high'.
     *
     * @var string
     */
    private string $priority = 'default';

    /**
     * The subtitle to display in the notification below the title.
     *
     * iOS only.
     *
     * @var string|null
     */
    private ?string $subtitle = null;

    /**
     * Play a sound when the recipient receives this notification.
     * Specify "default" to play the device's default notification sound, or omit this field to play no sound.
     * Custom sounds are not supported.
     *
     * iOS only.
     *
     * @var string|null
     */
    private ?string $sound = null;

    /**
     * Number to display in the badge on the app icon.
     * Specify zero to clear the badge.
     *
     * iOS only.
     *
     * @var int|float|null
     */
    private int|float|null $badge = null;

    /**
     * ID of the Notification Channel through which to display this notification.
     * If an ID is specified but the corresponding channel does not exist on the device (i.e., has not yet been created
     * by your app), the notification will not be displayed to the user.
     *
     * Android only.
     *
     * @var string|null
     */
    private ?string $channelId = null;

    /**
     * ID of the notification category that this notification is associated with.
     * Must be on at least SDK 41 or bare workflow.
     *
     * @see https://docs.expo.dev/versions/latest/sdk/notifications/#managing-notification-categories-interactive-notifications Notification categories
     *
     * @var string|null
     */
    private ?string $categoryId = null;

    /**
     * Specifies whether the client app can intercept this notification.
     * In Expo Go, this defaults to true, and if you change that to false, you may experience issues.
     * In standalone and bare apps, this defaults to false.
     *
     * iOS only.
     *
     * @var bool
     */
    private bool $mutableContent = false;

    /**
     * Used to handle notifications while the app is in the background on iOS.
     *
     * iOS only.
     *
     * @link https://docs.expo.dev/versions/latest/sdk/notifications/#background-events:~:text=normal%20circumstances%2C%20the-,%22content%2Davailable%22,-flag%20should%20launch
     *
     * @var bool
     */
    private bool $_contentAvailable = false;

    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            /**
             * Remove leading underscore's accounting for the _contentAvailable attribute.
             * Allows contentAvailable or _contentAvailable to be passed into the constructor.
             */
            $key = ltrim($key, '_');
            $method = 'set' . ucfirst($key);

            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    /**
     * Set recipients of the message
     *
     * @see to
     *
     * @param  string|string[]  $tokens
     * @return $this
     * @throws ExpoException
     * @throws InvalidTokensException
     *
     */
    public function setTo(array|string $tokens): self
    {
        $this->to = Utils::validateTokens($tokens);

        return $this;
    }

    /**
     * Sets the data for the message
     *
     * @see data
     *
     * @param  mixed|null  $data
     * @return $this
     * @throws ExpoMessageException
     */
    public function setData(mixed $data = null): self
    {
        if ($data === null) {
            $this->data = null;

            return $this;
        }

        if (is_array($data)) {
            if ($data === []) {
                $this->data = new stdClass();

                return $this;
            }

            if (! Utils::isAssoc($data)) {
                throw new ExpoMessageException(sprintf(
                    'Message data must be either an associative array, object or null. %s given',
                    gettype($data)
                ));
            }

            $this->data = $data;

            return $this;
        }

        if (is_object($data)) {
            $this->data = $data;

            return $this;
        }

        throw new ExpoMessageException(sprintf(
            'Message data must be either an associative array, object or null. %s given',
            gettype($data)
        ));
    }

    /**
     * Sets the title to display in the notification
     *
     * @see title
     *
     * @param  string|null  $title
     * @return $this
     */
    public function setTitle(?string $title = null): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Sets the message to display in the notification
     *
     * @see body
     *
     * @param  string|null  $body
     * @return $this
     */
    public function setBody(?string $body = null): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Sets the number of seconds for which the message may be kept around for redelivery
     *
     * @see ttl
     *
     * @param  int|null  $ttl
     * @return $this
     */
    public function setTtl(?int $ttl = null): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     * Sets expiration time of the message
     *
     * @see expiration
     *
     * @param  int|null  $expiration
     * @return $this
     */
    public function setExpiration(?int $expiration = null): self
    {
        $this->expiration = $expiration;

        return $this;
    }

    /**
     * Sets the delivery priority of the message, either 'default', 'normal' or 'high'
     *
     * @see priority
     *
     * @param  string  $priority
     * @return $this
     * @throws ExpoMessageException
     */
    public function setPriority(string $priority = 'default'): self
    {
        $priority = strtolower($priority);

        if (! in_array($priority, ['default', 'normal', 'high'])) {
            throw new ExpoMessageException('Priority must be default, normal or high.');
        }

        $this->priority = $priority;

        return $this;
    }

    /**
     * Sets the subtitle to display in the notification below the title
     *
     * @see subtitle
     *
     * @param  string|null  $subtitle
     * @return $this
     */
    public function setSubtitle(?string $subtitle = null): self
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /**
     * Play a sound when the recipient receives the notification
     *
     * @see sound
     *
     * @return $this
     */
    public function playSound(): self
    {
        $this->sound = 'default';

        return $this;
    }

    /**
     * Sets the sound to play when the notification is received
     *
     * @see sound
     * @see playSound()
     *
     * @param  string|null  $sound
     * @return $this
     */
    public function setSound(?string $sound = null): self
    {
        $this->sound = $sound;

        return $this;
    }

    /**
     * Set the number to display in the badge on the app icon
     *
     * @see badge
     *
     * @param  int|float|null  $badge
     * @return $this
     */
    public function setBadge(int|float|null $badge = null): self
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Set the ID of the Notification Channel through which to display this notification
     *
     * @see channelId
     *
     * @param  string|null  $channelId
     * @return $this
     */
    public function setChannelId(?string $channelId = null): self
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * Set the ID of the notification category that this notification is associated with
     *
     * @see categoryId
     *
     * @param  string|null  $categoryId
     * @return $this
     */
    public function setCategoryId(?string $categoryId = null): self
    {
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * Set whether the client app intercepts the notification
     *
     * @see mutableContent
     *
     * @param  bool  $mutable
     * @return $this
     */
    public function setMutableContent(bool $mutable): self
    {
        $this->mutableContent = $mutable;

        return $this;
    }

    /**
     * Set whether the notification can be handled while the app is in the background on iOS
     *
     * @see _contentAvailable
     *
     * @param  bool  $contentAvailable
     * @return $this
     */
    public function setContentAvailable(bool $contentAvailable): self
    {
        $this->_contentAvailable = $contentAvailable;

        return $this;
    }

    /**
     * Convert the message to an array
     * Skips properties with 'null' values
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = get_object_vars($this);

        return array_filter(
            $attributes,
            static fn (mixed $value): bool => $value !== null
        );
    }
}
