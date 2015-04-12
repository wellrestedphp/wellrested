<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\HttpExceptions\NotFoundException;
use WellRESTed\Routing\Router;

/**
 * @covers WellRESTed\Routing\Router
 * @uses WellRESTed\Routing\Dispatcher
 * @uses WellRESTed\Routing\MethodMap
 * @uses WellRESTed\Routing\RouteTable
 * @uses WellRESTed\Routing\Route\RouteFactory
 * @uses WellRESTed\Routing\Route\RegexRoute
 * @uses WellRESTed\Routing\Route\TemplateRoute
 * @uses WellRESTed\Routing\Route\PrefixRoute
 * @uses WellRESTed\Routing\Route\StaticRoute
 * @uses WellRESTed\Routing\Route\Route
 * @uses WellRESTed\Stream\Stream
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    private $middleware;
    private $request;
    private $responder;
    private $response;

    public function setUp()
    {
        $this->request = $this->prophesize("\\Psr\\Http\\Message\\ServerRequestInterface");
        $this->response = $this->prophesize("\\Psr\\Http\\Message\\ResponseInterface");
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->response->withBody(Argument::any())->willReturn($this->response->reveal());
        $this->response->getStatusCode()->willReturn(200);
        $this->middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");
        $this->middleware->dispatch(Argument::cetera())->willReturn();
        $this->responder = $this->prophesize("\\WellRESTed\\Routing\\ResponderInterface");
        $this->responder->respond(Argument::any())->willReturn();
    }

    public function testDispatchesRoute()
    {
        $this->request->getRequestTarget()->willReturn("/cats/");

        $router = new Router();
        $router->add("/cats/", $this->middleware->reveal());
        $router->dispatch($this->request->reveal(), $this->response->reveal());

        $this->middleware->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testRespondsWithErrorResponseForHttpException()
    {
        $this->request->getRequestTarget()->willReturn("/cats/");
        $this->middleware->dispatch(Argument::cetera())->willThrow(new NotFoundException());

        $router = new Router();
        $router->add("/cats/", $this->middleware->reveal());
        $router->dispatch($this->request->reveal(), $this->response->reveal());

        $this->response->withStatus(404)->shouldHaveBeenCalled();
    }

    public function testDispatchesErrorHandlerForStatusCode()
    {
        $this->response->getStatusCode()->willReturn(403);

        $statusMiddleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");
        $statusMiddleware->dispatch(Argument::cetera())->willReturn();

        $router = new Router();
        $router->add("/cats/", $this->middleware->reveal());
        $router->setStatusHandler(403, $statusMiddleware->reveal());
        $router->dispatch($this->request->reveal(), $this->response->reveal());

        $statusMiddleware->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testRegisterRouteWithMethodMap()
    {
        $router = new SettableRouter();
        $methodMap = $this->prophesize('\WellRESTed\Routing\MethodMapInterface');
        $router->methodMap = $methodMap->reveal();

        $router->add("/cats/", ["GET" => $this->middleware->reveal()]);
        $methodMap->addMap(["GET" => $this->middleware->reveal()])->shouldHaveBeenCalled();
    }

    public function testRespondDispatchesRequest()
    {
        $this->request->getRequestTarget()->willReturn("/cats/");

        $router = new SettableRouter();
        $router->request = $this->request->reveal();
        $router->response = $this->response->reveal();
        $router->responder = $this->responder->reveal();
        $router->add("/cats/", $this->middleware->reveal());
        $router->respond();

        $this->middleware->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }
}

// ----------------------------------------------------------------------------

/**
 * Overrides the methods that return new instances to return public ivars for
 * easy testing.
 */
class SettableRouter extends Router
{
    public $methodMap;
    public $request;
    public $response;
    public $responder;

    public function getMethodMap()
    {
        return $this->methodMap ?: parent::getMethodMap();
    }

    public function getRequest()
    {
        return $this->request ?: parent::getRequest();
    }

    public function getResponse()
    {
        return $this->response ?: parent::getResponse();
    }

    public function getResponder()
    {
        return $this->responder ?: parent::getResponder();
    }
}

