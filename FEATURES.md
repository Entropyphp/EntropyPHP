# EntropyPHP Framework

EntropyPHP is a modern PHP framework designed to help developers build web applications with a clean, maintainable architecture. It follows PSR standards and incorporates modern PHP practices.

## Features

- **Event-driven Architecture**: Built around a powerful event system for flexible application flow
- **PSR-7 HTTP Messages**: Uses standard HTTP message interfaces
- **PSR-15 Middleware**: Supports middleware-based request handling
- **Dependency Injection**: Built-in DI container using PHP-DI
- **Parameter Resolution**: Automatic resolution of controller parameters
- **Flexible Routing**: Route-based controller dispatching
- **Modern PHP**: Requires PHP 8.1+

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

Install EntropyPHP using Composer:

```bash
composer require entropyphp/entropyphp
```

## Basic Usage

### Creating a Simple Application

```php
<?php

use Entropy\Kernel\KernelEvent;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

// Create a DI container
$container = new \DI\Container();

// Create the kernel
$kernel = $container->get(KernelEvent::class);

// Define a controller
$controller = function (ServerRequestInterface $request): ResponseInterface {
    return new \GuzzleHttp\Psr7\Response(
        200,
        ['Content-Type' => 'text/html'],
        '<h1>Hello, EntropyPHP!</h1>'
    );
};

// Create a request
$request = ServerRequest::fromGlobals()
    ->withAttribute('_controller', $controller);

// Handle the request
$response = $kernel->handle($request);

// Send the response
(new \GuzzleHttp\Psr7\HttpResponse($response))->send();
```

### Using Middleware

```php
<?php

use Entropy\Middleware\CombinedMiddleware;
use Entropy\Middleware\RouteCallerMiddleware;

// Create middleware stack
$middleware = new CombinedMiddleware([
    // Add your middleware here
    new RouteCallerMiddleware($container),
]);

// Add middleware to your application
// ...
```

## Advanced Usage

For more advanced usage examples, please refer to the documentation or check the tests directory.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

- **Willy** - [willy68](https://github.com/willy68)