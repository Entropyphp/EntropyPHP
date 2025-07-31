<?php

namespace Entropy\Tests\Middleware;

use Entropy\Middleware\BodyParserMiddleware;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BodyParserMiddlewareTest extends TestCase
{
    private ServerRequestInterface|MockObject $request;
    private RequestHandlerInterface|MockObject $handler;
    private StreamInterface|MockObject $stream;
    private ResponseInterface|MockObject $response;
    private MiddlewareInterface $middleware;

    public function testProcessWithJsonContentType(): void
    {
        $jsonData = '{"name":"John","age":30}';
        $expected = ['name' => 'John', 'age' => 30];

        // Set up request with JSON content type
        $this->request->expects($this->once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->stream->expects($this->once())
            ->method('getContents')
            ->willReturn($jsonData);

        // Expect the request to be updated with a parsed body
        $this->request->expects($this->once())
            ->method('withParsedBody')
            ->with($expected)
            ->willReturn($this->request);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $response = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $response);
    }

    public function testProcessWithUnsupportedMethod(): void
    {
        // Set up request with unsupported method
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        // Should not process the body for GET requests
        $this->request->expects($this->never())
            ->method('getHeaderLine');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $response = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $response);
    }

    public function testProcessWithUnsupportedContentType(): void
    {
        // Set up request with unsupported content type
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/xml');

        // Should not try to parse XML by default
        $this->stream->expects($this->never())
            ->method('getContents');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $response = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $response);
    }

    public function testProcessWithCustomParser(): void
    {
        $csvData = "name,age\nJohn,30";
        $expected = [['name' => 'John', 'age' => '30']];

        // Set up request with custom content type
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('text/csv');

        $this->stream->expects($this->once())
            ->method('getContents')
            ->willReturn($csvData);

        // Create a custom CSV parser
        $csvParser = function ($body) {
            $lines = explode("\n", trim($body));
            $headers = str_getcsv(array_shift($lines));
            $result = [];
            foreach ($lines as $line) {
                $result[] = array_combine($headers, str_getcsv($line));
            }
            return $result;
        };

        // Create listener with custom parser
        $middleware = new BodyParserMiddleware(['json' => false]);
        $middleware->addParser(['text/csv'], $csvParser);

        // Expect the request to be updated with a parsed body
        $this->request->expects($this->once())
            ->method('withParsedBody')
            ->with($expected)
            ->willReturn($this->request);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $response = $middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $response);
    }

    public function testProcessWithEmptyBody(): void
    {
        // Set up request with empty body
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('POST');

        $this->request->expects($this->once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');

        $this->stream->expects($this->once())
            ->method('getContents')
            ->willReturn('');

        // Should parse empty JSON as an empty array
        $this->request->expects($this->once())
            ->method('withParsedBody')
            ->with([])
            ->willReturn($this->request);

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $response = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($this->response, $response);
    }

    public function testSetAndGetMethods(): void
    {
        $methods = ['POST', 'PUT'];
        $middleware = new BodyParserMiddleware();

        // Test setter returns self for chaining
        $result = $middleware->setMethods($methods);
        $this->assertSame($middleware, $result);

        // Test getter returns the set methods
        $this->assertSame($methods, $middleware->getMethods());
    }

    public function testAddAndGetParsers(): void
    {
        $middleware = new BodyParserMiddleware(['json' => false]);

        // Test adding a parser
        $parser = function ($body) {
            return ['parsed' => $body];
        };

        $result = $middleware->addParser(['test/type'], $parser);
        $this->assertSame($middleware, $result);

        // Test getting parsers
        $parsers = $middleware->getParsers();
        $this->assertArrayHasKey('test/type', $parsers);
        $this->assertSame($parser, $parsers['test/type']);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->stream = $this->createMock(StreamInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->middleware = new BodyParserMiddleware();

        // Default setup for request to return stream
        $this->request->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);
    }
}
