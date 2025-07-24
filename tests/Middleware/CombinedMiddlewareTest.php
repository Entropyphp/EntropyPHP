<?php

declare(strict_types=1);

namespace Entropy\Tests\Middleware;

use Entropy\Middleware\CombinedMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CombinedMiddlewareTest extends TestCase
{
    private ContainerInterface|MockObject $container;
    private ServerRequestInterface|MockObject $request;
    private RequestHandlerInterface|MockObject $handler;
    private ResponseInterface|MockObject $response;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
    }

    public function testProcessStoresHandlerAndCallsHandle(): void
    {
        // Create a partial mock to test that process calls handle
        $middleware = $this->getMockBuilder(CombinedMiddleware::class)
            ->setConstructorArgs([$this->container, []])
            ->onlyMethods(['handle'])
            ->getMock();
            
        $middleware->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);
            
        $result = $middleware->process($this->request, $this->handler);
        
        $this->assertSame($this->response, $result);
    }
    
    public function testHandleWithNoMiddlewaresDelegatesToHandler(): void
    {
        $middleware = new CombinedMiddleware($this->container, []);
        
        // Set up handler to return a response
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);
            
        // Process the request
        $middleware->process($this->request, $this->handler);
        
        // Handle the request (should delegate to handler)
        $result = $middleware->handle($this->request);
        
        $this->assertSame($this->response, $result);
    }
    
    public function testHandleWithMiddlewareInterface(): void
    {
        // Create a mock middleware
        $mockMiddleware = $this->createMock(MiddlewareInterface::class);
        
        // Set up the mock middleware to return a response
        $mockMiddleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(CombinedMiddleware::class))
            ->willReturn($this->response);
            
        // Create the combined middleware with the mock middleware
        $middleware = new CombinedMiddleware($this->container, [$mockMiddleware]);
        
        // Process the request
        $middleware->process($this->request, $this->handler);
        
        // Handle the request (should process the mock middleware)
        $result = $middleware->handle($this->request);
        
        $this->assertSame($this->response, $result);
    }
    
    public function testHandleWithCallableMiddleware(): void
    {
        // Create a callable middleware
        $callableMiddleware = function (ServerRequestInterface $request, callable $next) {
            return $this->response;
        };
        
        // Create the combined middleware with the callable middleware
        $middleware = new CombinedMiddleware($this->container, [$callableMiddleware]);
        
        // Process the request
        $middleware->process($this->request, $this->handler);
        
        // Handle the request (should call the callable middleware)
        $result = $middleware->handle($this->request);
        
        $this->assertSame($this->response, $result);
    }
    
    public function testHandleWithStringMiddleware(): void
    {
        // Create a mock middleware
        $mockMiddleware = $this->createMock(MiddlewareInterface::class);
        
        // Set up the container to return the mock middleware
        $this->container->expects($this->once())
            ->method('get')
            ->with('middleware.service')
            ->willReturn($mockMiddleware);
            
        // Set up the mock middleware to return a response
        $mockMiddleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(CombinedMiddleware::class))
            ->willReturn($this->response);
            
        // Create the combined middleware with the string middleware
        $middleware = new CombinedMiddleware($this->container, ['middleware.service']);
        
        // Process the request
        $middleware->process($this->request, $this->handler);
        
        // Handle the request (should resolve and process the mock middleware)
        $result = $middleware->handle($this->request);
        
        $this->assertSame($this->response, $result);
    }
    
    public function testMiddlewareMethodAddsMiddleware(): void
    {
        // Create a mock middleware
        $mockMiddleware = $this->createMock(MiddlewareInterface::class);
        
        // Create the combined middleware with no middlewares
        $middleware = new CombinedMiddleware($this->container, []);
        
        // Add a middleware
        $result = $middleware->middleware($mockMiddleware);
        
        // Check that the method returns $this for chaining
        $this->assertSame($middleware, $result);
        
        // Set up the mock middleware to return a response
        $mockMiddleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(CombinedMiddleware::class))
            ->willReturn($this->response);
            
        // Process the request
        $middleware->process($this->request, $this->handler);
        
        // Handle the request (should process the mock middleware)
        $result = $middleware->handle($this->request);
        
        $this->assertSame($this->response, $result);
    }
    
    public function testMiddlewaresMethodAddsMultipleMiddlewares(): void
    {
        // Create mock middlewares
        $mockMiddleware1 = $this->createMock(MiddlewareInterface::class);
        $mockMiddleware2 = $this->createMock(MiddlewareInterface::class);
        
        // Create the combined middleware with no middlewares
        $middleware = new CombinedMiddleware($this->container, []);
        
        // Add multiple middlewares
        $result = $middleware->middlewares([$mockMiddleware1, $mockMiddleware2]);
        
        // Check that the method returns $this for chaining
        $this->assertSame($middleware, $result);
        
        // Set up the first mock middleware to process the request and call the next middleware
        $mockMiddleware1->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(CombinedMiddleware::class))
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });
            
        // Set up the second mock middleware to return a response
        $mockMiddleware2->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(CombinedMiddleware::class))
            ->willReturn($this->response);
            
        // Process the request
        $middleware->process($this->request, $this->handler);
        
        // Handle the request (should process both middlewares)
        $result = $middleware->handle($this->request);
        
        $this->assertSame($this->response, $result);
    }
    
    public function testPrependMiddlewareAddsMiddlewareToBeginning(): void
    {
        // Create mock middlewares
        $mockMiddleware1 = $this->createMock(MiddlewareInterface::class);
        $mockMiddleware2 = $this->createMock(MiddlewareInterface::class);
        
        // Create the combined middleware with one middleware
        $middleware = new CombinedMiddleware($this->container, [$mockMiddleware1]);
        
        // Prepend another middleware
        $result = $middleware->prependMiddleware($mockMiddleware2);
        
        // Check that the method returns $this for chaining
        $this->assertSame($middleware, $result);
        
        // Set up the second mock middleware (which should be processed first) to process the request and call the next middleware
        $mockMiddleware2->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(CombinedMiddleware::class))
            ->willReturnCallback(function ($request, $handler) {
                return $handler->handle($request);
            });
            
        // Set up the first mock middleware to return a response
        $mockMiddleware1->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(CombinedMiddleware::class))
            ->willReturn($this->response);
            
        // Process the request
        $middleware->process($this->request, $this->handler);
        
        // Handle the request (should process both middlewares in the correct order)
        $result = $middleware->handle($this->request);
        
        $this->assertSame($this->response, $result);
    }
    
    public function testGetMiddlewareStackReturnsAllMiddlewares(): void
    {
        // Create mock middlewares
        $mockMiddleware1 = $this->createMock(MiddlewareInterface::class);
        $mockMiddleware2 = $this->createMock(MiddlewareInterface::class);
        
        // Create the combined middleware with the mock middlewares
        $middleware = new CombinedMiddleware($this->container, [$mockMiddleware1, $mockMiddleware2]);
        
        // Get the middleware stack
        $stack = $middleware->getMiddlewareStack();
        
        // Check that the stack contains the middlewares in the correct order
        $this->assertIsIterable($stack);
        $this->assertCount(2, $stack);
        $this->assertSame($mockMiddleware1, $stack[0]);
        $this->assertSame($mockMiddleware2, $stack[1]);
    }
}