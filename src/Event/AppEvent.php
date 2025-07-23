<?php

declare(strict_types=1);

namespace Entropy\Event;

use Entropy\Kernel\KernelInterface;

class AppEvent extends StoppableEvent
{
    public const NAME = Events::REQUEST;

    private KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getKernel(): KernelInterface
    {
        return $this->kernel;
    }
}
