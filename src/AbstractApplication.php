<?php

declare(strict_types=1);

namespace Entropy;

use DI\ContainerBuilder;
use Entropy\Utils\Environnement\Env;
use Entropy\Utils\File\FileUtils;
use Exception;
use Psr\Http\Server\MiddlewareInterface;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Container\ContainerInterface;
use Entropy\Kernel\KernelInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

use function DI\factory;

/**
 * AbstractApplication
 */
abstract class AbstractApplication implements ApplicationInterface
{
    public const PROXY_DIRECTORY = '/tmp/proxies';
    public const COMPILED_CONTAINER_DIRECTORY = '/tmp/di';
    public const CACHE_DIRECTORY = '/tmp/cache';
    public static ?ApplicationInterface $app = null;
    private ?ContainerInterface $container = null;
    private ?KernelInterface $kernel;
    private array $config = [];
    private array $modules = [];
    private array $middlewares = [];
    private array $listeners = [];
    private ServerRequestInterface $request;
    private string $projectDir;
    private string $configDir;

    public function __construct(?KernelInterface $kernel = null)
    {
        $this->config[] = __DIR__ . '/config.php';
        $this->projectDir = FileUtils::getRootPath();

        self::$app = $this;

        $this->kernel = $kernel;
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    /**
     *
     * @param array $modules
     * @return self
     */
    public function addModules(array $modules): self
    {
        foreach ($modules as $module) {
            $this->addModule($module);
        }
        return $this;
    }

    /**
     *
     * @param string $module
     * @return self
     */
    public function addModule(string $module): self
    {
        $this->modules[] = $module;
        return $this;
    }

    /**
     * @param string $listener
     * @return $this
     */
    public function addListener(string $listener): self
    {
        $this->listeners[] = $listener;
        return $this;
    }

    /**
     *
     * @param array $listeners
     * @return self
     */
    public function addListeners(array $listeners): self
    {
        $this->listeners = array_merge($this->listeners, $listeners);
        return $this;
    }

    public function getListeners(): array
    {
        return $this->listeners;
    }

    /**
     * @param string|callable|MiddlewareInterface $middleware
     * @return $this
     */
    public function addMiddleware(string|callable|MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     *
     * @param array $middlewares
     * @return self
     */
    public function addMiddlewares(array $middlewares): self
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * @throws Exception
     */
    public function init(?ServerRequestInterface $request = null): static
    {
        if ($request === null) {
            $request = ServerRequest::fromGlobals();
        }

        /** @var ServerRequestInterface $request */
        $this->request = $request->withAttribute(ApplicationInterface::class, $this);

        $container = $this->getContainer();

        $this->initModules($container);

        $this->initKernel($container);

        return $this;
    }

    /**
     * Get DI Injection Container
     *
     * @return ContainerInterface
     * @throws Exception
     */
    public function getContainer(): ContainerInterface
    {
        if ($this->container === null) {
            $builder = new ContainerBuilder();

            $env = Env::getEnv('APP_ENV', 'prod');
            if ($env === 'prod') {
                $projectDir = $this->projectDir;
                $builder->enableCompilation($projectDir . self::COMPILED_CONTAINER_DIRECTORY);
                $builder->writeProxiesToFile(true, $projectDir . self::PROXY_DIRECTORY);
            }

            $builder->addDefinitions($this->getRunTimeDefinitions());
            foreach ($this->config as $config) {
                $builder->addDefinitions($config);
            }

            /** @var Module $module */
            foreach ($this->modules as $module) {
                if ($module::DEFINITIONS) {
                    $builder->addDefinitions($module::DEFINITIONS);
                }
            }
            $this->container = $builder->build();
        }
        return $this->container;
    }

    protected function getRunTimeDefinitions(): array
    {
        // Get all config file definitions
        $config = FileUtils::getFiles($this->getConfigDir(), 'php', '.dist.');
        $this->config = array_merge($this->config, array_keys($config));

        return [
            ApplicationInterface::class => $this,
            'app.project.dir' => $this->projectDir,
            'app.cache.dir' => $this->projectDir . self::CACHE_DIRECTORY,
        ];
    }

    public function getConfigDir(): string
    {
        if (!isset($this->configDir)) {
            $this->configDir = $this->projectDir . '/config';
        }
        return $this->configDir;
    }

    abstract public function initModules(ContainerInterface $container): ApplicationInterface;

    abstract public function initKernel(ContainerInterface $container): ApplicationInterface;

    /**
     *
     * @return ResponseInterface
     */
    public function run(): ResponseInterface
    {
        try {
            return $this->kernel->handle($this->request);
        } catch (Throwable $e) {
            return $this->kernel->handleException($e, $this->kernel->getRequest());
        }
    }

    /**
     * Get the value of the request
     *
     * @return  ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Set the value of the request
     *
     * @param ServerRequestInterface $request
     *
     * @return  self
     */
    public function setRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;

        return $this;
    }

    /**
     *
     * @return KernelInterface|null
     */
    public function getKernel(): ?KernelInterface
    {
        return $this->kernel;
    }

    /**
     *
     * @return array
     */
    public function getModules(): array
    {
        return $this->modules;
    }
}
