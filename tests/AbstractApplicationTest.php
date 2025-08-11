<?php

declare(strict_types=1);

namespace Entropy\Tests;

use Entropy\Kernel\KernelEvent;
use Entropy\Kernel\KernelMiddleware;
use Entropy\Module;
use Exception;
use PHPUnit\Framework\TestCase;
use Entropy\ApplicationInterface;
use Entropy\AbstractApplication;
use Entropy\Kernel\KernelInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class AbstractApplicationTest extends TestCase
{
    private ConcreteApplication $app;
    private KernelInterface $kernel;

    public function testConstructor(): void
    {
        $this->assertSame($this->app, AbstractApplication::$app);
        $this->assertSame($this->kernel, $this->app->getKernel());
    }

    public function testAddModules(): void
    {
        $modules = [
            \stdClass::class,
            \DateTime::class
        ];

        $this->app->addModules($modules);
        $this->assertEquals($modules, $this->app->getModules());
    }

    public function testAddModule(): void
    {
        $module = \stdClass::class;
        $this->app->addModule($module);
        $this->assertContains($module, $this->app->getModules());
    }

    /**
     */
    public function testAddMiddleware(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);

        $this->app->addMiddleware($middleware);
        $middlewares = $this->app->getMiddlewares();

        $this->assertContains($middleware, $middlewares);
    }

    /**
     */
    public function testAddMiddlewares(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = fn() => null;
        $middlewares = [$middleware1, $middleware2];

        $this->app->addMiddlewares($middlewares);
        $result = $this->app->getMiddlewares();

        $this->assertEquals($middlewares, $result);
    }

    public function testInit(): void
    {
        // Setup test environment
        $_ENV['APP_ENV'] = 'test';

        // Create a mock container
        $container = $this->createMock(ContainerInterface::class);

        // Create a mock request
        $request = $this->createMock(ServerRequestInterface::class);

        // Mock the ConcreteApplication to verify initModules and initKernel are called
        $app = $this->getMockBuilder(ConcreteApplication::class)
            ->setConstructorArgs([$this->kernel])
            ->onlyMethods(['initModules', 'initKernel', 'getContainer'])
            ->getMock();

        $request->expects($this->once())
            ->method('withAttribute')
            ->with(ApplicationInterface::class, $app)
            ->willReturn($request);

        // Set up expectations
        $app->expects($this->once())
            ->method('getContainer')
            ->willReturn($container);

        $app->expects($this->once())
            ->method('initModules')
            ->with($container)
            ->willReturn($app);

        $app->expects($this->once())
            ->method('initKernel')
            ->with($container)
            ->willReturn($app);

        // Execute the test
        $result = $app->init($request);

        // Verify results
        $this->assertSame($app, $result);
        $this->assertSame($request, $app->getRequest());

        // Cleanup
        unset($_ENV['APP_ENV']);
    }

    public function testInitWithNullRequest(): void
    {
        // Setup test environment
        $_ENV['APP_ENV'] = 'test';

        // Create a mock container
        $container = $this->createMock(ContainerInterface::class);

        // Mock the ConcreteApplication
        $app = $this->getMockBuilder(ConcreteApplication::class)
            ->setConstructorArgs([$this->kernel])
            ->onlyMethods(['initModules', 'initKernel', 'getContainer'])
            ->getMock();

        // Set up expectations
        $app->expects($this->once())
            ->method('getContainer')
            ->willReturn($container);

        $app->expects($this->once())
            ->method('initModules')
            ->with($container)
            ->willReturn($app);

        $app->expects($this->once())
            ->method('initKernel')
            ->with($container)
            ->willReturn($app);

        // Execute the test with null request
        $result = $app->init(null);

        // Verify results
        $this->assertSame($app, $result);
        $this->assertInstanceOf(ServerRequestInterface::class, $app->getRequest());

        // Cleanup
        unset($_ENV['APP_ENV']);
    }

    public function testRun(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $this->kernel->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $this->app->setRequest($request);
        $result = $this->app->run();

        $this->assertSame($response, $result);
    }

    public function testRunWithException(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $exception = new Exception('Test exception');

        $this->kernel->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willThrowException($exception);

        $this->kernel->expects($this->once())
            ->method('handleException')
            ->with($exception, $request)
            ->willReturn($response);

        $this->kernel->expects($this->once())
            ->method('getRequest')
            ->willReturn($request);

        $this->app->setRequest($request);
        $result = $this->app->run();

        $this->assertSame($response, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    public function testGetContainerTestEnv(): void
    {
        // Setup test environment
        $_ENV['APP_ENV'] = 'test';

        // Create a mock for the container builder to avoid actual container compilation
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('get')
            ->willReturnCallback(function ($id) {
                if ($id === ApplicationInterface::class) {
                    return $this->app;
                }
                if (in_array($id, ['app.project.dir', 'app.cache.dir'], true)) {
                    return '/tmp';
                }
                throw new class extends \Exception implements NotFoundExceptionInterface {
                };
            });

        // Use reflection to set the container
        $reflection = new \ReflectionProperty(AbstractApplication::class, 'container');
        $reflection->setValue($this->app, $containerMock);

        // Test
        $container = $this->app->getContainer();
        $this->assertSame($this->app, $container->get(ApplicationInterface::class));
        $this->assertIsString($container->get('app.project.dir'));
        $this->assertIsString($container->get('app.cache.dir'));

        // Cleanup
        unset($_ENV['APP_ENV']);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    public function testGetContainerEnvProd()
    {
        // Setup test environment
        $_ENV['APP_ENV'] = 'prod';
        $module = new class extends Module {
            public const DEFINITIONS = [
                'module.test' => self::class,
            ];
        };

        $this->app->addModule($module::class);

        // Test
        $container = $this->app->getContainer();
        $this->assertSame($this->app, $container->get(ApplicationInterface::class));
        $this->assertIsString($container->get('app.project.dir'));
        $this->assertIsString($container->get('app.cache.dir'));
        $this->assertInstanceof(KernelEvent::class, $container->get(KernelEvent::class));
        $this->assertInstanceof(KernelMiddleware::class, $container->get(KernelMiddleware::class));
        $this->assertInstanceof(EventDispatcherInterface::class, $container->get(EventDispatcherInterface::class));
        $this->assertSame($module::class, $container->get('module.test'));

        $this->removeDirectory($this->app->getProjectDir() . AbstractApplication::COMPILED_CONTAINER_DIRECTORY);
        $this->removeDirectory($this->app->getProjectDir() . AbstractApplication::PROXY_DIRECTORY);
        $this->removeDirectory($this->app->getProjectDir() . '/tmp');

        // Restore environment
        $_ENV['APP_ENV'] = 'test';
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    public function testGetConfigDir(): void
    {
        $dir = $this->app->getProjectDir();
        mkdir($dir . '/config');

        $configDir = $this->app->getConfigDir();
        $this->assertStringEndsWith('/config', $configDir);
        $this->assertDirectoryExists($configDir);

        rmdir($dir . '/config');
    }

    public function testAddListener(): void
    {
        $listener = 'Test\Listener\TestListener';
        $this->app->addListener($listener);

        $listeners = $this->app->getListeners();
        $this->assertContains($listener, $listeners);
    }

    public function testAddListeners(): void
    {
        $listeners = [
            'Test\Listener\Listener1',
            'Test\Listener\Listener2'
        ];

        $this->app->addListeners($listeners);
        $result = $this->app->getListeners();

        $this->assertEquals($listeners, $result);
    }

    public function testGetProjectDir(): void
    {
        $projectDir = $this->app->getProjectDir();
        $this->assertIsString($projectDir);
        $this->assertDirectoryExists($projectDir);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetRunTimeDefinitions(): void
    {
        $reflection = new \ReflectionClass(ConcreteApplication::class);
        $method = $reflection->getMethod('getRunTimeDefinitions');

        $definitions = $method->invoke($this->app);

        $this->assertIsArray($definitions);
        $this->assertArrayHasKey(ApplicationInterface::class, $definitions);
        $this->assertArrayHasKey('app.project.dir', $definitions);
        $this->assertArrayHasKey('app.cache.dir', $definitions);
        $this->assertSame($this->app, $definitions[ApplicationInterface::class]);
    }

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->kernel->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->createMock(ServerRequestInterface::class));

        $this->app = new ConcreteApplication($this->kernel);
    }

    protected function tearDown(): void
    {
        // Clean up container instance between tests
        $reflection = new \ReflectionProperty(AbstractApplication::class, 'container');
        $reflection->setValue($this->app, null);

        // Clean up environment variables
        unset($_ENV['APP_ENV']);
    }
}
