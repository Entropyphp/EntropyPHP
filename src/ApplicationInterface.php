<?php

declare(strict_types=1);

namespace Entropy;

use Psr\Container\ContainerInterface;
use Entropy\Kernel\KernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ApplicationInterface
{
    /**
     * @param ServerRequestInterface|null $request
     * @return $this
     */
    public function init(?ServerRequestInterface $request = null): self;

    /**
     * @return ResponseInterface
     */
    public function run(): ResponseInterface;

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface;

    /**
     * @return KernelInterface|null
     */
    public function getKernel(): ?KernelInterface;
}
