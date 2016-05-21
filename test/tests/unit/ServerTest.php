<?php

namespace WellRESTed\Test\Unit;

use Prophecy\Argument;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Server;
use WellRESTed\Test\Doubles\NextMock;

/** @covers WellRESTed\Server */
class ServerTest extends \PHPUnit_Framework_TestCase
{
    private $dispatcher;
    private $next;
    private $request;
    private $response;
    private $transmitter;
    private $server;

    public function setUp()
    {
        parent::setUp();
        $this->request = new ServerRequest();
        $this->response = new Response();
        $this->next = new NextMock();

        $this->transmitter = $this->prophesize('WellRESTed\Transmission\TransmitterInterface');
        $this->transmitter->transmit(Argument::cetera())->willReturn();
        $this->dispatcher = $this->prophesize('WellRESTed\Dispatching\DispatcherInterface');
        $this->dispatcher->dispatch(Argument::cetera())->will(
            function ($args) {
                list($middleware, $request, $response, $next) = $args;
                return $next($request, $response);
            }
        );

        $this->server = $this->getMockBuilder('WellRESTed\Server')
            ->setMethods(["getDefaultDispatcher", "getRequest", "getResponse", "getTransmitter"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->server->expects($this->any())
            ->method("getDefaultDispatcher")
            ->will($this->returnValue($this->dispatcher->reveal()));
        $this->server->expects($this->any())
            ->method("getRequest")
            ->will($this->returnValue($this->request));
        $this->server->expects($this->any())
            ->method("getResponse")
            ->will($this->returnValue($this->response));
        $this->server->expects($this->any())
            ->method("getTransmitter")
            ->will($this->returnValue($this->transmitter->reveal()));
        $this->server->__construct();
    }

    public function testCreatesInstances()
    {
        $server = new Server();
        $this->assertNotNull($server);
    }

    public function testAddIsFluid()
    {
        $server = new Server();
        $this->assertSame($server, $server->add("middleware"));
    }

    public function testReturnsDispatcher()
    {
        $this->assertSame($this->dispatcher->reveal(), $this->server->getDispatcher());
    }

    public function testDispatchesMiddlewareStack()
    {
        $this->server->add("first");
        $this->server->add("second");
        $this->server->add("third");

        $this->server->dispatch($this->request, $this->response, $this->next);

        $this->dispatcher->dispatch(
            ["first", "second", "third"],
            $this->request,
            $this->response,
            $this->next
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Respond

    public function testRespondDispatchesRequest()
    {
        $this->server->respond();
        $this->dispatcher->dispatch(
            Argument::any(),
            $this->request,
            Argument::any(),
            Argument::any()
        )->shouldHaveBeenCalled();
    }

    public function testRespondDispatchesResponse()
    {
        $this->server->respond();
        $this->dispatcher->dispatch(
            Argument::any(),
            Argument::any(),
            $this->response,
            Argument::any()
        )->shouldHaveBeenCalled();
    }

    public function testRespondSendsResponseToResponder()
    {
        $this->server->respond();
        $this->transmitter->transmit(
            $this->request,
            $this->response
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Router

    public function testCreatesRouterWithDispatcher()
    {
        $this->request = $this->request
            ->withMethod("GET")
            ->withRequestTarget("/");

        $router = $this->server->createRouter();
        $router->register("GET", "/", "middleware");
        $router($this->request, $this->response, $this->next);

        $this->dispatcher->dispatch(
            "middleware",
            $this->request,
            $this->response,
            $this->next
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Attributes

    public function testAddsAttributesToRequest()
    {
        $attributes = [
            "name" => "value"
        ];

        $this->server->__construct($attributes);
        $this->server->respond();

        $isRequestWithExpectedAttribute = function ($request) {
            return $request->getAttribute("name") === "value";
        };

        $this->dispatcher->dispatch(
            Argument::any(),
            Argument::that($isRequestWithExpectedAttribute),
            Argument::any(),
            Argument::any()
        )->shouldHaveBeenCalled();
    }
}
