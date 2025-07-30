<?php

namespace Entropy\Tests\Event;

use Entropy\Event\ControllerEvent;
use Entropy\Kernel\KernelInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class ControllerEventTest extends TestCase
{
    private KernelInterface $kernel;
    private ServerRequestInterface $request;

    public function testConstructor(): void
    {
        $event = new ControllerEvent($this->kernel, 'controller', $this->request);

        $this->assertSame($this->kernel, $event->getKernel());
        $this->assertSame($this->request, $event->getRequest());
        $this->assertSame('controller', $event->getController());
        $this->assertFalse($event->hasResponse());
        $this->assertNull($event->getResponse());
    }

    public function testSetController(): void
    {
        $event = new ControllerEvent($this->kernel, 'controller', $this->request);

        $event->setController('controller2');

        $this->assertSame('controller2', $event->getController());
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
