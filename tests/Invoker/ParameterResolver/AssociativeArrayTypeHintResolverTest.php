<?php

declare(strict_types=1);

namespace Entropy\Tests\Invoker\ParameterResolver;

use Entropy\Invoker\ParameterResolver\AssociativeArrayTypeHintResolver;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use stdClass;

class AssociativeArrayTypeHintResolverTest extends TestCase
{
    private AssociativeArrayTypeHintResolver $resolver;

    /**
     * @throws ReflectionException
     */
    public function testResolvesParametersByName(): void
    {
        // Create a function with named parameters
        $function = function (string $name, int $age) {
            return [$name, $age];
        };

        $reflection = new ReflectionFunction($function);
        $providedParameters = [
            'name' => 'John',
            'age' => 30
        ];
        $resolvedParameters = [];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]);
        $this->assertEquals(30, $result[1]);
    }

    /**
     * @throws ReflectionException
     */
    public function testConvertsNumericStringToIntForIntParameter(): void
    {
        // Create a function with an int parameter
        $function = function (int $age) {
            return $age;
        };

        $reflection = new ReflectionFunction($function);
        $providedParameters = [
            'age' => '30' // String that should be converted to int
        ];
        $resolvedParameters = [];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(1, $result);
        $this->assertSame(30, $result[0]); // Should be converted to int
        $this->assertIsInt($result[0]);
    }

    /**
     * @throws ReflectionException
     */
    public function testHandlesClassTypeParameters(): void
    {
        // Create a function with a class type parameter
        $function = function (stdClass $obj) {
            return $obj;
        };

        $obj = new stdClass();
        $obj->property = 'value';

        $reflection = new ReflectionFunction($function);
        $providedParameters = [
            'obj' => $obj
        ];
        $resolvedParameters = [];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(1, $result);
        $this->assertSame($obj, $result[0]);
    }

    /**
     * @throws ReflectionException
     */
    public function testSkipsParametersNotInProvidedParameters(): void
    {
        // Create a function with multiple parameters
        $function = function (string $name, int $age, bool $active) {
            return [$name, $age, $active];
        };

        $reflection = new ReflectionFunction($function);
        $providedParameters = [
            'name' => 'John',
            // 'age' is missing
            'active' => true
        ];
        $resolvedParameters = [];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]);
        $this->assertTrue($result[2]);
        $this->assertArrayNotHasKey(1, $result); // 'age' parameter should not be resolved
    }

    /**
     * @throws ReflectionException
     */
    public function testSkipsAlreadyResolvedParameters(): void
    {
        // Create a function with multiple parameters
        $function = function (string $name, int $age) {
            return [$name, $age];
        };

        $reflection = new ReflectionFunction($function);
        $providedParameters = [
            'name' => 'John',
            'age' => 30
        ];
        $resolvedParameters = [
            0 => 'Already resolved' // Parameter at index 0 is already resolved
        ];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(2, $result);
        $this->assertEquals('Already resolved', $result[0]); // Should not be overwritten
        $this->assertEquals(30, $result[1]);
    }

    /**
     * @throws ReflectionException
     */
    public function testWorksWithMethodReflection(): void
    {
        // Create a test class with a method that has named parameters
        $testObject = new class {
            public function testMethod(string $name, int $age): array
            {
                return [$name, $age];
            }
        };

        $reflection = new ReflectionMethod($testObject, 'testMethod');
        $providedParameters = [
            'name' => 'John',
            'age' => 30
        ];
        $resolvedParameters = [];

        $result = $this->resolver->getParameters($reflection, $providedParameters, $resolvedParameters);

        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]);
        $this->assertEquals(30, $result[1]);
    }

    protected function setUp(): void
    {
        $this->resolver = new AssociativeArrayTypeHintResolver();
    }
}
