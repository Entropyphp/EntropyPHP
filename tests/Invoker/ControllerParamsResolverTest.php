<?php

declare(strict_types=1);

namespace Entropy\Tests\Invoker;

use Entropy\Invoker\ParameterResolver\AssociativeArrayTypeHintResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\NumericArrayResolver;
use PHPUnit\Framework\TestCase;
use Entropy\Invoker\ControllerParamsResolver;
use Invoker\Exception\NotEnoughParametersException;
use ReflectionException;
use ReflectionFunction;

class ControllerParamsResolverTest extends TestCase
{
    private ControllerParamsResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ControllerParamsResolver([
            new NumericArrayResolver(),
            new AssociativeArrayTypeHintResolver(),
            new DefaultValueResolver(),
        ]);
    }

    /**
     * @throws ReflectionException
     * @throws NotEnoughParametersException
     */
    public function testGetParametersSuccess(): void
    {
        $func = function (string $name, int $age) {
            return true;
        };
        $reflection = new ReflectionFunction($func);
        $providedParams = ['name' => 'John', 'age' => 30];
        $resolvedParams = [];

        $result = $this->resolver->getParameters($reflection, $providedParams, $resolvedParams);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]);
        $this->assertEquals(30, $result[1]);
    }

    /**
     * @throws ReflectionException
     * @throws NotEnoughParametersException
     */
    public function testGetParametersWithVariadicSuccess(): void
    {
        $func = function (string $name, int ...$numbers) {
            return true;
        };
        $reflection = new ReflectionFunction($func);
        $providedParams = ['name' => 'John', 'numbers' => [1, 2, 3]];
        $resolvedParams = [];

        $result = $this->resolver->getParameters($reflection, $providedParams, $resolvedParams);

        $this->assertIsArray($result);
        $this->assertEquals('John', $result[0]);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetParametersThrowsExceptionWhenParameterMissing(): void
    {
        $func = function (string $name, int $age) {
            return true;
        };
        $reflection = new ReflectionFunction($func);
        $providedParams = ['name' => 'John'];
        $resolvedParams = [];

        $this->expectException(NotEnoughParametersException::class);
        $this->expectExceptionMessage(
            'Unable to invoke the callable because no value was given for parameter 2 ($age)'
        );

        $this->resolver->getParameters($reflection, $providedParams, $resolvedParams);
    }

    /**
     * @throws ReflectionException
     * @throws NotEnoughParametersException
     */
    public function testGetParametersWithParentResolverChain(): void
    {
        $func = function (string $name, ?int $age = null) {
            return true;
        };
        $reflection = new ReflectionFunction($func);
        $providedParams = ['name' => 'John'];
        $resolvedParams = [];

        $result = $this->resolver->getParameters($reflection, $providedParams, $resolvedParams);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]);
        $this->assertNull($result[1]);
    }
}
