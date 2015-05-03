<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\HttpExceptions\NotFoundException;
use WellRESTed\Routing\Router;

/**
 * @coversDefaultClass WellRESTed\Routing\Router
 * @uses WellRESTed\Routing\Router
 * @uses WellRESTed\Message\Stream
 * @uses WellRESTed\Routing\Dispatcher
 * @uses WellRESTed\Routing\MethodMap
 * @uses WellRESTed\Routing\ResponsePrep\ContentLengthPrep
 * @uses WellRESTed\Routing\ResponsePrep\HeadPrep
 * @uses WellRESTed\Routing\Route\PrefixRoute
 * @uses WellRESTed\Routing\Route\RegexRoute
 * @uses WellRESTed\Routing\Route\Route
 * @uses WellRESTed\Routing\Route\RouteFactory
 * @uses WellRESTed\Routing\Route\StaticRoute
 * @uses WellRESTed\Routing\Route\TemplateRoute
 * @uses WellRESTed\Routing\RouteTable
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    private $dispatcher;
    private $middleware;
    private $request;
    private $responder;
    private $response;

    public function setUp()
    {
        $this->dispatcher = $this->prophesize('WellRESTed\Routing\DispatcherInterface');
        $this->dispatcher->dispatch(Argument::any())->willReturn();
        $this->middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');
        $this->middleware->dispatch(Argument::cetera())->willReturn();
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->request->getRequestTarget()->willReturn("/");
        $this->request->getMethod()->willReturn("GET");
        $this->responder = $this->prophesize('WellRESTed\Routing\ResponderInterface');
        $this->responder->respond(Argument::any())->willReturn();
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->response->withBody(Argument::any())->willReturn($this->response->reveal());
        $this->response->hasHeader("Content-length")->willReturn(true);
        $this->response->getStatusCode()->willReturn(200);
    }

    // ------------------------------------------------------------------------
    // Construction

    /**
     * @covers ::__construct
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
    public function testAddWithSimpleRouteRegistersRoute()
    {
        $factory = $this->prophesize('WellRESTed\Routing\Route\RouteFactoryInterface');
        $factory->registerRoute(Argument::cetera())->willReturn();

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRouteFactory"])
            ->disableOriginalConstructor()
            ->getMock();
        $router->expects($this->any())
            ->method("getRouteFactory")
            ->will($this->returnValue($factory->reveal()));
        $router->__construct();

        $target = "/cats/";
        $middleware = $this->middleware->reveal();
        $router->add($target, $middleware);

        $factory->registerRoute(Argument::any(), $target, $middleware, Argument::any())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::add
     */
    public function testAddWithMapAddsMiddlewareToMethodMap()
    {
        $map = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $map->addMap(Argument::any())->willReturn();

        $factory = $this->prophesize('WellRESTed\Routing\Route\RouteFactoryInterface');
        $factory->registerRoute(Argument::cetera())->willReturn();

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRouteFactory", "getMethodMap"])
            ->disableOriginalConstructor()
            ->getMock();
        $router->expects($this->any())
            ->method("getMethodMap")
            ->will($this->returnValue($map->reveal()));
        $router->expects($this->any())
            ->method("getRouteFactory")
            ->will($this->returnValue($factory->reveal()));
        $router->__construct();

        $target = "/cats/";
        $middleware = ["GET" => $this->middleware->reveal()];
        $router->add($target, $middleware);

        $map->addMap($middleware)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::add
     */
    public function testAddWithMapRegistersMethodMap()
    {
        $map = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
        $map->addMap(Argument::any())->willReturn();

        $factory = $this->prophesize('WellRESTed\Routing\Route\RouteFactoryInterface');
        $factory->registerRoute(Argument::cetera())->willReturn();

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRouteFactory", "getMethodMap"])
            ->disableOriginalConstructor()
            ->getMock();
        $router->expects($this->any())
            ->method("getMethodMap")
            ->will($this->returnValue($map->reveal()));
        $router->expects($this->any())
            ->method("getRouteFactory")
            ->will($this->returnValue($factory->reveal()));
        $router->__construct();

        $target = "/cats/";
        $middleware = ["GET" => $this->middleware->reveal()];
        $router->add($target, $middleware);

        $factory->registerRoute(Argument::any(), $target, $map, Argument::any())->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Dispatching Routes

    /**
     * @covers ::dispatch
     */
    public function testDispatchesMatchedRoute()
    {
        $this->request->getRequestTarget()->willReturn("/cats/");

        $router = new Router();
        $router->add("/cats/", $this->middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $this->middleware->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Hooks

    /**
     * @covers ::addPreRouteHook
     * @covers ::disptachPreRouteHooks
     */
    public function testDispatchesPreRouteHooks()
    {
        $hook = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $hook->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $router = new Router();
        $router->addPreRouteHook($hook->reveal());
        $router->add("/cats/", $this->middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $hook->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::addPostRouteHook
     * @covers ::disptachPostRouteHooks
     */
    public function testDispatchesPostRouteHooks()
    {
        $hook = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $hook->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $router = new Router();
        $router->addPostRouteHook($hook->reveal());
        $router->add("/cats/", $this->middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $hook->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::addResponsePreparationHook
     * @covers ::dispatchResponsePreparationHooks
     */
    public function testDispatchesResponsePreparationHooks()
    {
        $hook = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $hook->dispatch(Argument::cetera())->willReturn();

        $this->request->getRequestTarget()->willReturn("/cats/");

        $router = new Router();
        $router->addResponsePreparationHook($hook->reveal());
        $router->add("/cats/", $this->middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $hook->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Status Handlers

    /**
     * @covers ::dispatch
     * @covers ::setStatusHandler
     */
    public function testDispatchesHandlerForStatusCode()
    {
        $this->response->getStatusCode()->willReturn(403);

        $statusMiddleware = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $statusMiddleware->dispatch(Argument::cetera())->willReturn();

        $router = new Router();
        $router->add("/cats/", $this->middleware->reveal());
        $router->setStatusHandler(403, $statusMiddleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $statusMiddleware->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::setStatusHandler
     */
    public function testDispatchesHandlerForStatusCodeForHttpException()
    {
        $this->request->getRequestTarget()->willReturn("/cats/");
        $this->middleware->dispatch(Argument::cetera())->willThrow(new NotFoundException());

        $router = new Router();
        $router->add("/cats/", $this->middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $this->response->withStatus(404)->shouldHaveBeenCalled();
    }

    // ------------------------------------------------------------------------
    // Respond

    /**
     * @covers ::respond
     */
    public function testRespondDispatchesRequest()
    {
        $target = "/cats/";

        $this->request->getRequestTarget()->willReturn($target);
        $this->responder->respond(Argument::any())->willReturn();

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRequest", "getResponse", "getResponder"])
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
            ->will($this->returnValue($this->responder->reveal()));
        $router->__construct();
        $router->add($target, $this->middleware->reveal());
        $router->respond();

        $this->middleware->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    /**
     * @covers ::respond
     */
    public function testSendsResponseToResponder()
    {
        $target = "/cats/";

        $this->request->getRequestTarget()->willReturn($target);

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRequest", "getResponse", "getResponder"])
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
            ->will($this->returnValue($this->responder->reveal()));
        $router->__construct();
        $router->add($target, $this->middleware->reveal());
        $router->respond();

        $this->responder->respond($this->response->reveal())->shouldHaveBeenCalled();
    }
}
