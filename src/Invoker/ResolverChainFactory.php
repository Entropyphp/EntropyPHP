<?php

declare(strict_types=1);

namespace Entropy\Invoker;

use DI\Definition\Resolver\ResolverDispatcher;
use DI\Proxy\ProxyFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Invoker\ParameterResolver\ParameterResolver;

class ResolverChainFactory extends AbstractResolverChainFactory
{
    /**
     * @param ContainerInterface $container
     * @return ParameterResolver
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ParameterResolver
    {
        $proxyDir = $this->getProxyDirectory($container);
        $definitionResolver = new ResolverDispatcher($container, new ProxyFactory($proxyDir));

        // Default resolvers
        $defaultResolvers = $this->getDefaultResolvers($container, $definitionResolver);

        // Custom resolvers
        $otherResolvers = $this->getCustomResolvers($container);

        return new ControllerParamsResolver(array_merge($otherResolvers, $defaultResolvers));
    }
}
