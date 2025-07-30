<?php

namespace Entropy\Tests\Event;

use Entropy\Event\Events;
use Entropy\Event\ExceptionEvent;
use Entropy\Kernel\KernelInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class ExceptionEventTest extends TestCase
{
    private KernelInterface $kernel;
    private ServerRequestInterface $request;
    private Throwable $exception;

    public function testConstruct()
    {
        $event = new ExceptionEvent($this->kernel, $this->request, $this->exception);

        $this->assertSame($this->kernel, $event->getKernel());
        $this->assertSame($this->request, $event->getRequest());
        $this->assertSame($this->exception, $event->getException());
        $this->assertFalse($event->hasResponse());
        $this->assertNull($event->getResponse());
    }

    /**
     * @throws Exception
     */
    public function testSetException()
    {
        $exception = $this->createMock(Throwable::class);
        $event = new ExceptionEvent($this->kernel, $this->request, $this->exception);

        $this->assertSame($this->exception, $event->getException());

        $event->setException($exception);

        $this->assertSame($exception, $event->getException());
    }

    public function testGetException()
    {
        $event = new ExceptionEvent($this->kernel, $this->request, $this->exception);

        $this->assertSame($this->exception, $event->getException());
    }

    public function testEventName()
    {
        $event = new ExceptionEvent($this->kernel, $this->request, $this->exception);

        $this->assertSame(Events::EXCEPTION, $event->eventName());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->exception = $this->createMock(Exception::class);
    }
}
