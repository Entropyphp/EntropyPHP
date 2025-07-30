<?php

namespace Entropy\Tests\Event;

use Entropy\Event\ViewEvent;
use Entropy\Kernel\KernelInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ViewEventTest extends TestCase
{
    private KernelInterface $kernel;
    private ServerRequestInterface $request;
    private ResponseInterface $response;

    public function testConstructor()
    {
        $event = new ViewEvent($this->kernel, $this->request, $this->response);

        $this->assertSame($this->kernel, $event->getKernel());
        $this->assertSame($this->request, $event->getRequest());
        $this->assertSame($this->response, $event->getResult());
        $this->assertFalse($event->hasResponse());
        $this->assertNull($event->getResponse());
    }

    /**
     * @throws Exception
     */
    public function testSetResult()
    {
        $response = $this->createMock(ResponseInterface::class);
        $event = new ViewEvent($this->kernel, $this->request, $this->response);

        $event->setResult($response);

        $this->assertFalse($event->hasResponse());
        $this->assertSame($response, $event->getResult());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }
}
