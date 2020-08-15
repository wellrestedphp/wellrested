<?php

namespace WellRESTed;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Test\TestCase;
use WellRESTed\Transmission\TransmitterInterface;

class ServerTest extends TestCase
{
    use ProphecyTrait;

    private $transmitter;
    /** @var Server */
    private $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transmitter = $this->prophesize(TransmitterInterface::class);
        $this->transmitter->transmit(Argument::cetera());

        $this->server = new Server();
        $this->server->setTransmitter($this->transmitter->reveal());
    }

    // -------------------------------------------------------------------------

    public function testDispatchesMiddlewareStack(): void
    {
        // This test will add a string to this array from each middleware.

        $steps = [];

        $this->server->add(
            function ($rqst, $resp, $next) use (&$steps) {
                $steps[] = 'first';
                return $next($rqst, $resp);
            }
        );

        $this->server->add(
            function ($rqst, $resp, $next) use (&$steps) {
                $steps[] = 'second';
                return $next($rqst, $resp);
            }
        );

        $this->server->add(
            function ($rqst, $resp, $next) use (&$steps) {
                $steps[] = 'third';
                return $next($rqst, $resp);
            }
        );

        $this->server->respond();

        $this->assertEquals(['first', 'second', 'third'], $steps);
    }

    public function testDispatchedRequest(): void
    {
        $request = new ServerRequest();
        $capturedRequest = null;

        $this->server->setRequest($request);
        $this->server->add(function ($rqst, $resp) use (&$capturedRequest) {
            $capturedRequest = $rqst;
            return $resp;
        });
        $this->server->respond();

        $this->assertSame($request, $capturedRequest);
    }

    public function testDispatchedResponse(): void
    {
        $response = new Response();
        $capturedResponse = null;

        $this->server->setResponse($response);
        $this->server->add(function ($rqst, $resp) use (&$capturedResponse) {
            $capturedResponse = $resp;
            return $resp;
        });
        $this->server->respond();

        $this->assertSame($response, $capturedResponse);
    }

    // -------------------------------------------------------------------------
    // Respond

    public function testRespondSendsResponseToTransmitter(): void
    {
        $expectedResponse = new Response(200);

        $this->server->add(
            function ($rqst, $resp, $next) {
                return $next($rqst, $resp);
            }
        );

        $this->server->add(
            function ($rqst, $resp, $next) {
                return $next($rqst, $resp);
            }
        );

        $this->server->add(
            function () use ($expectedResponse) {
                return $expectedResponse;
            }
        );

        $this->server->respond();

        $this->transmitter->transmit(
            Argument::any(),
            $expectedResponse
        )->shouldHaveBeenCalled();
    }

    // -------------------------------------------------------------------------
    // Router

    public function testCreatesRouterWithDispatcher(): void
    {
        $dispatcher = $this->prophesize(DispatcherInterface::class);
        $dispatcher->dispatch(Argument::cetera())->will(
            function ($args) {
                list($middleware, $request, $response, $next) = $args;
                return $next($request, $response);
            }
        );

        $this->server->setDispatcher($dispatcher->reveal());
        $this->server->setPathVariablesAttributeName('pathVariables');

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withRequestTarget('/');
        $response = new Response();
        $next = function ($rqst, $resp) {
            return $resp;
        };

        $router = $this->server->createRouter();
        $router->register('GET', '/', 'middleware');
        $router($request, $response, $next);

        $dispatcher->dispatch(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    // -------------------------------------------------------------------------
    // Attributes

    public function testAddsAttributesToRequest(): void
    {
        $this->server->setAttributes([
            'name' => 'value'
        ]);

        $capturedRequest = null;
        $this->server->add(function ($rqst, $resp) use (&$capturedRequest) {
            $capturedRequest = $rqst;
            return $resp;
        });

        $this->server->respond();

        $this->assertEquals('value', $capturedRequest->getAttribute('name'));
    }

    // -------------------------------------------------------------------------
    // End of Stack

    public function testReturnsLastDoublePassResponseAtEndOfStack(): void
    {
        $defaultResponse = new Response(404);

        $this->server->setResponse($defaultResponse);

        $this->server->add(
            function ($rqst, $resp, $next) {
                return $next($rqst, $resp);
            }
        );

        $this->server->respond();

        $this->transmitter->transmit(
            Argument::any(),
            $defaultResponse
        )->shouldHaveBeenCalled();
    }
}
