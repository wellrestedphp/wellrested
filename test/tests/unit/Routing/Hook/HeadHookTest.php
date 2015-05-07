<?php

namespace WellRESTed\Test\Unit\Routing\Hook;

use Prophecy\Argument;
use WellRESTed\Routing\Hook\HeadHook;

/**
 * @covers WellRESTed\Routing\Hook\HeadHook
 * @uses WellRESTed\Message\NullStream
 */
class HeadHookTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;
    private $body;

    public function setUp()
    {
        parent::setUp();
        $this->body = $this->prophesize('Psr\Http\Message\StreamInterface');
        $this->body->getSize()->willReturn(1024);
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->response->getBody()->willReturn($this->body->reveal());
        $this->response->withBody(Argument::any())->will(function ($args) {
            $this->getBody()->willReturn($args[0]);
            return $this;
        });
    }

    public function testReplacesBodyForHeadRequest()
    {
        $this->request->getMethod()->willReturn("HEAD");
        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $hook = new HeadHook();
        $hook->dispatch($request, $response);
        $this->assertSame(0, $response->getBody()->getSize());
    }

    public function testMultipleDispatchesHaveNoEffect()
    {
        $this->request->getMethod()->willReturn("HEAD");
        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $hook = new HeadHook();
        $hook->dispatch($request, $response);
        $hook->dispatch($request, $response);
        $this->response->withBody(Argument::any())->shouldHaveBeenCalledTimes(1);
    }

    public function testDoesNotReplaceBodyForNonHeadRequests()
    {
        $this->request->getMethod()->willReturn("GET");
        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $hook = new HeadHook();
        $hook->dispatch($request, $response);
        $this->response->withBody(Argument::any())->shouldNotHaveBeenCalled();
    }
}
