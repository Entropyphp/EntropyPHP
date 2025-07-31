<?php

declare(strict_types=1);

namespace Entropy\Tests\Invoker;

use Entropy\Invoker\ControllerParamsResolver;
use Entropy\Invoker\ParameterResolver\AssociativeArrayTypeHintResolver;
use Entropy\Invoker\ResolverChainFactory;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\NumericArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionObject;

class ResolverChainFactoryTest extends TestCase
{
    private ContainerInterface $container;
    private ResolverChainFactory $factory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new ResolverChainFactory();
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testInvokeWithoutCustomResolvers(): void
    {
        $this->container->method('has')
            ->willReturnMap([
                ['env', false],
                ['params.resolvers', false]
            ]);

        $resolver = ($this->factory)($this->container);

        $this->assertInstanceOf(ControllerParamsResolver::class, $resolver);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    public function testInvokeWithCustomResolvers(): void
    {
        $customResolver = $this->createMock(DefaultValueResolver::class);

        $this->container->method('has')
            ->willReturnMap([
                ['env', false],
                ['params.resolvers', true]
            ]);

        $this->container->method('get')
            ->with('params.resolvers')
            ->willReturn([$customResolver]);

        $resolver = ($this->factory)($this->container);

        $this->assertInstanceOf(ControllerParamsResolver::class, $resolver);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function testInvokeWithProdEnvironment(): void
    {
        $proxyDir = '/tmp/proxy';

        $this->container->method('has')
            ->willReturnMap([
                ['env', true],
                ['proxy_dir', true],
                ['params.resolvers', false]
            ]);

        $this->container->method('get')
            ->willReturnMap([
                ['env', 'prod'],
                ['proxy_dir', $proxyDir]
            ]);

        $resolver = ($this->factory)($this->container);

        $this->assertInstanceOf(ControllerParamsResolver::class, $resolver);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    public function testGetCustomResolversSingleResolver(): void
    {
        $customResolver = $this->createMock(DefaultValueResolver::class);

        $this->container->method('has')
            ->willReturnMap([
                ['env', false],
                ['params.resolvers', true]
            ]);

        $this->container->method('get')
            ->with('params.resolvers')
            ->willReturn($customResolver);

        $resolver = ($this->factory)($this->container);

        $this->assertInstanceOf(ControllerParamsResolver::class, $resolver);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testDefaultResolversArePresent(): void
    {
        $this->container->method('has')
            ->willReturnMap([
                ['env', false],
                ['params.resolvers', false]
            ]);

        $resolver = ($this->factory)($this->container);
        $reflection = new ReflectionObject($resolver);
        $parentClass = $reflection->getParentClass();
        $resolversProperty = $parentClass->getProperty('resolvers');
        $resolvers = $resolversProperty->getValue($resolver);

        $this->assertContainsOnlyInstancesOf(
            NumericArrayResolver::class,
            array_filter($resolvers, fn($r) => $r instanceof NumericArrayResolver)
        );
        $this->assertContainsOnlyInstancesOf(
            AssociativeArrayTypeHintResolver::class,
            array_filter($resolvers, fn($r) => $r instanceof AssociativeArrayTypeHintResolver)
        );
        $this->assertContainsOnlyInstancesOf(
            DefaultValueResolver::class,
            array_filter($resolvers, fn($r) => $r instanceof DefaultValueResolver)
        );
        $this->assertContainsOnlyInstancesOf(
            TypeHintContainerResolver::class,
            array_filter($resolvers, fn($r) => $r instanceof TypeHintContainerResolver)
        );
    }
}
