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
use Entropy\Kernel\KernelEvent;
use Exception;
use Invoker\CallableResolver;
use Invoker\Exception\NotCallableException;
use Invoker\ParameterResolver\ResolverChain;
use Entropy\Event\EventDispatcher;
use Entropy\Event\EventSubscriberInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use ReflectionException;
use RuntimeException;

class KernelEventTest extends TestCase
{
    private EventDispatcherInterface|MockObject $dispatcher;
    private ContainerInterface|MockObject $container;
    private KernelEvent $kernel;
    private ServerRequestInterface|MockObject $request;

    public function testGetDispatcher(): void
    {
        $this->assertSame($this->dispatcher, $this->kernel->getDispatcher());
    }

    public function testGetContainer(): void
    {
        $this->assertSame($this->container, $this->kernel->getContainer());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testSetAndGetRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $result = $this->kernel->setRequest($request);

        $this->assertSame($this->kernel, $result);
        $this->assertSame($request, $this->kernel->getRequest());
    }

    /**
     * @throws ReflectionException
     * @throws NotCallableException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testSetCallbacks(): void
    {
        $callbacks = [
            $this->createMock(EventSubscriberInterface::class),
            $this->createMock(EventSubscriberInterface::class),
        ];

        $invokedCount = $this->exactly(2);
        $dispatcher = $this->dispatcher;
        $this->dispatcher
            ->expects($invokedCount)
            ->method('addSubscriber')
            ->willReturnCallback(function ($parameter) use ($invokedCount, $callbacks, $dispatcher) {
                $currentInvocationCount = $invokedCount->numberOfInvocations();
                $currentExpectation = $callbacks[$currentInvocationCount - 1];
                $this->assertSame($currentExpectation, $parameter);
                return $dispatcher;
            });


        $result = $this->kernel->setCallbacks($callbacks);

        $this->assertSame($this->kernel, $result);
    }

    /**
     * @throws NotCallableException
     * @throws ReflectionException
     */
    public function testSetCallbacksThrowsExceptionWhenEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Une liste de listeners doit être passer à ce Kernel");

        $this->kernel->setCallbacks([]);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws Exception
     */
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

        $finishRequestEvent = $this->createMock(FinishRequestEvent::class);

        // Set up dispatcher expectations
        $expectations = [$requestEvent, $responseEvent, $finishRequestEvent];
        $invokedCount = $this->exactly(count($expectations));
        $this->dispatcher->expects($invokedCount)
            ->method('dispatch')
            ->willReturnCallback(function ($parameter) use ($invokedCount, $expectations) {
                $expectationsClass = [
                    RequestEvent::class,
                    ResponseEvent::class,
                    FinishRequestEvent::class,
                ];
                $currentInvocationCount = $invokedCount->numberOfInvocations();
                $currentExpectation = $expectations[$currentInvocationCount - 1];
                $currentExpectationClass = $expectationsClass[$currentInvocationCount - 1];
                $this->assertInstanceOf($currentExpectationClass, $parameter);
                return $currentExpectation;
            });

        $result = $this->kernel->handle($request);

