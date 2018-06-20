<?php

namespace WellRESTed\Test\Unit;

use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Server;
use WellRESTed\Test\TestCase;
use WellRESTed\Transmission\TransmitterInterface;

class ServerTest extends TestCase
{
    private $transmitter;
    private $server;

    public function setUp()
    {
        parent::setUp();

        $this->transmitter = $this->prophesize(TransmitterInterface::class);
        $this->transmitter->transmit(Argument::cetera())->willReturn();

        $this->server = new Server();
    }

    private function respond()
    {
        $this->server->respond(
            new ServerRequest(),
            new Response(),
            $this->transmitter->reveal()
        );
    }

    // ------------------------------------------------------------------------

    public function testReturnsDispatcher()
    {
        $this->assertNotNull($this->server->getDispatcher());
    }

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

        $this->respond();

        $this->assertEquals(['first', 'second', 'third'], $steps);
    }

    // ------------------------------------------------------------------------
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

        $this->respond();

        $this->transmitter->transmit(
            Argument::any(),
            $expectedResponse
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
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

        $server = new Server(null, $dispatcher->reveal());

        $request = (new ServerRequest())
            ->withMethod("GET")
            ->withRequestTarget("/");
        $response = new Response();
        $next = function ($rqst, $resp) {
            return $resp;
        };

        $router = $server->createRouter();
        $router->register("GET", "/", "middleware");
        $router($request, $response, $next);

        $dispatcher->dispatch(Argument::cetera())
            ->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Attributes

    public function testAddsAttributesToRequest()
    {
        $attributes = [
            'name' => 'value'
        ];

        $server = new Server($attributes);

        $spyMiddleware = function ($rqst, $resp) use (&$capturedRequest) {
            $capturedRequest = $rqst;
            return $resp;
        };

        $server->add($spyMiddleware);

        $server->respond(
            new ServerRequest(),
            new Response(),
            $this->transmitter->reveal()
        );

        $this->assertEquals('value', $capturedRequest->getAttribute('name'));
    }

    // ------------------------------------------------------------------------
    // End of Stack

    public function testRespondsWithDefaultHandlerWhenReachingEndOfStack()
    {
        $this->respond();

        $has404StatusCode = function ($response) {
            return $response->getStatusCode() === 404;
        };

        $this->transmitter->transmit(
            Argument::any(),
            Argument::that($has404StatusCode)
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Defaults

    public function testUsesDefaultRequestResponseAndTransmitter()
    {
        $request = new ServerRequest();
        $response = new Response();

        $server = new TestServer(
            $request,
            $response,
            $this->transmitter->reveal()
        );
        $server->add(function ($rqst, $resp) {
            return $resp;
        });
        $server->respond();

        $this->transmitter->transmit($request, $response)
            ->shouldHaveBeenCalled();
    }
}

// ----------------------------------------------------------------------------

class TestServer extends Server
{
    /** @var ServerRequestInterface */
    private $request;
    /** @var ResponseInterface */
    private $response;
    /** @var TransmitterInterface */
    private $transmitter;

    /**
     * TestServer constructor.
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param TransmitterInterface $transmitter
     */
    public function __construct(
        ServerRequestInterface $request,
        ResponseInterface $response,
        TransmitterInterface $transmitter
    ) {
        parent::__construct();
        $this->request = $request;
        $this->response = $response;
        $this->transmitter = $transmitter;
    }

    protected function getRequest()
    {
        return $this->request;
    }

    protected function getResponse()
    {
        return $this->response;
    }

    protected function getTransmitter()
    {
        return $this->transmitter;
    }
}
