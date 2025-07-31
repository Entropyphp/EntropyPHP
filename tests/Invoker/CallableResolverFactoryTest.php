<?php

declare(strict_types=1);

namespace Entropy\Tests\Invoker;

use Entropy\Invoker\CallableResolverFactory;
use Invoker\CallableResolver;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionException;

class CallableResolverFactoryTest extends TestCase
{
    private ContainerInterface $container;
    private CallableResolverFactory $factory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new CallableResolverFactory();
    }

    public function testInvokeReturnsCallableResolver(): void
    {
        $resolver = ($this->factory)($this->container);

        $this->assertInstanceOf(CallableResolver::class, $resolver);
    }

    /**
     * @throws ReflectionException
     */
    public function testResolverIsConfiguredWithContainer(): void
    {
        $resolver = ($this->factory)($this->container);

        $reflection = new \ReflectionObject($resolver);
        $containerProp = $reflection->getProperty('container');

        $this->assertSame($this->container, $containerProp->getValue($resolver));
    }
}
