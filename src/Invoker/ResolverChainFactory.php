<?php

declare(strict_types=1);

namespace Entropy\Invoker;

use DI\Definition\Resolver\ResolverDispatcher;
use DI\NotFoundException;
use DI\Proxy\ProxyFactory;
use Entropy\Invoker\ParameterResolver\AssociativeArrayTypeHintResolver;
use Pg\Utils\File\FileUtils;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use DI\Invoker\DefinitionParameterResolver;
use Invoker\ParameterResolver\ParameterResolver;
use Invoker\ParameterResolver\DefaultValueResolver;
use Invoker\ParameterResolver\NumericArrayResolver;
use Entropy\Invoker\ParameterResolver\DoctrineEntityResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Entropy\Invoker\ParameterResolver\DoctrineParamConverterAnnotations;
use Psr\Container\NotFoundExceptionInterface;

class ResolverChainFactory
{
    /**
     * @param ContainerInterface $container
     * @return ParameterResolver
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ParameterResolver
    {
        $proxyDir = $this->getProxyDirectory($container);
        $definitionResolver = new ResolverDispatcher($container, new ProxyFactory($proxyDir));

        // Résolveurs par défaut
        $defaultResolvers = $this->getDefaultResolvers($container, $definitionResolver);

        // Résolveurs Doctrine
        //$doctrineResolvers = $this->getDoctrineResolvers($container);

        return new ControllerParamsResolver(/*array_merge($doctrineResolvers,*/ $defaultResolvers/*)*/);
    }

    /**
     * @param ContainerInterface $container
     * @return string|null
     * @throws NotFoundException
     * @throws ContainerExceptionInterface
     */
    private function getProxyDirectory(ContainerInterface $container): ?string
    {
        if ($container->get('env') !== 'prod') {
            return null;
        }

        if (!$container->has('proxy_dir')) {
            return null;
        }

        $proxyDir = $container->get('proxy_dir');

        if ($proxyDir !== null) {
            if (!$container->has('env')) {
                return null;
            }
        }

        return $proxyDir ?? FileUtils::getProjectDir() . $proxyDir;
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
     * @param ContainerInterface $container
     * @return array
     * @throws NotFoundException
     */
    /*
    private function getDoctrineResolvers(ContainerInterface $container): array
    {
        if (!$container->has(ManagerRegistry::class)) {
            return [];
        }

        try {
            $managerRegistry = $container->get(ManagerRegistry::class);
            return [
                new DoctrineParamConverterAnnotations($managerRegistry, $container->get(AnnotationsLoader::class)),
                new DoctrineEntityResolver($managerRegistry),
            ];
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            throw new NotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }*/
}
