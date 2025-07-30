<?php

namespace Entropy\Tests\Event;

use Entropy\Event\ControllerParamsEvent;
use Entropy\Kernel\KernelInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ControllerParamsEventTest extends TestCase
{
    private KernelInterface $kernel;
    private ServerRequestInterface $request;

    public function testGetParams()
    {
        $callable = fn() => null;
        $event = new ControllerParamsEvent($this->kernel, $callable, ['param'], $this->request);

        $this->assertSame(['param'], $event->getParams());
    }

    public function testSetParams()
    {
        $callable = fn() => null;
        $event = new ControllerParamsEvent($this->kernel, $callable, ['param'], $this->request);

        $event->setParams(['param2']);
        $this->assertSame(['param2'], $event->getParams());
    }

    public function testConstructor()
    {
        $callable = fn() => null;
        $event = new ControllerParamsEvent($this->kernel, $callable, [], $this->request);

        $this->assertSame($this->kernel, $event->getKernel());
        $this->assertSame($this->request, $event->getRequest());
        $this->assertSame($callable, $event->getController());
        $this->assertFalse($event->hasResponse());
        $this->assertNull($event->getResponse());
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
