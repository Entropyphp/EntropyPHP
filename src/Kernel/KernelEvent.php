<?php

// Inspired by Symfony HttpKernel (c) Fabien Potencier, MIT License

declare(strict_types=1);

namespace Entropy\Kernel;

use Exception;
use Invoker\Exception\NotCallableException;
use Invoker\ParameterResolver\ResolverChain;
use Entropy\Event\EventDispatcher;
use Entropy\Invoker\ParameterResolver\RequestParamResolver;
use ReflectionException;
use RuntimeException;
use InvalidArgumentException;
use Invoker\CallableResolver;
use Entropy\Event\ViewEvent;
use Entropy\Event\RequestEvent;
use Entropy\Event\ResponseEvent;
use Entropy\Event\ExceptionEvent;
use Psr\Container\ContainerInterface;
use Entropy\Event\ControllerEvent;
use Psr\Http\Message\ResponseInterface;
use Entropy\Event\FinishRequestEvent;
use Invoker\Reflection\CallableReflection;
use Entropy\Event\ControllerParamsEvent;
use Psr\Http\Message\ServerRequestInterface;
use Invoker\ParameterResolver\ParameterResolver;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

class KernelEvent implements KernelInterface
{
    protected ServerRequestInterface $request;

    protected EventDispatcherInterface $dispatcher;

    private CallableResolver $callableResolver;

    private ParameterResolver $paramsResolver;

    private ContainerInterface $container;


    public function __construct(
        EventDispatcherInterface $dispatcher,
        CallableResolver $callableResolver,
        ParameterResolver $paramsResolver,
        ContainerInterface $container
    ) {
        $this->dispatcher = $dispatcher;
        $this->callableResolver = $callableResolver;
        $this->paramsResolver = $paramsResolver;
        $this->container = $container;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        $event = new RequestEvent($this, $request);
        $event = $this->dispatcher->dispatch($event);

        if ($event->hasResponse()) {
            return $this->filterResponse($event->getResponse(), $this->getRequest());
        }

        if (null === $controller = $this->getRequest()->getAttribute('_controller')) {
            throw new RuntimeException(
                sprintf(
                    "Aucun controller trouver pour cette request, la route %s est peut-être mal configurée",
                    $request->getUri()->getPath()
                )
            );
        }

        $controller = $this->callableResolver->resolve($controller);

        $event = new ControllerEvent($this, $controller, $this->getRequest());
        $event = $this->dispatcher->dispatch($event);
        $controller = $event->getController();

        $params = $this->getRequest()->getAttribute('_params');

        $callableReflection = CallableReflection::create($controller);
        assert($this->paramsResolver instanceof ResolverChain);
        // Add a request param resolver if needed (hint ServerRequestInterface)
        $this->paramsResolver->appendResolver(new RequestParamResolver($this->getRequest()));
        $params = $this->paramsResolver->getParameters($callableReflection, $params, []);

        $event = new ControllerParamsEvent($this, $controller, $params, $this->getRequest());
        $event = $this->dispatcher->dispatch($event);
        $controller = $event->getController();
        $params = $event->getParams();

        // call controller
        $response = $controller(...$params);

        // view
        if (!$response instanceof ResponseInterface) {
            $event = new ViewEvent($this, $this->getRequest(), $response);
            $event = $this->dispatcher->dispatch($event);

            if ($event->hasResponse()) {
                $response = $event->getResponse();
            } else {
                $msg = sprintf('The controller must return a "Response" object but it returned %s.', $response);

                // the user may have forgotten to return something
                if (null === $response) {
                    $msg .= ' Did you forget to add a return statement somewhere in your controller?';
                }

                throw new Exception($msg . get_class($controller) . ' ' . __FILE__ . ' ' . (__LINE__ - 17));
            }
        }
        return $this->filterResponse($response, $this->getRequest());
    }

    /**
     * Filters a response object.
     *
     * @throws RuntimeException If the passed object is not a Response instance
     */
    private function filterResponse(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        $event = new ResponseEvent($this, $request, $response);

        $event = $this->dispatcher->dispatch($event);

        $this->finishRequest($this->getRequest());

        return $event->getResponse();
    }

    /**
     * Publishes the finish request event
     */
    private function finishRequest(ServerRequestInterface $request): void
    {
        $this->dispatcher->dispatch(new FinishRequestEvent($this, $request));
    }

    /**
     * @inheritDoc
     */
    public function handleException(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        $event = new ExceptionEvent($this, $request, $e);
        $event = $this->dispatcher->dispatch($event);

        // a listener might have replaced the exception
        $e = $event->getException();

        if (!$event->hasResponse()) {
            $this->finishRequest($this->getRequest());

            throw $e;
        }

        $response = $event->getResponse();

        try {
            return $this->filterResponse($response, $this->getRequest());
        } catch (Throwable) {
            return $response;
        }
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

    /**
     *
     * @param array $callbacks
     * @return self
     * @throws NotCallableException
     * @throws ReflectionException
     */
    public function setCallbacks(array $callbacks): self
    {
        if (empty($callbacks)) {
            throw new InvalidArgumentException("Une liste de listeners doit être passer à ce Kernel");
        }

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->dispatcher;
        foreach ($callbacks as $callback) {
            $dispatcher->addSubscriber($callback);
        }
        return $this;
    }

    public function getDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
