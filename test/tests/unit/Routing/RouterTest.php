<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\HttpExceptions\NotFoundException;
use WellRESTed\Routing\Router;

// TODO Tests that ensure hooks are called at correct times

/**
 * @coversDefaultClass WellRESTed\Routing\Router
 * @uses WellRESTed\Routing\Router
 * @uses WellRESTed\Message\Stream
 * @uses WellRESTed\Routing\Dispatcher
 * @uses WellRESTed\Routing\RouteMap
 * @uses WellRESTed\Routing\Hook\ContentLengthHook
 * @uses WellRESTed\Routing\Hook\HeadHook
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
        $this->response->hasHeader("Content-length")->willReturn(true);
        $this->response->getStatusCode()->willReturn(200);
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
    // Routes

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
    public function testDispatchesPreRouteHooks()
    {
        $hook = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $hook->dispatch(Argument::cetera())->willReturn();

        $router = new Router();
        $router->addPreRouteHook($hook->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $hook->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

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

    /**
     * @covers ::addPostRouteHook
     * @covers ::dispatchPostRouteHooks
     */
    public function testDispatchesPostRouteHooks()
    {
        $hook = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $hook->dispatch(Argument::cetera())->willReturn();

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
    public function testDispatchesFinalizationHooks()
    {
        $hook = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $hook->dispatch(Argument::cetera())->willReturn();

        $router = new Router();
        $router->addFinalizationHook($hook->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $hook->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    /**
     * @coversNothing
     */
    public function testDispatchesMiddlewareInCorrectSequence()
    {
        // Each middleware will push a value onto this array.
        $stack = [];

        $routeMap = $this->prophesize('WellRESTed\Routing\RouteMapInterface');

        $response = $this->response;
        $routeMap->dispatch(Argument::cetera())->will(function () use ($response, &$stack) {
            $stack[] = "routeMap";
            $response->getStatusCode()->willReturn(404);
        });

        $router = $this->getMockBuilder('WellRESTed\Routing\Router')
            ->setMethods(["getRouteMap"])
            ->disableOriginalConstructor()
            ->getMock();
        $router->expects($this->any())
            ->method("getRouteMap")
            ->will($this->returnValue($routeMap->reveal()));
        $router->__construct();

        $router->addPreRouteHook($this->createStackHook("pre1", $stack));
        $router->addPreRouteHook($this->createStackHook("pre2", $stack));
        $router->addPostRouteHook($this->createStackHook("post1", $stack));
        $router->addPostRouteHook($this->createStackHook("post2", $stack));
        $router->addFinalizationHook($this->createStackHook("final1", $stack));
        $router->addFinalizationHook($this->createStackHook("final2", $stack));
        $router->setStatusHook(404, $this->createStackHook("404", $stack));

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $this->assertEquals(["pre1","pre2","routeMap","404","post1","post2","final1","final2"], $stack);
    }

    private function createStackHook($name, &$stack)
    {
        $hook = $this->prophesize('\WellRESTed\Routing\MiddlewareInterface');
        $hook->dispatch(Argument::cetera())->will(function () use ($name, &$stack) {
            $stack[] = $name;
        });
        return $hook->reveal();
    }

    /**
     * @coversNothing
     */
    public function testProvidesContentLengthHeader()
    {
        $this->request->getMethod()->willReturn("HEAD");
        $body = $this->prophesize('Psr\Http\Message\StreamInterface');
        $body->getSize()->willReturn(1024);
        $this->response->getBody()->willReturn($body->reveal());
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");
        $this->response->withHeader(Argument::cetera())->will(
            function () {
                $this->hasHeader("Content-length")->willReturn(true);
                return $this;
            }
        );
        $this->response->withBody(Argument::any())->willReturn($this->response->reveal());
        $router = new Router();

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $this->response->withHeader(Argument::cetera())->shouldHaveBeenCalled();
    }

    /**
     * @coversNothing
     */
    public function testRemovesBodyForHeadRequest()
    {
        $this->request->getMethod()->willReturn("HEAD");
        $body = $this->prophesize('Psr\Http\Message\StreamInterface');
        $body->getSize()->willReturn(1024);
        $this->response->getBody()->willReturn($body->reveal());
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");
        $this->response->withHeader(Argument::cetera())->will(
            function () {
                $this->hasHeader("Content-length")->willReturn(true);
                return $this;
            }
        );
        $this->response->withBody(Argument::any())->willReturn($this->response->reveal());
        $router = new Router();

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $router->dispatch($request, $response);

        $this->response->withBody(Argument::that(function ($body) {
            return $body->getSize() === 0;
        }))->shouldHaveBeenCalled();
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
