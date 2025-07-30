<?php

namespace Entropy\Tests\Event;

use Entropy\Event\Events;
use Entropy\Event\FinishRequestEvent;
use Entropy\Kernel\KernelInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class FinishRequestEventTest extends TestCase
{
    private KernelInterface $kernel;
    private ServerRequestInterface $request;

    public function testEventName(): void
    {
        $event = new FinishRequestEvent($this->kernel, $this->request);
        $this->assertSame(Events::FINISH, $event->eventName());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
    }
}
