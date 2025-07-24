<?php

declare(strict_types=1);

namespace Entropy\Tests\Kernel;

use Entropy\Kernel\KernelMiddleware;
use Entropy\Middleware\CombinedMiddleware;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class KernelMiddlewareTest extends TestCase
{
    private ContainerInterface|MockObject $container;
    private ServerRequestInterface|MockObject $request;
    private ResponseInterface|MockObject $response;
    private KernelMiddleware $kernel;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->kernel = new KernelMiddleware($this->container);
    }

    public function testHandleProcessesRequestThroughMiddlewareStack(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        
        // Mock CombinedMiddleware to return our response
        $combinedMiddleware = $this->createMock(CombinedMiddleware::class);
        $combinedMiddleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->kernel)
            ->willReturn($this->response);

        // Mock container to return our middleware
        $this->container->expects($this->once())
            ->method('get')
            ->with(CombinedMiddleware::class)
            ->willReturn($combinedMiddleware);

        $result = $this->kernel->handle($this->request);
        $this->assertSame($this->response, $result);
    }

    public function testHandleThrowsExceptionWhenNoMiddlewareHandlesRequest(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Aucun middleware n\'a intercepté cette requête');

        // Simulate a second call to handle to trigger the exception
        $kernel = new class($this->container) extends KernelMiddleware {
            private int $callCount = 0;
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->callCount++;
                if ($this->callCount > 1) {
                    return parent::handle($request);
                }
                return parent::handle($request);
            }
        };

        $kernel->handle($this->request);
        $kernel->handle($this->request); // This should trigger the exception
    }

    public function testSetCallbacksWithValidMiddlewares(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        
        $result = $this->kernel->setCallbacks([$middleware1, $middleware2]);
        
        $this->assertSame($this->kernel, $result);
        // Additional assertions to verify middlewares were set could be added if we had getter methods
    }

    public function testSetCallbacksWithEmptyArrayThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Une liste de middlewares doit être passer à ce Kernel');
        
        $this->kernel->setCallbacks([]);
    }

    public function testPipeMethodReturnsSelfForMethodChaining(): void
    {
        $result = $this->kernel->pipe('/api', 'ApiMiddleware');
        $this->assertSame($this->kernel, $result);
    }

    public function testGetContainerReturnsInjectedContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $kernel = new KernelMiddleware($container);
        
        $this->assertSame($container, $kernel->getContainer());
    }

    public function testGetRequestReturnsCurrentRequest(): void
    {
        $reflection = new \ReflectionClass($this->kernel);
        $property = $reflection->getProperty('request');
        $property->setAccessible(true);
        $property->setValue($this->kernel, $this->request);
        
        $this->assertSame($this->request, $this->kernel->getRequest());
    }

    public function testSetRequestReturnsSelfForMethodChaining(): void
    {
        $newRequest = $this->createMock(ServerRequestInterface::class);
        $result = $this->kernel->setRequest($newRequest);
        
        $this->assertSame($this->kernel, $result);
        $this->assertSame($newRequest, $this->kernel->getRequest());
    }

    public function testHandleExceptionRethrowsException(): void
    {
        $exception = new RuntimeException('Test exception');
        
        $this->expectExceptionObject($exception);
        
        $this->kernel->handleException($exception, $this->request);
    }

    public function testLazyPipeAddsRoutePrefixMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $routePrefix = '/admin';
        $middlewareClass = 'AdminMiddleware';
        
        // Use reflection to test protected method
        $kernel = new KernelMiddleware($container);
        $reflection = new \ReflectionClass($kernel);
        $method = $reflection->getMethod('lazyPipe');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($kernel, [$container, $routePrefix, $middlewareClass]);
        
        $this->assertSame($kernel, $result);
    }
}
