<?php

declare(strict_types=1);

namespace Entropy\Kernel;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use InvalidArgumentException;
use Entropy\Middleware\CombinedMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Entropy\Middleware\Stack\MiddlewareAwareStackTrait;

class KernelMiddleware implements KernelInterface, RequestHandlerInterface
{
    use MiddlewareAwareStackTrait;

    protected ServerRequestInterface $request;
    protected ContainerInterface $container;

    private int $index = 0;

    public function __construct(ContainerInterface $c)
    {
        $this->container = $c;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        $this->index++;
        if ($this->index > 1) {
            throw new Exception('Aucun middleware n\'a intercepté cette requête');
        }

        $middleware = new CombinedMiddleware($this->container, (array)$this->getMiddlewareStack());
        return $middleware->process($request, $this);
    }

    /**
     * @throws Throwable
     */
    public function handleException(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        throw $e;
    }

    /**
     *
     * @param string $routePrefix
     * @param string|null $middleware
     * @return self
     */
    public function pipe(string $routePrefix, ?string $middleware = null): self
    {
        return $this->lazyPipe($this->getContainer(), $routePrefix, $middleware);
    }

    /**
     *
     * @param string[]|MiddlewareInterface[]|callable[] $callbacks
     * @return self
     */
    public function setCallbacks(array $callbacks): self
    {
        if (empty($callbacks)) {
            throw new InvalidArgumentException("Une liste de middlewares doit être passer à ce Kernel");
        }

        $this->middlewares($callbacks);
        return $this;
    }

    /**
     *
     * @return object
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getMiddleware(): object
    {
        return $this->shiftMiddleware($this->getContainer());
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @inheritDoc
     */
    public function setRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }
}
