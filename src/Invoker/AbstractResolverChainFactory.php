<?php

declare(strict_types=1);

namespace Entropy\Invoker;

use DI\Definition\Resolver\ResolverDispatcher;
use DI\Invoker\DefinitionParameterResolver;
use Entropy\Invoker\ParameterResolver\AssociativeArrayTypeHintResolver;
use Entropy\Utils\File\FileUtils;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\NumericArrayResolver;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class AbstractResolverChainFactory
{
    /**
     * @param ContainerInterface $container
     * @return string|null
     * @throws ContainerExceptionInterface
     */
    protected function getProxyDirectory(ContainerInterface $container): ?string
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
    protected function getDefaultResolvers(ContainerInterface $container, ResolverDispatcher $definitionResolver): array
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
     */
    protected function getCustomResolvers(ContainerInterface $container): array
    {
        if (!$container->has('params.resolvers')) {
            return [];
        }

        $resolvers = $container->get('params.resolvers');

        return is_array($resolvers) ? $resolvers : [$resolvers];
    }
}