        $this->assertEquals($response, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
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

        $finishRequestEvent = $this->createMock(FinishRequestEvent::class);

        // Set up dispatcher expectations
        $expectations = [$exceptionEvent, $responseEvent, $finishRequestEvent];
        $invokedCount = $this->exactly(count($expectations));
        $this->dispatcher->expects($invokedCount)
            ->method('dispatch')
            ->willReturnCallback(function ($parameter) use ($invokedCount, $expectations) {
                $expectationsClass = [
                    ExceptionEvent::class,
                    ResponseEvent::class,
                    FinishRequestEvent::class,
                ];
                $currentInvocationCount = $invokedCount->numberOfInvocations();
                $currentExpectation = $expectations[$currentInvocationCount - 1];
                $currentExpectationClass = $expectationsClass[$currentInvocationCount - 1];
                $this->assertInstanceOf($currentExpectationClass, $parameter);
                return $currentExpectation;
            });

        $result = $this->kernel->handleException($exception, $request);

        $this->assertSame($response, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testHandleExceptionWillThrowException(): void
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

        // Set up dispatcher expectations
        $expectations = [$exceptionEvent, $responseEvent];
        $invokedCount = $this->exactly(count($expectations));
        $this->dispatcher->expects($invokedCount)
            ->method('dispatch')
            ->willReturnCallback(function ($parameter) use ($invokedCount, $expectations, $exception) {
                $expectationsClass = [
                    ExceptionEvent::class,
                    ResponseEvent::class,
                ];
                $currentInvocationCount = $invokedCount->numberOfInvocations();
                $currentExpectation = $expectations[$currentInvocationCount - 1];
                $currentExpectationClass = $expectationsClass[$currentInvocationCount - 1];
                $this->assertInstanceOf($currentExpectationClass, $parameter);
                if ($currentExpectationClass === ResponseEvent::class) {
                    throw $exception;
                }
                return $currentExpectation;
            });

        $result = $this->kernel->handleException($exception, $request);

        $this->assertSame($response, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testHandleExceptionWithoutResponse(): void
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
            ->willReturn(false);
        $exceptionEvent->expects($this->once())
            ->method('getException')
            ->willReturn($exception);

        $finishRequestEvent = $this->createMock(FinishRequestEvent::class);

        // Set up dispatcher expectations
        $expectations = [$exceptionEvent, $finishRequestEvent];
        $invokedCount = $this->exactly(count($expectations));
        $this->dispatcher->expects($invokedCount)
            ->method('dispatch')
            ->willReturnCallback(function ($parameter) use ($invokedCount, $expectations) {
                $expectationsClass = [
                    ExceptionEvent::class,
                    FinishRequestEvent::class,
                ];
                $currentInvocationCount = $invokedCount->numberOfInvocations();
                $currentExpectation = $expectations[$currentInvocationCount - 1];
                $currentExpectationClass = $expectationsClass[$currentInvocationCount - 1];
                $this->assertInstanceOf($currentExpectationClass, $parameter);
                return $currentExpectation;
            });

        $this->expectException($exception::class);
        $result = $this->kernel->handleException($exception, $request);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \Exception
     */
    public function testHandleWithoutController(): void
    {
        $requestEvent = new RequestEvent($this->kernel, $this->request);

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())
            ->method('getPath')
            ->willReturn('/test');

        $this->request
            ->expects($this->once())
            ->method('getAttribute')
            ->with('_controller')
            ->willReturn(null);
        $this->request
            ->expects($this->once())
            ->method('getUri')
            ->willReturn($uri);

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn($requestEvent);

        $this->expectException(RuntimeException::class);
        $this->kernel->handle($this->request);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \Exception
     */
    public function testHandleWithControllerAndParams(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $resolvedController = function (
            string $name,
            ServerRequestInterface $request
        ) use ($response): ResponseInterface {
            return $response;
        };

        $this->request->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnCallback(function ($name) {
                return match ($name) {
                    '_controller' => 'some_controller',
                    '_params' => ['name' => 'test'],
                    default => null,
                };
            });

        $callableResolver = $this->createMock(CallableResolver::class);
        $callableResolver->expects($this->once())
            ->method('resolve')
            ->with('some_controller')
            ->willReturn($resolvedController);

        $paramsResolver = $this->createMock(ResolverChain::class);
        $paramsResolver->expects($this->once())
            ->method('getParameters')
            ->willReturn(['test', $this->request]);

        $this->kernel = new KernelEvent(
            $this->dispatcher,
            $callableResolver,
            $paramsResolver,
            $this->container
        );

        $responseEvent = $this->createMock(ResponseEvent::class);
        $responseEvent->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        // Set up dispatcher expectations for all events that will be dispatched
        $dispatcherCalls = [
            [
                'event' => RequestEvent::class,
                'return' => new RequestEvent($this->kernel, $this->request)
            ],
            [
                'event' => ControllerEvent::class,
                'return' => new ControllerEvent($this->kernel, $resolvedController, $this->request)
            ],
            [
                'event' => ControllerParamsEvent::class,
                'return' => new ControllerParamsEvent(
                    $this->kernel,
                    $resolvedController,
                    ['test', $this->request],
                    $this->request
                )
            ],
            [
                'event' => ResponseEvent::class,
                'return' => $responseEvent
            ],
            [
                'event' => FinishRequestEvent::class,
                'return' => new FinishRequestEvent($this->kernel, $this->request)
            ],
        ];

        $this->dispatcher
            ->expects($this->exactly(count($dispatcherCalls)))
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls(...array_map(fn($call) => $call['return'], $dispatcherCalls));

        $responseResult = $this->kernel->handle($this->request);

        $this->assertSame($response, $responseResult);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \Exception
     */
    public function testHandleWithStringResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $resolvedController = function (
            string $name,
            ServerRequestInterface $request
        ): string {
            return $name;
        };

        $this->request->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnCallback(function ($name) {
                return match ($name) {
                    '_controller' => 'some_controller',
                    '_params' => ['name' => 'test'],
                    default => null,
                };
            });

        $callableResolver = $this->createMock(CallableResolver::class);
        $callableResolver->expects($this->once())
            ->method('resolve')
            ->with('some_controller')
            ->willReturn($resolvedController);

        $paramsResolver = $this->createMock(ResolverChain::class);
        $paramsResolver->expects($this->once())
            ->method('getParameters')
            ->willReturn(['test', $this->request]);

        $this->kernel = new KernelEvent(
            $this->dispatcher,
            $callableResolver,
            $paramsResolver,
            $this->container
        );

        $viewEvent = $this->createMock(ViewEvent::class);
        $viewEvent->expects($this->once())
            ->method('hasResponse')
            ->willReturn(true);
        $viewEvent->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        $responseEvent = $this->createMock(ResponseEvent::class);
        $responseEvent->expects($this->once())
            ->method('getResponse')
            ->willReturn($response);

        // Set up dispatcher expectations for all events that will be dispatched
        $dispatcherCalls = [
            [
                'event' => RequestEvent::class,
                'return' => new RequestEvent($this->kernel, $this->request)
            ],
            [
                'event' => ControllerEvent::class,
                'return' => new ControllerEvent($this->kernel, $resolvedController, $this->request)
            ],
            [
                'event' => ControllerParamsEvent::class,
                'return' => new ControllerParamsEvent(
                    $this->kernel,
                    $resolvedController,
                    ['test', $this->request],
                    $this->request
                )
            ],
            [
                'event' => ViewEvent::class,
                'return' => $viewEvent
            ],
            [
                'event' => ResponseEvent::class,
                'return' => $responseEvent
            ],
            [
                'event' => FinishRequestEvent::class,
                'return' => new FinishRequestEvent($this->kernel, $this->request)
            ],
        ];

        $this->dispatcher
            ->expects($this->exactly(count($dispatcherCalls)))
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls(...array_map(fn($call) => $call['return'], $dispatcherCalls));

        $responseResult = $this->kernel->handle($this->request);

        $this->assertSame($response, $responseResult);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \Exception
     */
    public function testHandleWithNullResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $resolvedController = function (
            string $name,
            ServerRequestInterface $request
        ): ?string {
            return null;
        };

        $this->request->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnCallback(function ($name) {
                return match ($name) {
                    '_controller' => 'some_controller',
                    '_params' => ['name' => 'test'],
                    default => null,
                };
            });

        $callableResolver = $this->createMock(CallableResolver::class);
        $callableResolver->expects($this->once())
            ->method('resolve')
            ->with('some_controller')
            ->willReturn($resolvedController);

        $paramsResolver = $this->createMock(ResolverChain::class);
        $paramsResolver->expects($this->once())
            ->method('getParameters')
            ->willReturn(['test', $this->request]);

        $this->kernel = new KernelEvent(
            $this->dispatcher,
            $callableResolver,
            $paramsResolver,
            $this->container
        );

        $viewEvent = $this->createMock(ViewEvent::class);
        $viewEvent->expects($this->once())
            ->method('hasResponse')
            ->willReturn(false);

        // Set up dispatcher expectations for all events that will be dispatched
        $dispatcherCalls = [
            [
                'event' => RequestEvent::class,
                'return' => new RequestEvent($this->kernel, $this->request)
            ],
            [
                'event' => ControllerEvent::class,
                'return' => new ControllerEvent($this->kernel, $resolvedController, $this->request)
            ],
            [
                'event' => ControllerParamsEvent::class,
                'return' => new ControllerParamsEvent(
                    $this->kernel,
                    $resolvedController,
                    ['test', $this->request],
                    $this->request
                )
            ],
            [
                'event' => ViewEvent::class,
                'return' => $viewEvent
            ],
        ];

        $this->dispatcher
            ->expects($this->exactly(count($dispatcherCalls)))
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls(...array_map(fn($call) => $call['return'], $dispatcherCalls));

        $this->expectException(\Exception::class);
        $this->kernel->handle($this->request);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $callableResolver = $this->createMock(CallableResolver::class);
        $paramsResolver = $this->createMock(ResolverChain::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->request = $this->createMock(ServerRequestInterface::class);

        $this->kernel = new KernelEvent(
            $this->dispatcher,
            $callableResolver,
            $paramsResolver,
            $this->container
        );
    }
}
