<?php

declare(strict_types=1);

namespace Entropy\Event;

use Entropy\Kernel\KernelInterface;
use Psr\Http\Message\ServerRequestInterface;

class ControllerEvent extends RequestEvent
{
    public const NAME = Events::CONTROLLER;

    private mixed $controller;

    public function __construct(KernelInterface $kernel, mixed $controller, ServerRequestInterface $request)
    {
        parent::__construct($kernel, $request);
        $this->controller = $controller;
    }

    public function getController(): mixed
    {
        return $this->controller;
    }

    public function setController($controller): void
    {
        $this->controller = $controller;
    }
}
