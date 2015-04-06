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
 * @uses WellRESTed\Stream\StringStream
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;
    private $middleware;

    public function setUp()
    {
        $this->request = $this->prophesize("\\Psr\\Http\\Message\\ServerRequestInterface");
        $this->response = $this->prophesize("\\Psr\\Http\\Message\\ResponseInterface");
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->response->withBody(Argument::any())->willReturn($this->response->reveal());
        $this->response->getStatusCode()->willReturn(200);
        $this->middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");
        $this->middleware->dispatch(Argument::cetera())->willReturn();
    }

    public function testDispatchedRoute()
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
        $this->request->getRequestTarget()->willReturn("/cats/");
        $this->request->getMethod()->willReturn("GET");

        $router = new Router();
        $router->add("/cats/", ["GET" => $this->middleware->reveal()]);
        $router->dispatch($this->request->reveal(), $this->response->reveal());

        $this->middleware->dispatch(Argument::cetera())->shouldHaveBeenCalled();
    }
}
