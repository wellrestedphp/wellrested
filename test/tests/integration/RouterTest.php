<?php

namespace pjdietz\WellRESTed\Test\Integration;

use pjdietz\WellRESTed\Router;
use Prophecy\Argument;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;

    public function setUp()
    {
        $this->request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $this->request->getPath()->willReturn("/");
        $this->request->getMethod()->willReturn("GET");
        $this->response = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\ResponseInterface");
        $this->response->getStatusCode()->willReturn(200);
        $this->response->getBody()->willReturn("Hello, world!");
    }

    public function testDispatchesCallable()
    {
        $response = $this->response;

        $router = new Router();
        $router->add("/", function () use ($response) {
            return $response->reveal();
        });

        $result = $router->getResponse($this->request->reveal());
        $this->assertSame($response->reveal(), $result);
    }

    public function testDispatchesCallableWithArguments()
    {
        $response = $this->response;
        $args = ["cat" => "molly"];

        $router = new Router();
        $router->add("/", function ($rqst, $args) use ($response) {
            $response->getBody()->willReturn($args["cat"]);
            return $response->reveal();
        });

        $result = $router->getResponse($this->request->reveal(), $args);
        $this->assertEquals("molly", $result->getBody());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testStopsDispatchingCallablesAfterFirstNonNull()
    {
        $router = new Router();
        $router->add("/cats/{cat}", function () {
            echo "Hello, cat!";
            return true;
        });
        $router->add("/cats/{cat}", function () {
            echo "Hello, cat!";
        });

        $this->request->getPath()->willReturn("/cats/molly");

        ob_start();
        $router->getResponse($this->request->reveal());
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("Hello, cat!", $captured);
    }
}
