<?php

/**
 * https://github.com/thephpleague/route
 */

declare(strict_types=1);

namespace Entropy\Middleware\Stack;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Entropy\Middleware\RoutePrefixMiddleware;

trait MiddlewareAwareStackTrait
{
    protected array $middlewares = [];

    /**
     * Add middleware
     *
     * @param callable|string|MiddlewareInterface $middleware
     * @return self
     */
    public function middleware(callable|MiddlewareInterface|string $middleware): static
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Add a middlewares array
     *
     * @param string[]|MiddlewareInterface[]|callable[] $middlewares
     * @return self
     */
    public function middlewares(array $middlewares): static
    {
        foreach ($middlewares as $middleware) {
            $this->middleware($middleware);
        }
        return $this;
    }

    /**
     * Add middleware in first
     *
     * @param callable|string|MiddlewareInterface $middleware
     * @return self
     */
    public function prependMiddleware(callable|MiddlewareInterface|string $middleware): static
    {
        array_unshift($this->middlewares, $middleware);
        return $this;
    }

    /**
     * @param string $routePrefix
     * @param string|null $middleware
     * @param ContainerInterface $c
     * @return self
     */
    public function lazyPipe(ContainerInterface $c, string $routePrefix, ?string $middleware = null): static
    {
        $middleware = $middleware ?
            new RoutePrefixMiddleware($c, $routePrefix, $middleware) :
            $routePrefix;
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Get first middleware from the stack
     *
     * @param ContainerInterface $c
     * @return MiddlewareInterface|Closure|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function shiftMiddleware(ContainerInterface $c): null|MiddlewareInterface|Closure
    {
        $middleware =  array_shift($this->middlewares);
        if ($middleware === null) {
            return null;
        }

        if (is_string($middleware)) {
            $middleware = $c->get($middleware);
        }
        return $middleware;
    }

    /**
     * get middleware stack
     *
     * @return iterable
     */
    public function getMiddlewareStack(): iterable
    {
        return $this->middlewares;
    }
}
