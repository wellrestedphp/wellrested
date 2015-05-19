<?php

namespace WellRESTed\Test\Unit\Server;

use Prophecy\Argument;
use WellRESTed\Server;

/**
 * @coversDefaultClass WellRESTed\Server
 * @uses WellRESTed\Server
 */
class ServerTest extends \PHPUnit_Framework_TestCase
{
    private $dispatcher;
    private $request;
    private $response;
    private $transmitter;
    private $server;

    public function setUp()
    {
        parent::setUp();
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->request->withAttribute(Argument::cetera())->willReturn($this->request->reveal());
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
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
            ->setMethods(["getDispatcher", "getRequest", "getResponse", "getTransmitter"])
            ->disableOriginalConstructor()
            ->getMock();
        $this->server->expects($this->any())
            ->method("getDispatcher")
            ->will($this->returnValue($this->dispatcher->reveal()));
        $this->server->expects($this->any())
            ->method("getRequest")
            ->will($this->returnValue($this->request->reveal()));
        $this->server->expects($this->any())
            ->method("getResponse")
            ->will($this->returnValue($this->response->reveal()));
        $this->server->expects($this->any())
            ->method("getTransmitter")
            ->will($this->returnValue($this->transmitter->reveal()));
        $this->server->__construct();
    }

    /**
     * @covers ::__construct
     * @covers ::getDispatcher
     * @uses WellRESTed\Dispatching\Dispatcher
     */
    public function testCreatesInstances()
    {
        $server = new Server();
        $this->assertNotNull($server);
    }

    /**
     * @covers ::add
     */
    public function testAddIsFluid()
    {
        $server = new Server();
        $this->assertSame($server, $server->add("middleware"));
    }

    /**
     * @covers ::add
     * @covers ::dispatch
     */
    public function testDispatchesMiddlewareStack()
    {
        $next = function ($request, $response) {
            return $response;
        };

        $this->server->add("first");
        $this->server->add("second");
        $this->server->add("third");

        $this->server->dispatch($this->request->reveal(), $this->response->reveal(), $next);

        $this->dispatcher->dispatch(
            ["first", "second", "third"],
            $this->request->reveal(),
            $this->response->reveal(),
            $next
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Respond

    /**
     * @covers ::respond
     */
    public function testRespondDispatchesRequest()
    {
        $this->server->respond();
        $this->dispatcher->dispatch(
            Argument::any(),
            $this->request->reveal(),
            Argument::any(),
            Argument::any()
        )->shouldHaveBeenCalled();
    }

    /**
     * @covers ::respond
     */
    public function testRespondDispatchesResponse()
    {
        $this->server->respond();
        $this->dispatcher->dispatch(
            Argument::any(),
            Argument::any(),
            $this->response->reveal(),
            Argument::any()
        )->shouldHaveBeenCalled();
    }

    /**
     * @covers ::respond
     */
    public function testRespondSendsResponseToResponder()
    {
        $this->server->respond();
        $this->transmitter->transmit(
            $this->request->reveal(),
            $this->response->reveal()
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Router

    /**
     * @covers ::createRouter
     * @uses WellRESTed\Routing\Router
     * @uses WellRESTed\Routing\MethodMap
     * @uses WellRESTed\Routing\Route\RouteFactory
     * @uses WellRESTed\Routing\Route\Route
     * @uses WellRESTed\Routing\Route\StaticRoute
     */
    public function testCreatesRouterWithDispatcher()
    {
        $this->request->getMethod()->willReturn("GET");
        $this->request->getRequestTarget()->willReturn("/");

        $next = function ($request, $response) {
            return $response;
        };

        $router = $this->server->createRouter();
        $router->register("GET", "/", "middleware");
        $router($this->request->reveal(), $this->response->reveal(), $next);

        $this->dispatcher->dispatch(
            "middleware",
            $this->request->reveal(),
            $this->response->reveal(),
            $next
        )->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Attributes

    /**
     * @covers ::respond
     */
    public function testAddsAttributesToRequest()
    {
        $attributes = [
            "name" => "value"
        ];

        $this->server->__construct($attributes);
        $this->server->respond();
        $this->request->withAttribute("name", "value")->shouldHaveBeenCalled();
    }

}
