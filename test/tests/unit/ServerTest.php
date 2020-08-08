<?php

namespace WellRESTed\Test\Unit;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Message\Stream;
use WellRESTed\Server;
use WellRESTed\Test\TestCase;
use WellRESTed\Transmission\TransmitterInterface;

require_once __DIR__ . '/../../src/HeaderStack.php';

class ServerTest extends TestCase
{
    use ProphecyTrait;

    private $transmitter;
    /** @var Server */
    private $server;

    public function setUp(): void
    {
        parent::setUp();

        $this->transmitter = $this->prophesize(TransmitterInterface::class);
        $this->transmitter->transmit(Argument::cetera())->willReturn();

        $this->server = new Server();
        $this->server->setTransmitter($this->transmitter->reveal());
    }

    // -------------------------------------------------------------------------

    public function testDispatchesMiddlewareStack()
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

    public function testDispatchedRequest()
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

    public function testDispatchedResponse()
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

    public function testRespondSendsResponseToTransmitter()
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

    public function testCreatesRouterWithDispatcher()
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
            ->withMethod("GET")
            ->withRequestTarget("/");
        $response = new Response();
        $next = function ($rqst, $resp) {
            return $resp;
        };

        $router = $this->server->createRouter();
        $router->register("GET", "/", "middleware");
        $router($request, $response, $next);

        $dispatcher->dispatch(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    // -------------------------------------------------------------------------
    // Attributes

    public function testAddsAttributesToRequest()
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

    public function testReturnsLastDoublePassResponseAtEndOfStack()
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

    // -------------------------------------------------------------------------

    public function testCreatesStockTransmitterByDefault()
    {
        $content = "Hello, world!";

        $response = (new Response())
            ->withBody(new Stream($content));

        $server = new Server();
        $server->add(function () use ($response) {
            return $response;
        });

        ob_start();
        $server->respond();
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($content, $captured);
    }
}
