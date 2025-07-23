<?php

declare(strict_types=1);

namespace Entropy\Middleware;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RoutePrefixMiddleware implements MiddlewareInterface
{
    private ContainerInterface $container;

    private string $routePrefix;

    private string $middleware;

    /**
     * RoutePrefixMiddleware constructor.
     *
     * @param ContainerInterface $container
     * @param string $routePrefix
     * @param string $middleware
     */
    public function __construct(
        ContainerInterface $container,
        string $routePrefix,
        string $middleware
    ) {
        $this->container = $container;
        $this->routePrefix = $routePrefix;
        $this->middleware = $middleware;
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri()->getPath();
        if (stripos($uri, $this->routePrefix) === 0) {
            return $this->container->get($this->middleware)->process($request, $handler);
        }
        return $handler->handle($request);
    }
}
