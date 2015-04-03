<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Routing\MethodMap;

/**
 * @covers WellRESTed\Routing\MethodMap
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

    public function testDispatchesMiddlewareWithMatchingMethod()
    {
        $this->request->getMethod()->willReturn("GET");

        $middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");
        $middleware->dispatch(Argument::cetera())->willReturn();

        $map = new MethodMap(["GET" => $middleware->reveal()]);
        $map->dispatch($this->request->reveal(), $this->response->reveal());

        $middleware->dispatch($this->request->reveal(), $this->response->reveal())->shouldHaveBeenCalled();
    }

    public function testDispatchesGetMiddlewareForHeadByDefault()
    {
        $this->request->getMethod()->willReturn("HEAD");

        $middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");
        $middleware->dispatch(Argument::cetera())->willReturn();

        $map = new MethodMap(["GET" => $middleware->reveal()]);
        $map->dispatch($this->request->reveal(), $this->response->reveal());

        $middleware->dispatch($this->request->reveal(), $this->response->reveal())->shouldHaveBeenCalled();
    }

    public function testRegistersMiddlewareForMultipleMethods()
    {
        $middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");
        $middleware->dispatch(Argument::cetera())->willReturn();

        $map = new MethodMap();
        $map->add("GET,POST", $middleware->reveal());

        $this->request->getMethod()->willReturn("GET");
        $map->dispatch($this->request->reveal(), $this->response->reveal());

        $this->request->getMethod()->willReturn("POST");
        $map->dispatch($this->request->reveal(), $this->response->reveal());

        $middleware->dispatch($this->request->reveal(), $this->response->reveal())->shouldHaveBeenCalledTimes(2);
    }

    public function testSetsStatusTo200ForOptions()
    {
        $this->request->getMethod()->willReturn("OPTIONS");

        $middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");

        $map = new MethodMap(["GET" => $middleware->reveal()]);
        $map->dispatch($this->request->reveal(), $this->response->reveal());

        $this->response->withStatus(200)->shouldHaveBeenCalled();
    }

    /**
     * @dataProvider allowedMethodProvider
     */
    public function testSetsAllowHeaderForOptions($methodsDeclared, $methodsAllowed)
    {
        $this->request->getMethod()->willReturn("OPTIONS");

        $middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");

        $map = new MethodMap();
        foreach ($methodsDeclared as $method) {
            $map->add($method, $middleware->reveal());
        }

        $map->dispatch($this->request->reveal(), $this->response->reveal());

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

    public function testSetsStatusTo405ForBadMethod()
    {
        $this->request->getMethod()->willReturn("POST");

        $middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");

        $map = new MethodMap(["GET" => $middleware->reveal()]);
        $map->dispatch($this->request->reveal(), $this->response->reveal());

        $this->response->withStatus(405)->shouldHaveBeenCalled();
    }

    /**
     * @dataProvider allowedMethodProvider
     */
    public function testSetsAlloweHeaderForBadMethod($methodsDeclared, $methodsAllowed)
    {
        $this->request->getMethod()->willReturn("BAD");

        $middleware = $this->prophesize("\\WellRESTed\\Routing\\MiddlewareInterface");

        $map = new MethodMap();
        foreach ($methodsDeclared as $method) {
            $map->add($method, $middleware->reveal());
        }

        $map->dispatch($this->request->reveal(), $this->response->reveal());

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
