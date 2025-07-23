<?php

declare(strict_types=1);

namespace Entropy\Kernel;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface KernelInterface
{
    /**
     * Handle request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;

    /**
     * Handle exception
     *
     * @param Throwable $e
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleException(Throwable $e, ServerRequestInterface $request): ResponseInterface;

    /**
     * set Listeners or middlewares
     *
     * @param array $callbacks
     * @return self
     */
    public function setCallbacks(array $callbacks): self;

    /**
     * Set request value
     *
     * @param ServerRequestInterface $request
     * @return self
     */
    public function setRequest(ServerRequestInterface $request): self;

    /**
     * Get value of request
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface;

    /**
     * Get Container
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface;
}
