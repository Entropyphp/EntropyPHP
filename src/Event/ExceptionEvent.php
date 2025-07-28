<?php

declare(strict_types=1);

namespace Entropy\Event;

use Entropy\Kernel\KernelInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class ExceptionEvent extends RequestEvent
{
    public const NAME = Events::EXCEPTION;

    private Throwable $exception;

    public function __construct(KernelInterface $kernel, ServerRequestInterface $request, Throwable $e)
    {
        parent::__construct($kernel, $request);
        $this->exception = $e;
    }

    /**
     * Get the value of the exception
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * Set the value of the exception
     *
     * @param Throwable $exception
     * @return  self
     */
    public function setException(Throwable $exception): self
    {
        $this->exception = $exception;
        return $this;
    }
}
