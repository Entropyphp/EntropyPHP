<?php

/**
 * CakePHP(tm): Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.6.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Entropy\Middleware;

use Closure;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Parse encoded request body data.
 *
 * Enables JSON request payloads to be parsed into the request's
 * You must provide CSRF protection and validation.
 *
 * You can also add your own request body parsers using the `addParser()` method.
 */
class BodyParserMiddleware implements MiddlewareInterface
{
    /**
     * Registered Parsers
     *
     * @var Closure[]
     */
    protected array $parsers = [];

    /**
     * The HTTP methods to parse data on.
     *
     * @var string[]
     */
    protected array $methods = ['PUT', 'POST', 'PATCH', 'DELETE'];

    /**
     * Constructor
     *
     * ### Options
     *
     * - `json` Set false to disable JSON body parsing.
     *   Handling requires more care than JSON does.
     * - `methods` The HTTP methods to parse on. Defaults to PUT, POST, PATCH DELETE.
     *
     * @param array $options The options to use. See above.
     */
    public function __construct(array $options = [])
    {
        $options += ['json' => true, 'methods' => $this->methods];
        if ($options['json']) {
            $this->addParser(
                ['application/json', 'text/json'],
                [$this, 'decodeJson']
            );
        }
        if ($options['methods']) {
            $this->setMethods($options['methods']);
        }
    }

    /**
     * Set the HTTP methods to parse request bodies on.
     *
     * @param string[] $methods The methods to parse data on.
     * @return $this
     */
    public function setMethods(array $methods): static
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * Get the HTTP methods to parse request bodies on.
     *
     * @return string[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Add a parser.
     *
     * Map a set of content-type header values to be parsed by the $parser.
     *
     * ### Example
     *
     * A naive CSV request body parser could be built like so:
     *
     * ```
     * $parser->addParser(['text/csv'], function ($body) {
     *   return str_getcsv($body);
     * });
     * ```
     *
     * @param string[] $types An array of content-type header values to match. eg. application/json
     * @param Closure|callable $parser The parser function. Must return an array of data to be inserted
     *   into the request.
     * @return $this
     */
    public function addParser(array $types, Closure|callable $parser): static
    {
        foreach ($types as $type) {
            $type = strtolower($type);
            $this->parsers[$type] = $parser;
        }

        return $this;
    }

    /**
     * Get the current parsers
     *
     * @return Closure[]
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    /**
     * Apply the middleware.
     *
     * Will modify the request adding a parsed body if the content-type is known.
     *
     * @param ServerRequestInterface $request The request.
     * @param RequestHandlerInterface $handler The request handler.
     * @return ResponseInterface A response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array($request->getMethod(), $this->methods, true)) {
            return $handler->handle($request);
        }
        [$type] = explode(';', $request->getHeaderLine('Content-Type'));
        $type = strtolower($type);
        if (!isset($this->parsers[$type])) {
            return $handler->handle($request);
        }

        $parser = $this->parsers[$type];
        $result = $parser($request->getBody()->getContents());
        if (!is_array($result)) {
            throw new InvalidArgumentException();
        }
        $request = $request->withParsedBody($result);

        return $handler->handle($request);
    }

    /**
     * Decode JSON into an array.
     *
     * @param string $body The request body to decode
     * @return array|null
     */
    protected function decodeJson(string $body): ?array
    {
        if ($body === '') {
            return [];
        }
        $decoded = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return (array)$decoded;
        }

        return null;
    }
}
