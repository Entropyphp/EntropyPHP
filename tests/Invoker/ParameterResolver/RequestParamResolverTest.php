<?php

declare(strict_types=1);

namespace Entropy\Tests\Invoker\ParameterResolver;

use Entropy\Invoker\ParameterResolver\RequestParamResolver;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use stdClass;

class RequestParamResolverTest extends TestCase
{
    private ServerRequestInterface|MockObject $request;
    private RequestParamResolver $resolver;

    /**
     * @throws ReflectionException
     */
    public function testResolvesServerRequestInterfaceParameter(): void
    {
        // Create a function with a ServerRequestInterface parameter
        $function = function (ServerRequestInterface $request) {
            return $request;
        };

        $reflection = new ReflectionFunction($function);
        $providedParameters = [];
        $resolvedParameters = [];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(1, $result);
        $this->assertSame($this->request, $result[0]);
    }

    /**
     * @throws ReflectionException
     */
    public function testDoesNotResolveOtherClassParameters(): void
    {
        // Create a function with a non-ServerRequestInterface parameter
        $function = function (stdClass $obj) {
            return $obj;
        };

        $reflection = new ReflectionFunction($function);
        $providedParameters = [];
        $resolvedParameters = [];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(0, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testDoesNotResolveBuiltinTypeParameters(): void
    {
        // Create a function with a built-in type parameter
        $function = function (string $str) {
            return $str;
        };

        $reflection = new ReflectionFunction($function);
        $providedParameters = [];
        $resolvedParameters = [];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(0, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testDoesNotResolveParametersWithoutType(): void
    {
        // Create a function with a parameter without a type
        $function = function ($param) {
            return $param;
        };

        $reflection = new ReflectionFunction($function);
        $providedParameters = [];
        $resolvedParameters = [];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(0, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testSkipsAlreadyResolvedParameters(): void
    {
        // Create a function with multiple parameters
        $function = function (ServerRequestInterface $request, stdClass $obj) {
            return [$request, $obj];
        };

        $reflection = new ReflectionFunction($function);
        $providedParameters = [];
        $resolvedParameters = [1 => new stdClass()]; // Parameter at index 1 is already resolved

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(2, $result);
        $this->assertSame($this->request, $result[0]);
        $this->assertInstanceOf(stdClass::class, $result[1]);
    }

    /**
     * @throws ReflectionException
     */
    public function testWorksWithMethodReflection(): void
    {
        // Create a test class with a method that has a ServerRequestInterface parameter
        $testObject = new class {
            public function testMethod(ServerRequestInterface $request): ServerRequestInterface
            {
                return $request;
            }
        };

        $reflection = new ReflectionMethod($testObject, 'testMethod');
        $providedParameters = [];
        $resolvedParameters = [];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(1, $result);
        $this->assertSame($this->request, $result[0]);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->resolver = new RequestParamResolver($this->request);
    }
}
