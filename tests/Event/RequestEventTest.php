<?php

declare(strict_types=1);

namespace Entropy\Tests\Event;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Entropy\Event\RequestEvent;
use Entropy\Kernel\KernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestEventTest extends TestCase
{
    private KernelInterface $kernel;
    private ServerRequestInterface $request;
    private ResponseInterface $response;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    public function testConstructor(): void
    {
        $event = new RequestEvent($this->kernel, $this->request);

        $this->assertSame($this->kernel, $event->getKernel());
        $this->assertSame($this->request, $event->getRequest());
        $this->assertFalse($event->hasResponse());
        $this->assertNull($event->getResponse());
    }

    public function testSetResponse(): void
    {
        $event = new RequestEvent($this->kernel, $this->request);

        $event->setResponse($this->response);

        $this->assertTrue($event->hasResponse());
        $this->assertSame($this->response, $event->getResponse());
    }

    public function testIsPropagationStopped(): void
    {
        $event = new RequestEvent($this->kernel, $this->request);

        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testGetKernel(): void
    {
        $event = new RequestEvent($this->kernel, $this->request);

        $this->assertSame($this->kernel, $event->getKernel());
    }

    public function testGetRequest(): void
    {
        $event = new RequestEvent($this->kernel, $this->request);

        $this->assertSame($this->request, $event->getRequest());
    }

    /**
     * @throws Exception
     */
    public function testSetRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $event = new RequestEvent($this->kernel, $this->request);

        $this->kernel->expects($this->once())->method('setRequest')->with($request);

        $event->setRequest($request);

        $this->assertSame($request, $event->getRequest());
    }
}
