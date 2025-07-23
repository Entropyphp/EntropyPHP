<?php

declare(strict_types=1);

namespace Entropy\Event;

use Entropy\Kernel\KernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ResponseEvent extends AppEvent
{
    public const NAME = Events::RESPONSE;

    private ServerRequestInterface $request;
    private ResponseInterface $response;

    public function __construct(KernelInterface $kernel, ServerRequestInterface $request, ResponseInterface $response)
    {
        parent::__construct($kernel);
        $this->request = $request;
        $this->response = $response;
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
    }

    public function hasResponse(): bool
    {
        return null !== $this->response;
    }
}
