<?php

declare(strict_types=1);

namespace Entropy\Tests\EventListener;

use Entropy\Event\ViewEvent;
use Entropy\EventListener\StringResponseListener;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class StringResponseListenerTest extends TestCase
{
    private ViewEvent|MockObject $event;
    private StringResponseListener $listener;

    public function testInvokeWithStringResult(): void
    {
        $stringResult = 'Hello, World!';

        $this->event->expects($this->once())
            ->method('getResult')
            ->willReturn($stringResult);

        // Expect setResponse to be called with a Response object
        $this->event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function (ResponseInterface $response) use ($stringResult) {
                return (string)$response->getBody() === $stringResult &&
                    $response->getStatusCode() === 200;
            }));

        ($this->listener)($this->event);
    }

    public function testInvokeWithNonStringResult(): void
    {
        $nonStringResult = ['key' => 'value'];

        $this->event->expects($this->once())
            ->method('getResult')
            ->willReturn($nonStringResult);

        // setResponse should not be called for non-string results
        $this->event->expects($this->never())
            ->method('setResponse');

        ($this->listener)($this->event);
    }

    public function testInvokeWithNullResult(): void
    {
        $this->event->expects($this->once())
            ->method('getResult')
            ->willReturn(null);

        // setResponse should not be called for null results
        $this->event->expects($this->never())
            ->method('setResponse');

        ($this->listener)($this->event);
    }

    public function testInvokeWithEmptyString(): void
    {
        $emptyString = '';
        $expectedResponse = new Response(200, [], $emptyString);

        $this->event->expects($this->once())
            ->method('getResult')
            ->willReturn($emptyString);

        // Empty string should still create a response
        $this->event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function (ResponseInterface $response) {
                return (string)$response->getBody() === '' &&
                    $response->getStatusCode() === 200;
            }));

        ($this->listener)($this->event);
    }

    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = StringResponseListener::getSubscribedEvents();

        $this->assertIsArray($subscribedEvents);
        $this->assertArrayHasKey(ViewEvent::NAME, $subscribedEvents);
        $this->assertIsInt($subscribedEvents[ViewEvent::NAME]);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->event = $this->createMock(ViewEvent::class);
        $this->listener = new StringResponseListener();
    }
}
