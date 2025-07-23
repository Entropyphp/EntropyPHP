<?php

declare(strict_types=1);

namespace Entropy\Invoker;

use DI\Definition\Resolver\ResolverDispatcher;
use DI\Invoker\DefinitionParameterResolver;
use DI\Proxy\ProxyFactory;
use Invoker\Invoker;
use Invoker\InvokerInterface;
use Entropy\App;
use Entropy\ApplicationInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\NumericArrayResolver;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Entropy\Invoker\ParameterResolver\ActiveRecordResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Psr\Container\NotFoundExceptionInterface;

class InvokerFactory
{
    /**
     * Create Invoker
     *
     * @param ContainerInterface $container
     * @return InvokerInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): InvokerInterface
    {
        $proxyDir = null;
        if ($container->get('env') === 'prod') {
            $projectDir = $container->get(ApplicationInterface::class)->getProjectDir();
            $projectDir = realpath($projectDir) ?: $projectDir;
            $proxyDir = $projectDir . App::PROXY_DIRECTORY;
        }

        $definitionResolver = new ResolverDispatcher($container, new ProxyFactory($proxyDir));
        $parameterResolver = new ResolverChain([
            new DefinitionParameterResolver($definitionResolver),
            // Must before TypeHintContainerResolver
            new ActiveRecordResolver(),
            new NumericArrayResolver(),
            new AssociativeArrayResolver(),
            new DefaultValueResolver(),
            new TypeHintContainerResolver($container)
        ]);

        return new Invoker($parameterResolver, $container);
    }
}
