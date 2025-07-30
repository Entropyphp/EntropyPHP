<?php

declare(strict_types=1);

namespace Entropy\Tests\Event;

use Entropy\Event\StoppableEvent;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\StoppableEventInterface;

class StoppableEventTest extends TestCase
{
    public function testImplementsStoppableEventInterface(): void
    {
        $event = new class extends StoppableEvent {
            public const NAME = 'test.stoppable.event';
        };

        $this->assertInstanceOf(StoppableEventInterface::class, $event);
    }

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

    public function testInheritsEventNameFromParent(): void
    {
        $event = new class extends StoppableEvent {
            public const NAME = 'test.stoppable.event';
        };

        $this->assertSame('test.stoppable.event', $event->eventName());
    }
}
