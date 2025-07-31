<?php

declare(strict_types=1);

namespace Entropy\Tests\Invoker;

use Entropy\Invoker\InvokerFactory;
use Entropy\Invoker\ParameterResolver\AssociativeArrayTypeHintResolver;
use Invoker\Invoker;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\NumericArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionObject;

class InvokerFactoryTest extends TestCase
{
    private ContainerInterface $container;
    private InvokerFactory $factory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new InvokerFactory();
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

        $invoker = ($this->factory)($this->container);

        $this->assertInstanceOf(Invoker::class, $invoker);
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

        $invoker = ($this->factory)($this->container);

        $this->assertInstanceOf(Invoker::class, $invoker);
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

        $invoker = ($this->factory)($this->container);

        $this->assertInstanceOf(Invoker::class, $invoker);
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

        $invoker = ($this->factory)($this->container);

        $this->assertInstanceOf(Invoker::class, $invoker);
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

        $invoker = ($this->factory)($this->container);
        $reflection = new ReflectionObject($invoker);
        $resolverChainProp = $reflection->getProperty('parameterResolver');
        $resolverChain = $resolverChainProp->getValue($invoker);

        $resolversReflection = new ReflectionObject($resolverChain);
        $resolversProperty = $resolversReflection->getProperty('resolvers');
        $resolvers = $resolversProperty->getValue($resolverChain);

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
