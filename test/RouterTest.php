<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Exceptions\HttpExceptions\HttpException;
use pjdietz\WellRESTed\Router;
use pjdietz\WellRESTed\Routes\TemplateRoute;
use Prophecy\Argument;

/**
 * @covers pjdietz\WellRESTed\Router
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    private $handler;
    private $request;
    private $response;
    private $route;

    public function setUp()
    {
        $this->request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $this->request->getPath()->willReturn("/");
        $this->request->getMethod()->willReturn("GET");
        $this->response = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\ResponseInterface");
        $this->route = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $this->handler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
    }

    public function testAddsSingleRoute()
    {
        $this->request->getPath()->willReturn("/cats/");
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $router = new Router();
        $router->add("/cats/", $this->handler->reveal());
        $response = $router->getResponse($this->request->reveal());
        $this->assertNotNull($response);
    }

    /**
     * @dataProvider pathProvider
     */
    public function testAddsMultpleRoutes($path, $exptectedSuccess)
    {
        $this->request->getPath()->willReturn($path);
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $router = new Router();
        $router->add(
            ["/cats/", $this->handler->reveal()],
            ["/cats/*", $this->handler->reveal()],
            ["/dogs/{name}", $this->handler->reveal(), TemplateRoute::RE_ALPHA],
            ["~/hamsters/[a-z]+~", $this->handler->reveal()]
        );
        $response = $router->getResponse($this->request->reveal());
        $this->assertEquals($exptectedSuccess, !is_null($response));
    }

    public function pathProvider()
    {
        return [
            ["/cats/", true],
            ["/cats/molly", true],
            ["/dogs/bear", true],
            ["/hamsters/fizzgig", true],
            ["/dogs/", false],
            ["/birds/", false],
            ["/hamsters/23", false]
        ];
    }

    public function testAddsSingleRouteInstance()
    {
        $router = new Router();
        $router->addRoute($this->route->reveal());
        $router->getResponse($this->request->reveal());
        $this->route->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testAddsMultipleRouteInstances()
    {
        $router = new Router();
        $router->addRoutes([$this->route->reveal()]);
        $router->getResponse($this->request->reveal());
        $this->route->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testRespondsWithErrorResponseForHttpException()
    {
        $this->route->getResponse(Argument::cetera())->willThrow(new HttpException());

        $router = new Router();
        $router->addRoute($this->route->reveal());
        $response = $router->getResponse($this->request->reveal());
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testDispatchesErrorHandlerForStatusCode()
    {
        $this->response->getStatusCode()->willReturn(403);
        $this->route->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $errorHandler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $errorHandler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $router = new Router();
        $router->addRoute($this->route->reveal());
        $router->setErrorHandlers([403 => $errorHandler->reveal()]);
        $router->getResponse($this->request->reveal());

        $errorHandler->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testDispatchesErrorHandlerWithOriginalRequest()
    {
        $this->request->getPath()->willReturn("/");
        $this->response->getStatusCode()->willReturn(403);
        $this->route->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $errorHandler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $errorHandler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $request = $this->request->reveal();

        $router = new Router();
        $router->addRoute($this->route->reveal());
        $router->setErrorHandlers([403 => $errorHandler->reveal()]);
        $router->getResponse($request);

        $errorHandler->getResponse(
            Argument::that(
                function ($arg) use ($request) {
                    return $arg === $request;
                }
            ),
            Argument::any()
        )->shouldHaveBeenCalled();
    }

    public function testDispatchesErrorHandlerWithOriginalArguments()
    {
        $this->request->getPath()->willReturn("/");
        $this->response->getStatusCode()->willReturn(403);
        $response = $this->response->reveal();
        $this->route->getResponse(Argument::cetera())->willReturn($response);

        $errorHandler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $errorHandler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $arguments = [
            "cat" => "Molly",
            "dog" => "Bear"
        ];

        $router = new Router();
        $router->addRoute($this->route->reveal());
        $router->setErrorHandlers([403 => $errorHandler->reveal()]);
        $router->getResponse($this->request->reveal(), $arguments);

        $errorHandler->getResponse(
            Argument::any(),
            Argument::that(
                function ($args) use ($arguments) {
                    return count(array_diff_assoc($arguments, $args)) === 0;
                }
            )
        )->shouldHaveBeenCalled();
    }

    public function testDispatchesErrorHandlerWithPreviousResponse()
    {
        $this->request->getPath()->willReturn("/");
        $this->response->getStatusCode()->willReturn(403);
        $response = $this->response->reveal();
        $this->route->getResponse(Argument::cetera())->willReturn($response);

        $errorHandler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $errorHandler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $router = new Router();
        $router->addRoute($this->route->reveal());
        $router->setErrorHandlers([403 => $errorHandler->reveal()]);
        $router->getResponse($this->request->reveal());

        $errorHandler->getResponse(
            Argument::any(),
            Argument::that(
                function ($arg) use ($response) {
                    return isset($arg["response"]) && $arg["response"] === $response;
                }
            )
        )->shouldHaveBeenCalled();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRoutesServerRequest()
    {
        $_SERVER["REQUEST_URI"] = "/cats/";
        $_SERVER["HTTP_HOST"] = "localhost";
        $_SERVER["REQUEST_METHOD"] = "GET";

        $this->response->getStatusCode()->willReturn(200);
        $this->response->respond()->willReturn();

        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $this->route->willImplement("\\pjdietz\\WellRESTed\\Interfaces\\Routes\\StaticRouteInterface");
        $this->route->getPaths()->willReturn(["/cats/"]);
        $this->route->getHandler()->willReturn($this->handler->reveal());

        $router = new Router();
        $router->addRoute($this->route->reveal());

        ob_start();
        $router->respond();
        ob_end_clean();

        $this->response->respond()->shouldHaveBeenCalled();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRoutesStaticRequestToNoRouteResponse()
    {
        $_SERVER["REQUEST_URI"] = "/cats/";
        $_SERVER["HTTP_HOST"] = "localhost";
        $_SERVER["REQUEST_METHOD"] = "GET";

        $router = new Router();

        ob_start();
        $router->respond();
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("No resource at /cats/", $captured);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRoutesStaticRequestTo404ErrorHandler()
    {
        $_SERVER["REQUEST_URI"] = "/cats/";
        $_SERVER["HTTP_HOST"] = "localhost";
        $_SERVER["REQUEST_METHOD"] = "GET";

        $errorHandler = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface");
        $errorHandler->getResponse(Argument::cetera())->willReturn($this->response->reveal());

        $router = new Router();
        $router->setErrorHandler(404, $errorHandler->reveal());

        ob_start();
        $router->respond();
        ob_end_clean();

        $errorHandler->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testDeprecatedSetStaticRoute()
    {
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());
        $this->request->getPath()->willReturn("/cats/");

        $router = new Router();
        @$router->setStaticRoute(["/cats/"], $this->handler->reveal());
        $router->getResponse($this->request->reveal());

        $this->handler->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }

    public function testDeprecatedSetPrefixRoute()
    {
        $this->handler->getResponse(Argument::cetera())->willReturn($this->response->reveal());
        $this->request->getPath()->willReturn("/cats/molly");

        $router = new Router();
        @$router->setPrefixRoute(["/cats/"], $this->handler->reveal());
        $router->getResponse($this->request->reveal());

        $this->handler->getResponse(Argument::cetera())->shouldHaveBeenCalled();
    }
}
