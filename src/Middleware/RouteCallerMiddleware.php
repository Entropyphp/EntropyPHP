<?php

namespace Entropy\Middleware;

use Exception;
use GuzzleHttp\Psr7\Response;
use Invoker\CallableResolver;
use Invoker\Exception\NotCallableException;
use Invoker\ParameterResolver\ResolverChain;
use Entropy\Invoker\ParameterResolver\RequestParamResolver;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Invoker\Reflection\CallableReflection;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Invoker\ParameterResolver\ParameterResolver;
use ReflectionException;

/**
 * All magic happens here
 *
 * Wrap $route callable controller in middleware
 */
class RouteCallerMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;

    /**
     *  constructor
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Psr15 middleware process method
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws NotCallableException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Exception
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        $callback = $request->getAttribute('_controller');
        $params = $request->getAttribute('_params');

        /** @var CallableResolver $callableResolver */
        $callableResolver = $this->container->get(CallableResolver::class);
        $callback = $callableResolver->resolve($callback);

        $paramsResolver = $this->container->get(ParameterResolver::class);
        $callableReflection = CallableReflection::create($callback);
        assert($paramsResolver instanceof ResolverChain);
        // Add a request param resolver if needed (hint ServerRequestInterface)
        $paramsResolver->appendResolver(new RequestParamResolver($request));
        $params = $paramsResolver->getParameters($callableReflection, $params, []);

        $response = $callback(...$params);

        if (is_string($response)) {
            return new Response(200, [], $response);
        } elseif ($response instanceof ResponseInterface) {
            return $response;
        } else {
            throw new Exception('The response is not a string or a ResponseInterface');
        }
    }
}
