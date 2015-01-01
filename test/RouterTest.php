<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Exceptions\HttpExceptions\ForbiddenException;
use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Response;
use pjdietz\WellRESTed\Router;
use pjdietz\WellRESTed\Routes\StaticRoute;
use pjdietz\WellRESTed\Routes\TemplateRoute;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testAddRoute()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $route = new StaticRoute($path, __NAMESPACE__ . '\\RouterTestHandler');
        $router = new Router();
        $router->addRoute($route);
        $resp = $router->getResponse($mockRequest);
        $this->assertNotNull($resp);
    }

    public function testAddRoutes()
    {
        $path = "/";

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $routes = array();
        $routes[] = new StaticRoute("/", __NAMESPACE__ . '\\RouterTestHandler');
        $routes[] = new StaticRoute("/another/", __NAMESPACE__ . '\\RouterTestHandler');

        $router = new Router();
        $router->addRoutes($routes);
        $resp = $router->getResponse($mockRequest);
        $this->assertEquals(200, $resp->getStatusCode());
    }

    public function testRespondWithDefaultErrorForException()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/"));

        $router = new Router();
        $router->addRoute(new StaticRoute("/", __NAMESPACE__ . '\\ForbiddenExceptionHandler'));
        $resp = $router->getResponse($mockRequest);
        $this->assertEquals(403, $resp->getStatusCode());
    }

    public function testRespondWithErrorHandlerForException()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/"));

        $router = new Router();
        $router->addRoute(new StaticRoute("/", __NAMESPACE__ . '\\ForbiddenExceptionHandler'));
        $router->setErrorHandler(403, __NAMESPACE__ . '\\ForbiddenErrorHandler');
        $resp = $router->getResponse($mockRequest);
        $this->assertEquals("YOU SHALL NOT PASS!", $resp->getBody());
    }

    public function testRespondWithErrorHandlerForStatusCode()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/"));

        $router = new Router();
        $router->addRoute(new StaticRoute("/", __NAMESPACE__ . '\\ForbiddenHandler'));
        $router->setErrorHandler(403, __NAMESPACE__ . '\\ForbiddenErrorHandler');
        $resp = $router->getResponse($mockRequest);
        $this->assertEquals("YOU SHALL NOT PASS!", $resp->getBody());
    }

    public function testRespondWithErrorHandlerUsingOriginalResponse()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/"));

        $router = new Router();
        $router->addRoute(new StaticRoute("/", __NAMESPACE__ . '\\MessageHandler'));
        $router->setErrorHandlers([404 => __NAMESPACE__ . '\\MessageErrorHandler']);
        $resp = $router->getResponse($mockRequest);
        $this->assertEquals("<h1>Not Found</h1>", $resp->getBody());
    }

    public function testRespondWithErrorHandlerUsingInjection()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/"));

        $router = new Router();
        $router->addRoute(new StaticRoute("/", __NAMESPACE__ . '\\ForbiddenHandler'));
        $router->setErrorHandlers([403 => __NAMESPACE__ . '\\InjectionErrorHandler']);
        $resp = $router->getResponse($mockRequest, ["message" => "Pass through"]);
        $this->assertEquals("Pass through", $resp->getBody());
    }

    public function testReturnNullWhenNoRouteMatches()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/dog/"));

        $route = new StaticRoute("/cat/", __NAMESPACE__ . '\\RouterTestHandler');
        $router = new Router();
        $router->addRoute($route);
        $resp = $router->getResponse($mockRequest);
        $this->assertNull($resp);
    }

    public function testNestedRouters()
    {
        $path = "/cats/";

        $router1 = new Router();
        $router2 = new Router();
        $router3 = new Router();

        $router1->addRoute($router2);
        $router2->addRoute($router3);
        $router3->addRoute(new StaticRoute($path, __NAMESPACE__ . '\\RouterTestHandler'));

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $resp = $router1->getResponse($mockRequest);
        $this->assertNotNull($resp);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testStaticRequestDoesNotMatchRouter()
    {
        $_SERVER["REQUEST_URI"] = "/cats/";
        $_SERVER["HTTP_HOST"] = "localhost";
        $_SERVER["REQUEST_METHOD"] = "GET";

        $route = new StaticRoute("/dogs/", __NAMESPACE__ . '\\RouterTestHandler');
        $router = new Router();
        $router->addRoute($route);
        ob_start();
        $router->respond();
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("No resource at /cats/", $captured);
    }

    /**
     * @dataProvider nestedRouterRoutesProvider
     */
    public function testNestedRouterFromWithRoutes($path, $expectedBody)
    {
        $router = new Router();
        $router->addRoutes(array(
            new TemplateRoute("/cats/*", __NAMESPACE__ . "\\CatRouter"),
            new TemplateRoute("/dogs/*", __NAMESPACE__ . "\\DogRouter"),
            new NotFoundHandler()
        ));

        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue($path));

        $resp = $router->getResponse($mockRequest);
        $this->assertEquals($expectedBody, $resp->getBody());
    }

    public function nestedRouterRoutesProvider()
    {
        return [
            ["/cats/", "/cats/"],
            ["/cats/molly", "/cats/molly"],
            ["/dogs/", "/dogs/"],
            ["/birds/", "No resource found at /birds/"]
        ];
    }

    public function testInjection()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue("/2/3"));

        $dependencies = [
            "add" => function ($a, $b) {
                    return $a + $b;
                }
        ];

        $router = new Router();
        $router->addRoute(new TemplateRoute("/{a}/{b}", __NAMESPACE__ . "\\InjectionHandler"));
        $resp = $router->getResponse($mockRequest, $dependencies);
        $this->assertEquals("5", $resp->getBody());
    }

}

