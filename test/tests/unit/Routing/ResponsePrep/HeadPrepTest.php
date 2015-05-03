<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Routing\ResponsePrep\HeadPrep;

/**
 * @covers WellRESTed\Routing\ResponsePrep\HeadPrep
 * @uses WellRESTed\Message\NullStream
 */
class HeadPrepTest extends \PHPUnit_Framework_TestCase
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
        $prep = new HeadPrep();
        $prep->dispatch($request, $response);
        $this->assertSame(0, $response->getBody()->getSize());
    }

    public function testDoesNotReplaceBodyForNonHeadRequests()
    {
        $this->request->getMethod()->willReturn("GET");
        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $prep = new HeadPrep();
        $prep->dispatch($request, $response);
        $this->response->withBody(Argument::any())->shouldNotHaveBeenCalled();
    }
}
