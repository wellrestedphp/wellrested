<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Routing\MethodMap;

/**
 * @covers WellRESTed\Routing\MethodMap
 * @group routing
 */
class MethodMapTest extends \PHPUnit_Framework_TestCase
{
    private $dispatcher;
    private $request;
    private $response;
    private $next;
    private $middleware;

    public function setUp()
    {
        $this->request = new ServerRequest();
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->response->withStatus(Argument::any())->willReturn($this->response->reveal());
        $this->response->withHeader(Argument::cetera())->willReturn($this->response->reveal());
        $this->next = function ($request, $response) {
            return $response;
        };
        $this->middleware = $this->prophesize('WellRESTed\MiddlewareInterface');
        $this->middleware->__invoke(Argument::cetera())->willReturn();
        $this->dispatcher = $this->prophesize('WellRESTed\Dispatching\DispatcherInterface');
        $this->dispatcher->dispatch(Argument::cetera())->will(
            function ($args) {
                list($middleware, $request, $response, $next) = $args;
                return $middleware($request, $response, $next);
            }
        );
    }

    public function testCreatesInstance()
    {
        $methodMap = new MethodMap($this->dispatcher->reveal());
        $this->assertNotNull($methodMap);
    }

    public function testDispatchesMiddlewareWithMatchingMethod()
    {
        $this->request = $this->request->withMethod("GET");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $map($this->request, $this->response->reveal(), $this->next);

        $this->middleware->__invoke(
            $this->request,
            $this->response->reveal(),
            $this->next
        )->shouldHaveBeenCalled();
    }

    public function testTreatsMethodNamesCaseSensitively()
    {
        $this->request = $this->request->withMethod("get");

        $middlewareUpper = $this->prophesize('WellRESTed\MiddlewareInterface');
        $middlewareUpper->__invoke(Argument::cetera())->willReturn();

        $middlewareLower = $this->prophesize('WellRESTed\MiddlewareInterface');
        $middlewareLower->__invoke(Argument::cetera())->willReturn();

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $middlewareUpper->reveal());
        $map->register("get", $middlewareLower->reveal());
        $map($this->request, $this->response->reveal(), $this->next);

        $middlewareLower->__invoke(
            $this->request,
            $this->response->reveal(),
            $this->next
        )->shouldHaveBeenCalled();
    }

    public function testDispatchesWildcardMiddlewareWithNonMatchingMethod()
    {
        $this->request = $this->request->withMethod("GET");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("*", $this->middleware->reveal());
        $map($this->request, $this->response->reveal(), $this->next);

        $this->middleware->__invoke(
            $this->request,
            $this->response->reveal(),
            $this->next
        )->shouldHaveBeenCalled();
    }

    public function testDispatchesGetMiddlewareForHeadByDefault()
    {
        $this->request = $this->request->withMethod("HEAD");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $map($this->request, $this->response->reveal(), $this->next);

        $this->middleware->__invoke(
            $this->request,
            $this->response->reveal(),
            $this->next
        )->shouldHaveBeenCalled();
    }

    public function testRegistersMiddlewareForMultipleMethods()
    {
        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET,POST", $this->middleware->reveal());

        $this->request = $this->request->withMethod("GET");
        $map($this->request, $this->response->reveal(), $this->next);

        $this->request = $this->request->withMethod("POST");
        $map($this->request, $this->response->reveal(), $this->next);

        $this->middleware->__invoke(Argument::cetera())->shouldHaveBeenCalledTimes(2);
    }

    public function testSettingNullUnregistersMiddleware()
    {
        $this->request = $this->request->withMethod("POST");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("POST", $this->middleware->reveal());
        $map->register("POST", null);
        $map($this->request, $this->response->reveal(), $this->next);

        $this->response->withStatus(405)->shouldHaveBeenCalled();
    }

    public function testSetsStatusTo200ForOptions()
    {
        $this->request = $this->request->withMethod("OPTIONS");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $map($this->request, $this->response->reveal(), $this->next);

        $this->response->withStatus(200)->shouldHaveBeenCalled();
    }

    public function testStopsPropagatingAfterOptions()
    {
        $calledNext = false;
        $next = function ($request, $response) use (&$calledNext) {
            $calledNext = true;
            return $response;
        };

        $this->request = $this->request->withMethod("OPTIONS");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $map($this->request, $this->response->reveal(), $next);

        $this->assertFalse($calledNext);
    }

    /** @dataProvider allowedMethodProvider */
    public function testSetsAllowHeaderForOptions($methodsDeclared, $methodsAllowed)
    {
        $this->request = $this->request->withMethod("OPTIONS");

        $map = new MethodMap($this->dispatcher->reveal());
        foreach ($methodsDeclared as $method) {
            $map->register($method, $this->middleware->reveal());
        }
        $map($this->request, $this->response->reveal(), $this->next);

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

    /** @dataProvider allowedMethodProvider */
    public function testSetsStatusTo405ForBadMethod()
    {
        $this->request = $this->request->withMethod("POST");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $map($this->request, $this->response->reveal(), $this->next);

        $this->response->withStatus(405)->shouldHaveBeenCalled();
    }

    /**
     * @coversNothing
     * @dataProvider allowedMethodProvider
     */
    public function testStopsPropagatingAfterBadMethod()
    {
        $calledNext = false;
        $next = function ($request, $response) use (&$calledNext) {
            $calledNext = true;
            return $response;
        };
        $this->request = $this->request->withMethod("POST");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $map($this->request, $this->response->reveal(), $next);
        $this->assertFalse($calledNext);
    }

    /** @dataProvider allowedMethodProvider */
    public function testSetsAllowHeaderForBadMethod($methodsDeclared, $methodsAllowed)
    {
        $this->request = $this->request->withMethod("BAD");

        $map = new MethodMap($this->dispatcher->reveal());
        foreach ($methodsDeclared as $method) {
            $map->register($method, $this->middleware->reveal());
        }
        $map($this->request, $this->response->reveal(), $this->next);

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
            [["GET", "POST"], ["GET", "POST", "HEAD", "OPTIONS"]],
            [["POST"], ["POST", "OPTIONS"]],
            [["POST"], ["POST", "OPTIONS"]],
            [["GET", "PUT,DELETE"], ["GET", "PUT", "DELETE", "HEAD", "OPTIONS"]],
        ];
    }
}
