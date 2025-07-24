<?php

declare(strict_types=1);

namespace Entropy\Tests\Middleware;

use Entropy\Middleware\MethodMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MethodMiddlewareTest extends TestCase
{
    private ServerRequestInterface|MockObject $request;
    private RequestHandlerInterface|MockObject $handler;
    private ResponseInterface|MockObject $response;
    private MethodMiddleware $middleware;

    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->middleware = new MethodMiddleware();
    }

    public function testProcessWithDeleteMethodInBody(): void
    {
        $parsedBody = ['_method' => 'DELETE'];
        $updatedRequest = $this->createMock(ServerRequestInterface::class);

        // Set up request with DELETE in parsed body
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($parsedBody);

        // Expect withMethod to be called with 'DELETE'
        $this->request->expects($this->once())
            ->method('withMethod')
            ->with('DELETE')
            ->willReturn($updatedRequest);

        // Handler should receive the updated request
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($updatedRequest)
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }

    public function testProcessWithPutMethodInBody(): void
    {
        $parsedBody = ['_method' => 'PUT'];
        $updatedRequest = $this->createMock(ServerRequestInterface::class);

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($parsedBody);

        $this->request->expects($this->once())
            ->method('withMethod')
            ->with('PUT')
            ->willReturn($updatedRequest);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($updatedRequest)
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }

    public function testProcessWithPatchMethodInBody(): void
    {
        $parsedBody = ['_method' => 'PATCH'];
        $updatedRequest = $this->createMock(ServerRequestInterface::class);

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($parsedBody);

        $this->request->expects($this->once())
            ->method('withMethod')
            ->with('PATCH')
            ->willReturn($updatedRequest);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($updatedRequest)
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }

    public function testProcessWithUnsupportedMethodInBody(): void
    {
        $parsedBody = ['_method' => 'UNSUPPORTED'];

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($parsedBody);

        // withMethod should not be called for unsupported methods
        $this->request->expects($this->never())
            ->method('withMethod');

        // Handler should receive the original request
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }

    public function testProcessWithoutMethodInBody(): void
    {
        $parsedBody = ['other_param' => 'value'];

        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn($parsedBody);

        // withMethod should not be called when _method is not in the body
        $this->request->expects($this->never())
            ->method('withMethod');

        // Handler should receive the original request
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }

    public function testProcessWithEmptyParsedBody(): void
    {
        $this->request->expects($this->once())
            ->method('getParsedBody')
            ->willReturn(null);

        // withMethod should not be called when parsed body is null
        $this->request->expects($this->never())
            ->method('withMethod');

        // Handler should receive the original request
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }
}
