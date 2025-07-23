<?php

declare(strict_types=1);

namespace Entropy\Invoker;

use DI\Definition\Resolver\ResolverDispatcher;
use DI\Invoker\DefinitionParameterResolver;
use DI\NotFoundException;
use DI\Proxy\ProxyFactory;
use Entropy\Invoker\ParameterResolver\AssociativeArrayTypeHintResolver;
use Invoker\Invoker;
use Invoker\InvokerInterface;
use Pg\Utils\File\FileUtils;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\NumericArrayResolver;
use Invoker\ParameterResolver\AssociativeArrayResolver;
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
        $proxyDir = $this->getProxyDirectory($container);
        $definitionResolver = new ResolverDispatcher($container, new ProxyFactory($proxyDir));

        // Default resolvers
        $defaultResolvers = $this->getDefaultResolvers($container, $definitionResolver);

        // Developer resolvers
        $otherResolvers = $this->getDeveloperResolvers($container);

        return new Invoker(
            new ResolverChain(array_merge($otherResolvers, $defaultResolvers)),
            $container
        );
    }

    /**
     * @param ContainerInterface $container
     * @return string|null
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     */
    private function getProxyDirectory(ContainerInterface $container): ?string
    {
        if (!$container->has('env')) {
            return null;
        }

        $proxyDir = null;

        if ($container->get('env') === 'prod') {
            $projectDir = FileUtils::getProjectDir();
            $projectDir = realpath($projectDir) ?: $projectDir;
            $proxyDir = $container->has('proxy_dir') ? $container->get('proxy_dir') : null;
            $proxyDir = $proxyDir ? $projectDir . $proxyDir : null;
        }

        return $proxyDir;
    }

    /**
     * @param ContainerInterface $container
     * @param ResolverDispatcher $definitionResolver
     * @return array
     */
    private function getDefaultResolvers(ContainerInterface $container, ResolverDispatcher $definitionResolver): array
    {
        return [
            new DefinitionParameterResolver($definitionResolver),
            new NumericArrayResolver(),
            new AssociativeArrayTypeHintResolver(),
            new DefaultValueResolver(),
            new TypeHintContainerResolver($container),
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getDeveloperResolvers(ContainerInterface $container): array
    {
        if (!$container->has('params.resolvers')) {
            return [];
        }

        $resolvers = $container->get('params.resolvers');

        return is_array($resolvers) ? $resolvers : [$resolvers];
    }
}
