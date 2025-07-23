<?php

declare(strict_types=1);

namespace Entropy\Event;

use Entropy\Kernel\KernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestEvent extends AppEvent
{
    private ServerRequestInterface $request;

    protected ?ResponseInterface $response = null;

    public function __construct(KernelInterface $kernel, ServerRequestInterface $request)
    {
        parent::__construct($kernel);
        $this->request = $request;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
        $this->getKernel()->setRequest($request);
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
        $this->stopPropagation();
    }

    public function hasResponse(): bool
    {
        return null !== $this->response;
    }
}
