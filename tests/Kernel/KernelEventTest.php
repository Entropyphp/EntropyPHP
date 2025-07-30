<?php

declare(strict_types=1);

namespace Entropy\Tests\Kernel;

use Entropy\Event\ExceptionEvent;
use Entropy\Event\FinishRequestEvent;
use Entropy\Event\RequestEvent;
use Entropy\Event\ResponseEvent;
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
