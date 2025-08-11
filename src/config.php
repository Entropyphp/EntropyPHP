<?php

declare(strict_types=1);

use Entropy\Event\EventDispatcher;
use Entropy\Invoker\CallableResolverFactory;
use Entropy\Invoker\InvokerFactory;
use Entropy\Invoker\ResolverChainFactory;
use Entropy\Kernel\KernelEvent;
use Entropy\Kernel\KernelMiddleware;
use Invoker\CallableResolver;
use Invoker\Invoker;
use Invoker\ParameterResolver\ParameterResolver;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

use function DI\factory;

return [
    //@codeCoverageIgnoreStart
    Invoker::class => factory(InvokerFactory::class),
    ParameterResolver::class => factory(ResolverChainFactory::class),
    CallableResolver::class => factory(CallableResolverFactory::class),
    EventDispatcherInterface::class => function (ContainerInterface $c): EventDispatcherInterface {
        return new EventDispatcher($c->get(CallableResolver::class));
    },
    KernelEvent::class => function (ContainerInterface $c): KernelEvent {
        return new KernelEvent(
            $c->get(EventDispatcherInterface::class),
            $c->get(CallableResolver::class),
            $c->get(ParameterResolver::class),
            $c
        );
    },
    KernelMiddleware::class => function (ContainerInterface $c): KernelMiddleware {
        return new KernelMiddleware($c);
    },
    //@codeCoverageIgnoreEnd
];
