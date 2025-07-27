<?php

declare(strict_types=1);

namespace Entropy\Tests\Kernel;

use Entropy\Kernel\KernelMiddleware;
use Entropy\Middleware\CombinedMiddleware;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;

class KernelMiddlewareTest extends TestCase
{
    private ContainerInterface|MockObject $container;
    private ServerRequestInterface|MockObject $request;
    private ResponseInterface|MockObject $response;
    private KernelMiddleware $kernel;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testHandleProcessesRequestThroughMiddlewareStack(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($this->request, $this->isInstanceOf(CombinedMiddleware::class))
            ->willReturn($this->response);

        $this->kernel->setCallbacks([$middleware]);
        $result = $this->kernel->handle($this->request);
        $this->assertSame($this->response, $result);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testHandleThrowsExceptionWhenNoMiddlewareHandlesRequest(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Aucun middleware n\'a intercepté cette requête');

        // Simulate a second call to handle to trigger the exception
        $kernel = new class ($this->container) extends KernelMiddleware {
            private int $callCount = 0;

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->callCount++;
                return parent::handle($request);
            }
        };

        $kernel->handle($this->request);
        $kernel->handle($this->request); // This should trigger the exception
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
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
        $property->setValue($this->kernel, $this->request);

        $this->assertSame($this->request, $this->kernel->getRequest());
    }

    /**
     * @throws Exception
     */
    public function testSetRequestReturnsSelfForMethodChaining(): void
    {
        $newRequest = $this->createMock(ServerRequestInterface::class);
        $result = $this->kernel->setRequest($newRequest);

        $this->assertSame($this->kernel, $result);
        $this->assertSame($newRequest, $this->kernel->getRequest());
    }

    /**
     * @throws \Throwable
     */
    public function testHandleExceptionRethrowsException(): void
    {
        $exception = new RuntimeException('Test exception');

        $this->expectExceptionObject($exception);

        $this->kernel->handleException($exception, $this->request);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->kernel = new KernelMiddleware($this->container);
    }
}
