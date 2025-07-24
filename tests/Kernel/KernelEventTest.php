<?php

declare(strict_types=1);

namespace Entropy\Tests\Kernel;

use Entropy\Event\ControllerEvent;
use Entropy\Event\ControllerParamsEvent;
use Entropy\Event\ExceptionEvent;
use Entropy\Event\FinishRequestEvent;
use Entropy\Event\RequestEvent;
use Entropy\Event\ResponseEvent;
use Entropy\Event\ViewEvent;
use Entropy\Invoker\ParameterResolver\RequestParamResolver;
use Entropy\Kernel\KernelEvent;
use Exception;
use Invoker\CallableResolver;
use Invoker\ParameterResolver\ParameterResolver;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\Reflection\CallableReflection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

class KernelEventTest extends TestCase
{
    private EventDispatcherInterface|MockObject $dispatcher;
    private CallableResolver|MockObject $callableResolver;
    private ParameterResolver|MockObject $paramsResolver;
    private ContainerInterface|MockObject $container;
    private KernelEvent $kernel;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->callableResolver = $this->createMock(CallableResolver::class);
        $this->paramsResolver = $this->createMock(ResolverChain::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->kernel = new KernelEvent(
            $this->dispatcher,
            $this->callableResolver,
            $this->paramsResolver,
            $this->container
        );
    }

    public function testGetDispatcher(): void
    {
        $this->assertSame($this->dispatcher, $this->kernel->getDispatcher());
    }

    public function testGetContainer(): void
    {
        $this->assertSame($this->container, $this->kernel->getContainer());
    }

    public function testSetAndGetRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        
        $result = $this->kernel->setRequest($request);
        
        $this->assertSame($this->kernel, $result);
        $this->assertSame($request, $this->kernel->getRequest());
    }

    public function testSetCallbacks(): void
    {
        $callbacks = [
            $this->createMock(\stdClass::class),
            $this->createMock(\stdClass::class),
        ];

        $this->dispatcher->expects($this->exactly(2))
            ->method('addSubscriber')
            ->withConsecutive(
                [$callbacks[0]],
                [$callbacks[1]]
            );

        $result = $this->kernel->setCallbacks($callbacks);
        
        $this->assertSame($this->kernel, $result);
    }

    public function testSetCallbacksThrowsExceptionWhenEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Une liste de listeners doit être passer à ce Kernel");
        
        $this->kernel->setCallbacks([]);
    }

    public function testHandleWithResponseFromRequestEvent(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        
        // Mock RequestEvent
        $requestEvent = $this->createMock(RequestEvent::class);
        $requestEvent->expects($this->once())
            ->method('hasResponse')
            ->willReturn(true);
        $requestEvent->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);
            
        // Mock ResponseEvent
        $responseEvent = $this->createMock(ResponseEvent::class);
        $responseEvent->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);
            
        // Set up dispatcher expectations
        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(RequestEvent::class)],
                [$this->isInstanceOf(ResponseEvent::class)]
            )
            ->willReturnOnConsecutiveCalls($requestEvent, $responseEvent);
            
        // Set up finishRequest expectation
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(FinishRequestEvent::class));
            
        $result = $this->kernel->handle($request);
        
        $this->assertSame($response, $result);
    }

    public function testHandleExceptionWithResponse(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $exception = new Exception('Test exception');
        $response = $this->createMock(ResponseInterface::class);
        
        // Set up the request in the kernel
        $this->kernel->setRequest($request);
        
        // Mock ExceptionEvent
        $exceptionEvent = $this->createMock(ExceptionEvent::class);
        $exceptionEvent->expects($this->once())
            ->method('hasResponse')
            ->willReturn(true);
        $exceptionEvent->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);
        $exceptionEvent->expects($this->once())
            ->method('getException')
            ->willReturn($exception);
            
        // Mock ResponseEvent
        $responseEvent = $this->createMock(ResponseEvent::class);
        $responseEvent->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);
            
        // Set up dispatcher expectations
        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(ExceptionEvent::class)],
                [$this->isInstanceOf(ResponseEvent::class)]
            )
            ->willReturnOnConsecutiveCalls($exceptionEvent, $responseEvent);
            
        // Set up finishRequest expectation
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(FinishRequestEvent::class));
            
        $result = $this->kernel->handleException($exception, $request);
        
        $this->assertSame($response, $result);
    }
}