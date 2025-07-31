<?php

declare(strict_types=1);

namespace Entropy\Tests\EventListener;

use Entropy\Event\Events;
use Entropy\Event\RequestEvent;
use Entropy\EventListener\BodyParserListener;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class BodyParserListenerTest extends TestCase
{
    private ServerRequestInterface|MockObject $request;
    private RequestEvent|MockObject $event;
    private StreamInterface|MockObject $stream;

    public function testInvokeWithJsonContentType(): void
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

        $this->event->expects($this->once())
            ->method('setRequest')
            ->with($this->request);

        $listener = new BodyParserListener();
        $listener($this->event);
    }

    public function testInvokeWithUnsupportedMethod(): void
    {
        // Set up request with unsupported method
        $this->request->expects($this->once())
            ->method('getMethod')
            ->willReturn('GET');

        // Should not process the body for GET requests
        $this->request->expects($this->never())
            ->method('getHeaderLine');

        $listener = new BodyParserListener();
        $listener($this->event);
    }

    public function testInvokeWithUnsupportedContentType(): void
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

        $listener = new BodyParserListener();
        $listener($this->event);
    }

    public function testInvokeWithCustomParser(): void
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
        $listener = new BodyParserListener(['json' => false]);
        $listener->addParser(['text/csv'], $csvParser);

        // Expect the request to be updated with a parsed body
        $this->request->expects($this->once())
            ->method('withParsedBody')
            ->with($expected)
            ->willReturn($this->request);

        $this->event->expects($this->once())
            ->method('setRequest')
            ->with($this->request);

        $listener($this->event);
    }

    public function testInvokeWithEmptyBody(): void
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

        $listener = new BodyParserListener();
        $listener($this->event);
    }

    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = BodyParserListener::getSubscribedEvents();

        $this->assertIsArray($subscribedEvents);
        $this->assertArrayHasKey(Events::REQUEST, $subscribedEvents);
        $this->assertIsInt($subscribedEvents[Events::REQUEST]);
    }

    public function testSetAndGetMethods(): void
    {
        $methods = ['POST', 'PUT'];
        $listener = new BodyParserListener();

        // Test setter returns self for chaining
        $result = $listener->setMethods($methods);
        $this->assertSame($listener, $result);

        // Test getter returns the set methods
        $this->assertSame($methods, $listener->getMethods());
    }

    public function testAddAndGetParsers(): void
    {
        $listener = new BodyParserListener(['json' => false]);

        // Test adding a parser
        $parser = function ($body) {
            return ['parsed' => $body];
        };

        $result = $listener->addParser(['test/type'], $parser);
        $this->assertSame($listener, $result);

        // Test getting parsers
        $parsers = $listener->getParsers();
        $this->assertArrayHasKey('test/type', $parsers);
        $this->assertSame($parser, $parsers['test/type']);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->event = $this->createMock(RequestEvent::class);
        $this->stream = $this->createMock(StreamInterface::class);

        // Default setup for event to return request
        $this->event->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        // Default setup for request to return stream
        $this->request->expects($this->any())
            ->method('getBody')
            ->willReturn($this->stream);
    }
}
