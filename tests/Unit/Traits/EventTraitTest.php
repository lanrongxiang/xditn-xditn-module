<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use DateTimeInterface;
use Tests\TestCase;
use XditnModule\Contracts\Event;
use XditnModule\Traits\EventTrait;

class EventTraitTest extends TestCase
{
    public function test_event_name_returns_class_basename(): void
    {
        $event = new TestEvent();

        $this->assertEquals('TestEvent', $event->getEventName());
    }

    public function test_should_broadcast_defaults_to_false(): void
    {
        $event = new TestEvent();

        $this->assertFalse($event->shouldBroadcast());
    }

    public function test_set_broadcast(): void
    {
        $event = new TestEvent();
        $event->setBroadcast(true);

        $this->assertTrue($event->shouldBroadcast());
    }

    public function test_should_queue_defaults_to_false(): void
    {
        $event = new TestEvent();

        $this->assertFalse($event->shouldQueue());
    }

    public function test_set_queue(): void
    {
        $event = new TestEvent();
        $event->setQueue(true);

        $this->assertTrue($event->shouldQueue());
    }

    public function test_get_occurred_at_returns_datetime(): void
    {
        $event = new TestEvent();

        $this->assertInstanceOf(DateTimeInterface::class, $event->getOccurredAt());
    }

    public function test_get_data_default_returns_empty_array(): void
    {
        $event = new TestEvent();

        $this->assertEquals([], $event->getData());
    }

    public function test_get_data_with_custom_data(): void
    {
        $event = new TestEventWithData(['id' => 1, 'name' => 'test']);

        $this->assertEquals(['id' => 1, 'name' => 'test'], $event->getData());
    }

    public function test_to_array_contains_all_fields(): void
    {
        $event = new TestEvent();
        $event->setBroadcast(true);
        $event->setQueue(true);

        $array = $event->toArray();

        $this->assertArrayHasKey('event', $array);
        $this->assertArrayHasKey('occurred_at', $array);
        $this->assertArrayHasKey('should_broadcast', $array);
        $this->assertArrayHasKey('should_queue', $array);
        $this->assertArrayHasKey('data', $array);

        $this->assertEquals('TestEvent', $array['event']);
        $this->assertTrue($array['should_broadcast']);
        $this->assertTrue($array['should_queue']);
    }

    public function test_fluent_interface(): void
    {
        $event = new TestEvent();

        $result = $event->setBroadcast(true)->setQueue(true);

        $this->assertInstanceOf(TestEvent::class, $result);
        $this->assertTrue($result->shouldBroadcast());
        $this->assertTrue($result->shouldQueue());
    }
}

/**
 * 测试用事件类.
 */
class TestEvent implements Event
{
    use EventTrait;

    public function __construct()
    {
        $this->initializeEventTrait();
    }
}

/**
 * 带数据的测试事件类.
 */
class TestEventWithData implements Event
{
    use EventTrait;

    public function __construct(
        protected array $eventData = []
    ) {
        $this->initializeEventTrait();
    }

    public function getData(): array
    {
        return $this->eventData;
    }
}
