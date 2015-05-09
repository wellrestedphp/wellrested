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
    private $next;
    private $middleware;

    public function setUp()
    {
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->response->withHeader(Argument::cetera())->willReturn($this->response->reveal());
        $this->next = function ($request, $response) {
            return $response;
        };
        $this->middleware = $this->prophesize('WellRESTed\Routing\MiddlewareInterface');
        $this->middleware->dispatch(Argument::cetera())->willReturn();
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
     * @covers ::register
     */
    public function testDispatchesMiddlewareWithMatchingMethod()
    {
        $this->request->getMethod()->willReturn("GET");

        $map = new MethodMap();
        $map->register("GET", $this->middleware->reveal());
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->middleware->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
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
        $map->register("GET", $middlewareUpper->reveal());
        $map->register("get", $middlewareLower->reveal());
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $middlewareLower->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     * @covers ::dispatchMiddleware
     * @covers ::getDispatcher
     * @covers ::register
     */
    public function testDispatchesWildcardMiddlewareWithNonMatchingMethod()
    {
        $this->request->getMethod()->willReturn("GET");

        $map = new MethodMap();
        $map->register("*", $this->middleware->reveal());
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->middleware->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::dispatch
     */
    public function testDispatchesGetMiddlewareForHeadByDefault()
    {
        $this->request->getMethod()->willReturn("HEAD");

        $map = new MethodMap();
        $map->register("GET", $this->middleware->reveal());
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->middleware->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalled();
    }

    /*
     * @covers ::register
     */
    public function testRegistersMiddlewareForMultipleMethods()
    {
        $map = new MethodMap();
        $map->register("GET,POST", $this->middleware->reveal());

        $this->request->getMethod()->willReturn("GET");
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->request->getMethod()->willReturn("POST");
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->middleware->dispatch($this->request->reveal(), $this->response->reveal(), $this->next)->shouldHaveBeenCalledTimes(2);
    }

    public function testSettingNullUnregistersMiddleware()
    {
        $this->request->getMethod()->willReturn("POST");

        $map = new MethodMap();
        $map->register("POST", $this->middleware->reveal());
        $map->register("POST", null);
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

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

        $map = new MethodMap();
        $map->register("GET", $this->middleware->reveal());
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

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

        $map = new MethodMap();
        foreach ($methodsDeclared as $method) {
            $map->register($method, $this->middleware->reveal());
        }
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

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

        $map = new MethodMap();
        $map->register("GET", $this->middleware->reveal());
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

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

        $map = new MethodMap();
        foreach ($methodsDeclared as $method) {
            $map->register($method, $this->middleware->reveal());
        }
        $map->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

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
