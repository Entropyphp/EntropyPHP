<?php

namespace Entropy\Tests\Event;

use Entropy\Event\AppEvent;
use Entropy\Event\Events;
use Entropy\Kernel\KernelInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class AppEventTest extends TestCase
{
    private KernelInterface $kernel;

    public function testGetKernel()
    {
        $event = new AppEvent($this->kernel);

        $this->assertSame($this->kernel, $event->getKernel());
    }

    public function testConstruct()
    {
        $event = new AppEvent($this->kernel);

        $this->assertSame($this->kernel, $event->getKernel());
    }

    public function testEventName()
    {
        $event = new AppEvent($this->kernel);

        $this->assertSame(Events::REQUEST, $event->eventName());
    }

    public function testIsPropagationStopped(): void
    {
        $event = new AppEvent($this->kernel);

        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
    }
}
