<?php

declare(strict_types=1);

namespace Entropy\Event;

use Entropy\Kernel\KernelInterface;
use Psr\Http\Message\ServerRequestInterface;

class ViewEvent extends RequestEvent
{
    public const NAME = Events::VIEW;

    private mixed $result;

    public function __construct(KernelInterface $kernel, ServerRequestInterface $request, mixed $result)
    {
        parent::__construct($kernel, $request);
        $this->result = $result;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function setResult($result): void
    {
        $this->result = $result;
    }
}
