<?php

declare(strict_types=1);

namespace Entropy\Invoker;

use Invoker\CallableResolver;
use Psr\Container\ContainerInterface;

class CallableResolverFactory
{
    public function __invoke(ContainerInterface $container): CallableResolver
    {
        return new CallableResolver($container);
    }
}
