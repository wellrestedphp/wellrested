<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\HttpExceptions\NotFoundException;
use WellRESTed\Routing\Router;

// TODO Tests that ensure hooks are called at correct times
// TODO Test default finalization hooks

/**
 * @coversDefaultClass WellRESTed\Routing\Router
 * @uses WellRESTed\Routing\Router
 * @uses WellRESTed\Message\Stream
 * @uses WellRESTed\Routing\Dispatcher
 * @uses WellRESTed\Routing\RouteMap
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;

    public function setUp()
    {
        parent::setUp();
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
    }

    // ------------------------------------------------------------------------
    // Construction

    /**
     * @covers ::__construct
     * @covers ::getDispatcher
     * @covers ::getRouteMap
     * @covers ::getPreRouteHooks
     * @covers ::getPostRouteHooks
     * @covers ::getFinalizationHooks
     * @covers ::getStatusHooks
     */
    public function testCreatesInstance()
    {
        $router = new Router();
        $this->assertNotNull($router);
    }

    // ------------------------------------------------------------------------
    // Adding routes

    /**
     * @covers ::add
     */
    public function testAddRegistersRouteWithRouteMap()
    {
        $routeMap = $this->prophesize('WellRESTed\Routing\RouteMapInterface');
        $routeMap->add(Argument::cetera())->willReturn();

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRouteMap"])
            ->disableOriginalConstructor()
            ->getMock();
        $router->expects($this->any())
            ->method("getRouteMap")
            ->will($this->returnValue($routeMap->reveal()));
        $router->__construct();

        $method = "GET";
        $target = "/path/{id}";
        $middleware = "Middleware";

        $router->add($method, $target, $middleware);
        $routeMap->add($method, $target, $middleware)->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Dispatching Routes

    /**
     * @covers ::dispatch
     */
    public function testDispatchesRouteMap()
    {
        $routeMap = $this->prophesize('WellRESTed\Routing\RouteMapInterface');
        $routeMap->dispatch(Argument::cetera())->willReturn();

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRouteMap"])
            ->disableOriginalConstructor()
            ->getMock();
        $router->expects($this->any())
            ->method("getRouteMap")
            ->will($this->returnValue($routeMap->reveal()));
        $router->__construct();

        $request = $this->request->reveal();
        $resonse = $this->response->reveal();
        $router->dispatch($request, $resonse);

        $routeMap->dispatch($request, Argument::any())->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Hooks

    /**
     * @covers ::addPreRouteHook
     * @covers ::dispatchPreRouteHooks
     */
    public function testDispatchesPreRouteHook()
    {
        $hook = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $hook->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $router = new Router();
        $router->addPreRouteHook($hook->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $hook->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::addPostRouteHook
     * @covers ::dispatchPostRouteHooks
     */
    public function testDispatchesPostRouteHook()
    {
        $hook = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $hook->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $router = new Router();
        $router->addPostRouteHook($hook->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $hook->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::addFinalizationHook
     * @covers ::dispatchFinalizationHooks
     */
    public function testDispatchesFinalHooks()
    {
        $hook = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $hook->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $router = new Router();
        $router->addFinalizationHook($hook->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $hook->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Status Handlers

    /**
     * @covers ::dispatch
     * @covers ::dispatchStatusHooks
     * @covers ::setStatusHook
     */
    public function testDispatchesHookForStatusCode()
    {
        $this->response->getStatusCode()->willReturn(403);

        $statusMiddleware = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $statusMiddleware->dispatch(Argument::cetera())->willReturn();

        $router = new Router();
        $router->setStatusHook(403, $statusMiddleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $statusMiddleware->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::dispatchStatusHooks
     * @covers ::setStatusHook
     */
    public function testDispatchesStatusHookForHttpException()
    {
        $statusMiddleware = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $statusMiddleware->dispatch(Argument::cetera())->willReturn();

        $routeMap = $this->prophesize('WellRESTed\Routing\RouteMapInterface');
        $routeMap->dispatch(Argument::cetera())->willThrow(new NotFoundException());

        $this->response->withStatus(Argument::any())->will(
            function ($args) {
                $this->getStatusCode()->willReturn($args[0]);
                return $this;
            }
        );
        $this->response->withBody(Argument::any())->willReturn($this->response->reveal());

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRouteMap"])
            ->disableOriginalConstructor()
            ->getMock();
        $router->expects($this->any())
            ->method("getRouteMap")
            ->will($this->returnValue($routeMap->reveal()));
        $router->__construct();

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $this->response->withStatus(404)->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Respond

    /**
     * @covers ::respond
     * @covers ::getRequest
     * @covers ::getResponse
     * @covers ::getResponder
     */
    public function testRespondDispatchesRequest()
    {
        $middleware = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $middleware->dispatch(Argument::cetera())->willReturn();

        $responder = $this->prophesize('WellRESTed\Routing\ResponderInterface');
        $responder->respond(Argument::any())->willReturn();

        $routeMap = $this->prophesize('WellRESTed\Routing\RouteMapInterface');
        $routeMap->dispatch(Argument::cetera())->will(
            function ($args) use ($middleware) {
                $middleware->reveal()->dispatch($args[0], $args[1]);
            }
        );

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRequest", "getResponse", "getResponder", "getRouteMap"])
            ->disableOriginalConstructor()
            ->getMock();
        $router->expects($this->any())
            ->method("getRequest")
            ->will($this->returnValue($this->request->reveal()));
        $router->expects($this->any())
            ->method("getResponse")
            ->will($this->returnValue($this->response->reveal()));
        $router->expects($this->any())
            ->method("getResponder")
            ->will($this->returnValue($responder->reveal()));
        $router->expects($this->any())
            ->method("getRouteMap")
            ->will($this->returnValue($routeMap->reveal()));
        $router->__construct();
        $router->respond();

        $middleware->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::respond
     * @covers ::getRequest
     * @covers ::getResponse
     * @covers ::getResponder
     */
    public function testSendsResponseToResponder()
    {
        $middleware = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $middleware->dispatch(Argument::cetera())->willReturn();

        $responder = $this->prophesize('WellRESTed\Routing\ResponderInterface');
        $responder->respond(Argument::any())->willReturn();

        $routeMap = $this->prophesize('WellRESTed\Routing\RouteMapInterface');
        $routeMap->dispatch(Argument::cetera())->will(
            function ($args) use ($middleware) {
                $middleware->reveal()->dispatch($args[0], $args[1]);
            }
        );

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRequest", "getResponse", "getResponder", "getRouteMap"])
            ->disableOriginalConstructor()
            ->getMock();
        $router->expects($this->any())
            ->method("getRequest")
            ->will($this->returnValue($this->request->reveal()));
        $router->expects($this->any())
            ->method("getResponse")
            ->will($this->returnValue($this->response->reveal()));
        $router->expects($this->any())
            ->method("getResponder")
            ->will($this->returnValue($responder->reveal()));
        $router->expects($this->any())
            ->method("getRouteMap")
            ->will($this->returnValue($routeMap->reveal()));
        $router->__construct();
        $router->respond();

        $responder->respond($this->response->reveal())->shouldHaveBeenCalled();
    }
}
