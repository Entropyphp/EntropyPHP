<?php

namespace Entropy\Tests;

use Entropy\AbstractApplication;
use Entropy\ApplicationInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class ConcreteApplication extends AbstractApplication
{
    public function initModules(ContainerInterface $container): ApplicationInterface
    {
        return $this;
    }

    public function initKernel(ContainerInterface $container): ApplicationInterface
    {
        return $this;
    }
}
