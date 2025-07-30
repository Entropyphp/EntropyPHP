<?php

declare(strict_types=1);

namespace Entropy\Tests\Event;

use Entropy\Event\StoppableEvent;
use PHPUnit\Framework\TestCase;

class StoppableEventTest extends TestCase
{
    public function testPropagationIsNotStoppedByDefault(): void
    {
        $event = new StoppableEvent();

        $this->assertFalse($event->isPropagationStopped());
    }

    public function testStopPropagationSetsFlag(): void
    {
        $event = new StoppableEvent();

        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testIsInstance(): void
    {
        $event = new class extends StoppableEvent {
            public const NAME = 'test.event';
        };

        $this->assertSame('test.event', $event->eventName());
        $this->assertInstanceOf(StoppableEvent::class, $event);
    }
}
