<?php

namespace ExpoSDK\Tests;

use ExpoSDK\Exceptions\ExpoMessageException;
use ExpoSDK\ExpoMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class ExpoMessageTest extends TestCase
{
    #[Test]
    public function constructor_accepts_content_available_without_leading_underscore()
    {
        $message = new ExpoMessage([
            'contentAvailable' => true,
        ]);

        $this->assertSame(
            [
                'priority' => 'default',
                'mutableContent' => false,
                '_contentAvailable' => true,
            ],
            $message->toArray()
        );
    }

    #[Test]
    public function an_expo_message_can_be_instantiated()
    {
        $this->assertSame(
            [
                'priority' => 'default',
                'mutableContent' => false,
                '_contentAvailable' => false,
            ],
            new ExpoMessage()->toArray()
        );
    }

    #[Test]
    public function you_can_set_message_attributes()
    {
        $message = new ExpoMessage();

        $message->setData(['foo' => 'bar'])
            ->setTtl(10)
            ->setTo(['ExponentPushToken[valid-token]', 'invalid-token]'])
            ->setExpiration(10)
            ->setPriority('default')
            ->setSubtitle('Subtitle')
            ->setBadge(0)
            ->setChannelId('default')
            ->setCategoryId('category-id')
            ->setMutableContent(true)
            ->setContentAvailable(true);

        $this->assertSame(
            [
                "to" => ['ExponentPushToken[valid-token]'],
                "data" => ['foo' => 'bar'],
                "ttl" => 10,
                "expiration" => 10,
                "priority" => "default",
                "subtitle" => "Subtitle",
                "badge" => 0,
                "channelId" => "default",
                "categoryId" => "category-id",
                "mutableContent" => true,
                "_contentAvailable" => true,
            ],
            $message->toArray()
        );
    }

    #[Test]
    public function throws_exception_providing_unsupported_priority()
    {
        $message = new ExpoMessage();

        $this->expectExceptionMessage(
            'Priority must be default, normal or high.'
        );

        $message->setPriority('foo');
    }

    #[Test]
    public function throws_exception_if_data_is_a_list_array()
    {
        $message = new ExpoMessage();
        $data = ['foo'];

        $this->expectException(ExpoMessageException::class);
        $this->expectExceptionMessage(sprintf(
            'Message data must be either an associative array, object or null. %s given',
            gettype($data)
        ));

        $message->setData($data);
    }

    #[Test]
    public function throws_exception_if_data_is_a_scalar()
    {
        $message = new ExpoMessage();
        $data = 'foo';

        $this->expectException(ExpoMessageException::class);
        $this->expectExceptionMessage(sprintf(
            'Message data must be either an associative array, object or null. %s given',
            gettype($data)
        ));

        $message->setData($data);
    }

    #[Test]
    public function can_create_message_from_array()
    {
        $message = new ExpoMessage([
            'title' => 'test title',
            'body' => 'test body',
            'data' => [],
            'to' => ['ExponentPushToken[valid-token]', 'invalid-token]'],
            '_contentAvailable' => false,
        ])->toArray();
        $expected = [
            'mutableContent' => false,
            'priority' => 'default',
            'title' => 'test title',
            'body' => 'test body',
            'data' => new stdClass(),
            'to' => ['ExponentPushToken[valid-token]'],
            '_contentAvailable' => false,
        ];

        asort($expected);
        asort($message);

        $this->assertEquals($expected, $message);
    }

    #[Test]
    public function can_set_sound_properties_on_message()
    {
        $message = new ExpoMessage([
            'sound' => 'alert',
        ]);

        $expected = [
            'sound' => 'alert',
            'mutableContent' => false,
            'priority' => 'default',
            '_contentAvailable' => false,
        ];

        $this->assertEquals($expected, $message->toArray());

        $message->setSound('beep');
        $expected['sound'] = 'beep';

        $this->assertEquals($expected, $message->toArray());

        $message->setSound(null);
        unset($expected['sound']);

        $this->assertEquals($expected, $message->toArray());

        $message->playSound();
        $expected['sound'] = 'default';

        $this->assertEquals($expected, $message->toArray());
    }

    #[Test]
    public function can_set_data_to_null()
    {
        $message = new ExpoMessage([
            'data' => ['foo' => 'bar'],
        ]);

        $message->setData(null);

        $result = $message->toArray();
        $this->assertArrayNotHasKey('data', $result);
    }

    #[Test]
    public function can_set_data_to_object()
    {
        $message = new ExpoMessage();

        $obj = new stdClass();
        $obj->foo = 'bar';

        $message->setData($obj);

        $result = $message->toArray();
        $this->assertEquals($obj, $result['data']);
    }

    #[Test]
    public function throws_exception_if_data_is_integer()
    {
        $message = new ExpoMessage();

        $this->expectException(ExpoMessageException::class);
        $this->expectExceptionMessage(
            'Message data must be either an associative array, object or null. integer given'
        );

        $message->setData(42);
    }
}
