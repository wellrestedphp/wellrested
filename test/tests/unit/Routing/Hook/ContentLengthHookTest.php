<?php

namespace WellRESTed\Test\Unit\Routing\Hook;

use Prophecy\Argument;
use WellRESTed\Routing\Hook\ContentLengthHook;

/**
 * @covers WellRESTed\Routing\Hook\ContentLengthHook
 */
class ContentLengthHookTest extends \PHPUnit_Framework_TestCase
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
        $this->response->withHeader(Argument::cetera())->will(
            function () {
                $this->hasHeader("Content-length")->willReturn(true);
                return $this;
            }
        );
    }

    public function testAddsContentLengthHeader()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $hook = new ContentLengthHook();
        $hook->dispatch($request, $response);

        $this->response->withHeader("Content-length", 1024)->shouldHaveBeenCalled();
    }

    public function testMultipleDispatchesHaveNoEffect()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $hook = new ContentLengthHook();
        $hook->dispatch($request, $response);
        $hook->dispatch($request, $response);

        $this->response->withHeader("Content-length", 1024)->shouldHaveBeenCalledTimes(1);
    }

    public function testDoesNotAddHeaderWhenContentLenghtIsAlreadySet()
    {
        $this->response->hasHeader("Content-length")->willReturn(true);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $hook = new ContentLengthHook();
        $hook->dispatch($request, $response);

        $this->response->withHeader(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testDoesNotAddHeaderWhenTransferEncodingIsChunked()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("CHUNKED");

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $hook = new ContentLengthHook();
        $hook->dispatch($request, $response);

        $this->response->withHeader(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testDoesNotAddHeaderWhenBodySizeIsNull()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");
        $this->body->getSize()->willReturn(null);

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $hook = new ContentLengthHook();
        $hook->dispatch($request, $response);

        $this->response->withHeader(Argument::cetera())->shouldNotHaveBeenCalled();
    }
}
