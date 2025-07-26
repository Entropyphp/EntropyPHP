<?php

declare(strict_types=1);

namespace Entropy\Tests\Event;

use Entropy\Event\Event;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{
    public function testEventNameReturnsConstantName(): void
    {
        // Create a concrete implementation of an Event for testing
        $concreteEvent = new class extends Event {
            public const NAME = 'test.event';
        };

        $this->assertSame('test.event', $concreteEvent->eventName());
    }
}
