<?php

declare(strict_types=1);

namespace Entropy\Tests\Middleware;

use Entropy\Middleware\RoutePrefixMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutePrefixMiddlewareTest extends TestCase
{
    private ContainerInterface|MockObject $container;
    private ServerRequestInterface|MockObject $request;
    private RequestHandlerInterface|MockObject $handler;
    private ResponseInterface|MockObject $response;
    private MiddlewareInterface|MockObject $middleware;
    private UriInterface|MockObject $uri;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->middleware = $this->createMock(MiddlewareInterface::class);
        $this->uri = $this->createMock(UriInterface::class);
    }

    public function testProcessWithMatchingPrefix(): void
    {
        $prefix = '/api';
        $path = '/api/users';
        $middlewareService = 'api.middleware';

        // Set up URI mock
        $this->uri->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        // Set up request mock
        $this->request->expects($this->once())
            ->method('getUri')
            ->willReturn($this->uri);

        // Set up container to return the middleware
        $this->container->expects($this->once())
            ->method('get')
            ->with($middlewareService)
            ->willReturn($this->middleware);

        // Set up middleware to return response
        $this->middleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->handler)
            ->willReturn($this->response);

        $routePrefixMiddleware = new RoutePrefixMiddleware(
            $this->container,
            $prefix,
            $middlewareService
        );

        $result = $routePrefixMiddleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }

    public function testProcessWithNonMatchingPrefix(): void
    {
        $prefix = '/api';
        $path = '/admin/users';
        $middlewareService = 'api.middleware';

        // Set up URI mock
        $this->uri->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        // Set up request mock
        $this->request->expects($this->once())
            ->method('getUri')
            ->willReturn($this->uri);

        // Container should not be called
        $this->container->expects($this->never())
            ->method('get');

        // Handler should be called directly
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $routePrefixMiddleware = new RoutePrefixMiddleware(
            $this->container,
            $prefix,
            $middlewareService
        );

        $result = $routePrefixMiddleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }

    public function testProcessWithEmptyPath(): void
    {
        $prefix = '';
        $path = '/any/path';
        $middlewareService = 'api.middleware';

        // Set up URI mock
        $this->uri->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        // Set up request mock
        $this->request->expects($this->once())
            ->method('getUri')
            ->willReturn($this->uri);

        // With empty prefix, all paths should match
        $this->container->expects($this->once())
            ->method('get')
            ->with($middlewareService)
            ->willReturn($this->middleware);

        $this->middleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->handler)
            ->willReturn($this->response);

        $routePrefixMiddleware = new RoutePrefixMiddleware(
            $this->container,
            $prefix,
            $middlewareService
        );

        $result = $routePrefixMiddleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }

    public function testProcessWithCaseInsensitiveMatching(): void
    {
        $prefix = '/API';
        $path = '/api/users';
        $middlewareService = 'api.middleware';

        // Set up URI mock
        $this->uri->expects($this->once())
            ->method('getPath')
            ->willReturn($path);

        // Set up request mock
        $this->request->expects($this->once())
            ->method('getUri')
            ->willReturn($this->uri);

        // Container should return the middleware despite case difference
        $this->container->expects($this->once())
            ->method('get')
            ->with($middlewareService)
            ->willReturn($this->middleware);

        $this->middleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->handler)
            ->willReturn($this->response);

        $routePrefixMiddleware = new RoutePrefixMiddleware(
            $this->container,
            $prefix,
            $middlewareService
        );

        $result = $routePrefixMiddleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $result);
    }
}
