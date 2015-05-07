<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Routing\MethodMap;

/**
 * @coversDefaultClass WellRESTed\Routing\MethodMap
 * @uses WellRESTed\Routing\MethodMap
 * @uses WellRESTed\Routing\Dispatcher
 */
class MethodMapTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;

    public function setUp()
    {
        $this->request = $this->prophesize("\\Psr\\Http\\Message\\ServerRequestInterface");
        $this->response = $this->prophesize("\\Psr\\Http\\Message\\ResponseInterface");
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->response->withHeader(Argument::cetera())->willReturn($this->response->reveal());
    }

    /**
     * @covers ::__construct
     */
    public function testCreatesInstance()
    {
        $methodMap = new MethodMap();
        $this->assertNotNull($methodMap);
    }

    /**
     * @covers ::dispatch
     * @covers ::dispatchMiddleware
     * @covers ::getDispatcher
     * @covers ::setMethod
     */
    public function testDispatchesMiddlewareWithMatchingMethod()
    {
        $this->request->getMethod()->willReturn("GET");

        $middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');
        $middleware->dispatch(Argument::cetera())->willReturn();

        $map = new MethodMap();
        $map->setMethod("GET", $middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $map->dispatch($request, $response);

        $middleware->dispatch($request, $response)->shouldHaveBeenCalled();
    }

    /**
     * @coversNothing
     */
    public function testTreatsMethodNamesCaseSensitively()
    {
        $this->request->getMethod()->willReturn("get");

        $middlewareUpper = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');
        $middlewareUpper->dispatch(Argument::cetera())->willReturn();

        $middlewareLower = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');
        $middlewareLower->dispatch(Argument::cetera())->willReturn();

        $map = new MethodMap();
        $map->setMethod("GET", $middlewareUpper->reveal());
        $map->setMethod("get", $middlewareLower->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $map->dispatch($request, $response);

        $middlewareLower->dispatch($request, $response)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::dispatchMiddleware
     * @covers ::getDispatcher
     * @covers ::setMethod
     */
    public function testDispatchesWildcardMiddlewareWithNonMatchingMethod()
    {
        $this->request->getMethod()->willReturn("GET");

        $middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');
        $middleware->dispatch(Argument::cetera())->willReturn();

        $map = new MethodMap();
        $map->setMethod("*", $middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $map->dispatch($request, $response);

        $middleware->dispatch($request, $response)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     */
    public function testDispatchesGetMiddlewareForHeadByDefault()
    {
        $this->request->getMethod()->willReturn("HEAD");

        $middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');
        $middleware->dispatch(Argument::cetera())->willReturn();

        $map = new MethodMap();
        $map->setMethod("GET", $middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $map->dispatch($request, $response);

        $middleware->dispatch($request, $response)->shouldHaveBeenCalled();
    }

    /*
     * @covers ::setMethod
     */
    public function testRegistersMiddlewareForMultipleMethods()
    {
        $middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');
        $middleware->dispatch(Argument::cetera())->willReturn();

        $map = new MethodMap();
        $map->setMethod("GET,POST", $middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();

        $this->request->getMethod()->willReturn("GET");
        $map->dispatch($request, $response);

        $this->request->getMethod()->willReturn("POST");
        $map->dispatch($request, $response);

        $middleware->dispatch($request, $response)->shouldHaveBeenCalledTimes(2);
    }

    public function testSettingNullUnregistersMiddleware()
    {
        $this->request->getMethod()->willReturn("POST");

        $middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');

        $map = new MethodMap();
        $map->setMethod("POST", $middleware->reveal());
        $map->setMethod("POST", null);

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $map->dispatch($request, $response);

        $this->response->withStatus(405)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::addAllowHeader
     * @covers ::getAllowedMethods
     */
    public function testSetsStatusTo200ForOptions()
    {
        $this->request->getMethod()->willReturn("OPTIONS");

        $middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');

        $map = new MethodMap();
        $map->setMethod("GET", $middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $map->dispatch($request, $response);

        $this->response->withStatus(200)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::addAllowHeader
     * @covers ::getAllowedMethods
     * @dataProvider allowedMethodProvider
     */
    public function testSetsAllowHeaderForOptions($methodsDeclared, $methodsAllowed)
    {
        $this->request->getMethod()->willReturn("OPTIONS");

        $middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');

        $map = new MethodMap();
        foreach ($methodsDeclared as $method) {
            $map->setMethod($method, $middleware->reveal());
        }

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $map->dispatch($request, $response);

        $containsAllMethods = function ($headerValue) use ($methodsAllowed) {
            foreach ($methodsAllowed as $method) {
                if (strpos($headerValue, $method) === false) {
                    return false;
                }
            }
            return true;
        };
        $this->response->withHeader("Allow", Argument::that($containsAllMethods))->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::addAllowHeader
     * @covers ::getAllowedMethods
     * @dataProvider allowedMethodProvider
     */
    public function testSetsStatusTo405ForBadMethod()
    {
        $this->request->getMethod()->willReturn("POST");

        $middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');

        $map = new MethodMap();
        $map->setMethod("GET", $middleware->reveal());

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $map->dispatch($request, $response);

        $this->response->withStatus(405)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::addAllowHeader
     * @covers ::getAllowedMethods
     * @dataProvider allowedMethodProvider
     */
    public function testSetsAllowHeaderForBadMethod($methodsDeclared, $methodsAllowed)
    {
        $this->request->getMethod()->willReturn("BAD");

        $middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');

        $map = new MethodMap();
        foreach ($methodsDeclared as $method) {
            $map->setMethod($method, $middleware->reveal());
        }

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $map->dispatch($request, $response);

        $containsAllMethods = function ($headerValue) use ($methodsAllowed) {
            foreach ($methodsAllowed as $method) {
                if (strpos($headerValue, $method) === false) {
                    return false;
                }
            }
            return true;
        };
        $this->response->withHeader("Allow", Argument::that($containsAllMethods))->shouldHaveBeenCalled();
    }

    public function allowedMethodProvider()
    {
        return [
            [["GET"], ["GET", "HEAD", "OPTIONS"]],
            [["GET","POST"], ["GET", "POST", "HEAD", "OPTIONS"]],
            [["POST"], ["POST", "OPTIONS"]],
            [["POST"], ["POST", "OPTIONS"]],
            [["GET","PUT,DELETE"], ["GET", "PUT", "DELETE", "HEAD", "OPTIONS"]],
        ];
    }
}
