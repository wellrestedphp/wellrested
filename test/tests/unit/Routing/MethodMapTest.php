<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Routing\MethodMap;
use WellRESTed\Test\NextSpy;

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
        $this->response = new Response();
        $this->next = new NextSpy();
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
        $map($this->request, $this->response, $this->next);

        $this->middleware->__invoke(
            $this->request,
            $this->response,
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
        $map($this->request, $this->response, $this->next);

        $middlewareLower->__invoke(
            $this->request,
            $this->response,
            $this->next
        )->shouldHaveBeenCalled();
    }

    public function testDispatchesWildcardMiddlewareWithNonMatchingMethod()
    {
        $this->request = $this->request->withMethod("GET");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("*", $this->middleware->reveal());
        $map($this->request, $this->response, $this->next);

        $this->middleware->__invoke(
            $this->request,
            $this->response,
            $this->next
        )->shouldHaveBeenCalled();
    }

    public function testDispatchesGetMiddlewareForHeadByDefault()
    {
        $this->request = $this->request->withMethod("HEAD");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $map($this->request, $this->response, $this->next);

        $this->middleware->__invoke(
            $this->request,
            $this->response,
            $this->next
        )->shouldHaveBeenCalled();
    }

    public function testRegistersMiddlewareForMultipleMethods()
    {
        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET,POST", $this->middleware->reveal());

        $this->request = $this->request->withMethod("GET");
        $map($this->request, $this->response, $this->next);

        $this->request = $this->request->withMethod("POST");
        $map($this->request, $this->response, $this->next);

        $this->middleware->__invoke(Argument::cetera())->shouldHaveBeenCalledTimes(2);
    }

    public function testSettingNullUnregistersMiddleware()
    {
        $this->request = $this->request->withMethod("POST");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("POST", $this->middleware->reveal());
        $map->register("POST", null);
        $response = $map($this->request, $this->response, $this->next);

        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testSetsStatusTo200ForOptions()
    {
        $this->request = $this->request->withMethod("OPTIONS");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $response = $map($this->request, $this->response, $this->next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testStopsPropagatingAfterOptions()
    {
        $this->request = $this->request->withMethod("OPTIONS");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $map($this->request, $this->response, $this->next);

        $this->assertFalse($this->next->called);
    }

    /** @dataProvider allowedMethodProvider */
    public function testSetsAllowHeaderForOptions($methodsDeclared, $methodsAllowed)
    {
        $this->request = $this->request->withMethod("OPTIONS");

        $map = new MethodMap($this->dispatcher->reveal());
        foreach ($methodsDeclared as $method) {
            $map->register($method, $this->middleware->reveal());
        }
        $response = $map($this->request, $this->response, $this->next);

        $this->assertContainsEach($methodsAllowed, $response->getHeaderLine("Allow"));
    }

    /** @dataProvider allowedMethodProvider */
    public function testSetsStatusTo405ForBadMethod()
    {
        $this->request = $this->request->withMethod("POST");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $response = $map($this->request, $this->response, $this->next);

        $this->assertEquals(405, $response->getStatusCode());
    }

    /**
     * @coversNothing
     * @dataProvider allowedMethodProvider
     */
    public function testStopsPropagatingAfterBadMethod()
    {
        $this->request = $this->request->withMethod("POST");

        $map = new MethodMap($this->dispatcher->reveal());
        $map->register("GET", $this->middleware->reveal());
        $map($this->request, $this->response, $this->next);
        $this->assertFalse($this->next->called);
    }

    /** @dataProvider allowedMethodProvider */
    public function testSetsAllowHeaderForBadMethod($methodsDeclared, $methodsAllowed)
    {
        $this->request = $this->request->withMethod("BAD");

        $map = new MethodMap($this->dispatcher->reveal());
        foreach ($methodsDeclared as $method) {
            $map->register($method, $this->middleware->reveal());
        }
        $response = $map($this->request, $this->response, $this->next);

        $this->assertContainsEach($methodsAllowed, $response->getHeaderLine("Allow"));
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

    private function assertContainsEach($expectedList, $actual) {
        foreach ($expectedList as $expected) {
            if (strpos($actual, $expected) === false) {
                $this->assertTrue(false, "'$actual' does not contain expected '$expected'");
            }
        }
        $this->assertTrue(true);
    }
}
