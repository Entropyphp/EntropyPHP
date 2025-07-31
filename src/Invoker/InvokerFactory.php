<?php

declare(strict_types=1);

namespace Entropy\Invoker;

use DI\Definition\Resolver\ResolverDispatcher;
use DI\Proxy\ProxyFactory;
use Invoker\Invoker;
use Invoker\InvokerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Invoker\ParameterResolver\ResolverChain;

class InvokerFactory extends AbstractResolverChainFactory
{
    /**
     * Create Invoker
     *
     * @param ContainerInterface $container
     * @return InvokerInterface
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): InvokerInterface
    {
        $proxyDir = $this->getProxyDirectory($container);
        $definitionResolver = new ResolverDispatcher($container, new ProxyFactory($proxyDir));

        // Default resolvers
        $defaultResolvers = $this->getDefaultResolvers($container, $definitionResolver);

        // Custom resolvers
        $otherResolvers = $this->getCustomResolvers($container);

        return new Invoker(
            new ResolverChain(array_merge($otherResolvers, $defaultResolvers)),
            $container
        );
    }
}
