<?php

declare(strict_types=1);

namespace Entropy\Tests\Middleware;

use Entropy\Invoker\ParameterResolver\RequestParamResolver;
use Entropy\Middleware\RouteCallerMiddleware;
use Exception;
use GuzzleHttp\Psr7\Response;
use Invoker\Exception\NotCallableException;
use Invoker\Invoker;
use Invoker\ParameterResolver\ParameterResolver;
use Invoker\ParameterResolver\ResolverChain;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteCallerMiddlewareTest extends TestCase
{
    private ContainerInterface|MockObject $container;
    private RouteCallerMiddleware $middleware;
    private ServerRequestInterface|MockObject $request;
    private RequestHandlerInterface|MockObject $handler;
    private ParameterResolver|MockObject $paramsResolver;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotCallableException
     * @throws NotFoundExceptionInterface
     */
    public function testProcessWithResponseInterface(): void
    {
        $callback = function () {
            return new Response(200, [], 'Test response');
        };

        $params = ['param1' => 'value1'];

        // Set up request attributes
        $expectations = [$callback, $params];
        $invokedCount = $this->exactly(count($expectations));
        $this->request->expects($invokedCount)
            ->method('getAttribute')
            ->willReturnCallback(function ($parameter) use ($expectations, $invokedCount) {

                $expectedParameters = [
                    '_controller',
                    '_params',
                ];
                $currentInvocationCount = $invokedCount->numberOfInvocations();
                $currentExpectation = $expectations[$currentInvocationCount - 1];
                $currentExpectedParameter = $expectedParameters[$currentInvocationCount - 1];
                $this->assertSame($currentExpectedParameter, $parameter);
                return $currentExpectation;
            })
            ->willReturnOnConsecutiveCalls($callback, $params);

        // Set up container to return parameter resolver
        $this->container->expects($this->once())
            ->method('get')
            ->with(ParameterResolver::class)
            ->willReturn($this->paramsResolver);

        // Set up parameter resolver expectations
        $this->paramsResolver->expects($this->once())
            ->method('appendResolver')
            ->with($this->isInstanceOf(RequestParamResolver::class));

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Test response', (string)$response->getBody());
    }

    /**
     * @throws NotCallableException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testProcessWithStringResponse(): void
    {
        $callback = function () {
            return 'String response';
        };

        $params = ['param1' => 'value1'];

        // Set up request attributes
        $expectations = [$callback, $params];
        $invokedCount = $this->exactly(count($expectations));
        $this->request->expects($invokedCount)
            ->method('getAttribute')
            ->willReturnCallback(function ($parameter) use ($expectations, $invokedCount) {

                $expectedParameters = [
                    '_controller',
                    '_params',
                ];
                $currentInvocationCount = $invokedCount->numberOfInvocations();
                $currentExpectation = $expectations[$currentInvocationCount - 1];
                $currentExpectedParameter = $expectedParameters[$currentInvocationCount - 1];
                $this->assertSame($currentExpectedParameter, $parameter);
                return $currentExpectation;
            })
            ->willReturnOnConsecutiveCalls($callback, $params);

        // Set up container to return parameter resolver
        $this->container->expects($this->once())
            ->method('get')
            ->with(ParameterResolver::class)
            ->willReturn($this->paramsResolver);

        // Set up parameter resolver expectations
        $this->paramsResolver->expects($this->once())
            ->method('appendResolver')
            ->with($this->isInstanceOf(RequestParamResolver::class));

        $response = $this->middleware->process($this->request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('String response', (string)$response->getBody());
    }

    /**
     * @throws NotCallableException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testProcessWithInvalidResponseThrowsException(): void
    {
        $callback = function () {
            return ['invalid' => 'response'];
        };

        $params = ['param1' => 'value1'];

        // Set up request attributes
        $expectations = [$callback, $params];
        $invokedCount = $this->exactly(count($expectations));
        $this->request->expects($invokedCount)
            ->method('getAttribute')
            ->willReturnCallback(function ($parameter) use ($expectations, $invokedCount) {

                $expectedParameters = [
                    '_controller',
                    '_params',
                ];
                $currentInvocationCount = $invokedCount->numberOfInvocations();
                $currentExpectation = $expectations[$currentInvocationCount - 1];
                $currentExpectedParameter = $expectedParameters[$currentInvocationCount - 1];
                $this->assertSame($currentExpectedParameter, $parameter);
                return $currentExpectation;
            })
            ->willReturnOnConsecutiveCalls($callback, $params);

        // Set up container to return parameter resolver
        $this->container->expects($this->once())
            ->method('get')
            ->with(ParameterResolver::class)
            ->willReturn($this->paramsResolver);

        // Set up parameter resolver expectations
        $this->paramsResolver->expects($this->once())
            ->method('appendResolver')
            ->with($this->isInstanceOf(RequestParamResolver::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The response is not a string or a ResponseInterface');

        $this->middleware->process($this->request, $this->handler);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->middleware = new RouteCallerMiddleware($this->container);
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->paramsResolver = $this->createMock(ResolverChain::class);
    }
}