/**
 * Mini Handler class that allways returns a 200 status code Response.
 */
class RouterTestHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $resp = new Response();
        $resp->setStatusCode(200);
        $resp->setBody($request->getPath());
        return $resp;
    }
}

class CatRouter extends Router
{
    public function __construct()
    {
        parent::__construct();
        $this->addRoutes([
            new StaticRoute("/cats/", __NAMESPACE__ . "\\RouterTestHandler"),
            new StaticRoute("/cats/molly", __NAMESPACE__ . "\\RouterTestHandler"),
            new StaticRoute("/cats/oscar", __NAMESPACE__ . "\\RouterTestHandler")
        ]);
    }
}

class DogRouter extends Router
{
    public function __construct()
    {
        parent::__construct();
        $this->addRoutes([
            new StaticRoute("/dogs/", __NAMESPACE__ . "\\RouterTestHandler"),
            new StaticRoute("/dogs/bear", __NAMESPACE__ . "\\RouterTestHandler")
        ]);
    }
}

class NotFoundHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = new Response(404);
        $response->setBody("No resource found at " . $request->getPath());
        return $response;
    }
}

class ForbiddenHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = new Response(403);
        $response->setBody("Forbidden");
        return $response;
    }
}

class ForbiddenExceptionHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        throw new ForbiddenException();
    }
}

class ForbiddenErrorHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = new Response(403);
        $response->setBody("YOU SHALL NOT PASS!");
        return $response;
    }
}

class MessageHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = new Response(404);
        $response->setBody("Not Found");
        return $response;
    }
}

class MessageErrorHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        if (isset($args["response"])) {
            /** @var ResponseInterface $response */
            $response = $args["response"];
            $message = "<h1>" . $response->getBody() . "</h1>";
            $response->setBody($message);
            return $response;
        }
        return null;
    }
}

class InjectionErrorHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = new Response(403);
        $response->setBody($args["message"]);
        return $response;
    }
}


class InjectionHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = new Response(200);
        $body = $args["add"]($args["a"], $args["b"]);
        $response->setBody($body);
        return $response;
    }
}
