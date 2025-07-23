<?php

declare(strict_types=1);

namespace Entropy\Event;

use Entropy\Kernel\KernelInterface;
use Psr\Http\Message\ServerRequestInterface;

class ControllerParamsEvent extends ControllerEvent
{
    public const NAME = Events::PARAMETERS;

    private array $params;

    public function __construct(
        KernelInterface $kernel,
        callable $controller,
        array $params,
        ServerRequestInterface $request
    ) {
        parent::__construct($kernel, $controller, $request);
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }
}
