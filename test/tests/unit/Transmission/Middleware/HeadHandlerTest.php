<?php

namespace WellRESTed\Test\Unit\Transmission\Middleware;

use Prophecy\Argument;
use WellRESTed\Transmission\Middleware\HeadHandler;

/**
 * @covers WellRESTed\Transmission\Middleware\HeadHandler
 * @uses WellRESTed\Message\NullStream
 * @group transmission
 */
class HeadHandlerTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;
    private $next;
    private $body;

    public function setUp()
    {
        parent::setUp();
        $this->body = $this->prophesize('Psr\Http\Message\StreamInterface');
        $this->body->getSize()->willReturn(1024);
        $this->request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');
        $this->response = $this->prophesize('Psr\Http\Message\ResponseInterface');
        $this->response->getBody()->willReturn($this->body->reveal());
        $this->response->withBody(Argument::any())->will(
            function ($args) {
                $this->getBody()->willReturn($args[0]);
                return $this;
            }
        );
        $this->next = function ($request, $response) {
            return $response;
        };
    }

    public function testReplacesBodyForHeadRequest()
    {
        $this->request->getMethod()->willReturn("HEAD");
        $hook = new HeadHandler();
        $response = $hook->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->assertSame(0, $response->getBody()->getSize());
    }

    public function testMultipleDispatchesHaveNoEffect()
    {
        $this->request->getMethod()->willReturn("HEAD");
        $hook = new HeadHandler();
        $response = $hook->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);
        $hook->dispatch($this->request->reveal(), $response, $this->next);
        $this->response->withBody(Argument::any())->shouldHaveBeenCalledTimes(1);
    }

    public function testDoesNotReplaceBodyForNonHeadRequests()
    {
        $this->request->getMethod()->willReturn("GET");
        $hook = new HeadHandler();
        $hook->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);
        $this->response->withBody(Argument::any())->shouldNotHaveBeenCalled();
    }
}