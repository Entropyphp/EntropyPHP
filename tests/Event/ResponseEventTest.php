<?php

declare(strict_types=1);

namespace Entropy\Tests\Event;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Entropy\Event\ResponseEvent;
use Entropy\Kernel\KernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseEventTest extends TestCase
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
        $event = new ResponseEvent($this->kernel, $this->request, $this->response);

        $this->assertSame($this->kernel, $event->getKernel());
        $this->assertSame($this->request, $event->getRequest());
        $this->assertSame($this->response, $event->getResponse());
    }

    /**
     * @throws Exception
     */
    public function testSetResponse(): void
    {
        $event = new ResponseEvent($this->kernel, $this->request, $this->response);
        $newResponse = $this->createMock(ResponseInterface::class);

        $event->setResponse($newResponse);

        $this->assertTrue($event->hasResponse());
        $this->assertSame($newResponse, $event->getResponse());
    }

    public function testIsPropagationStopped(): void
    {
        $event = new ResponseEvent($this->kernel, $this->request, $this->response);

        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * @throws Exception
     */
    public function testSetRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $event = new ResponseEvent($this->kernel, $this->request, $this->response);

        $this->kernel->expects($this->once())->method('setRequest')->with($request);

        $event->setRequest($request);

        $this->assertSame($request, $event->getRequest());
    }
}
