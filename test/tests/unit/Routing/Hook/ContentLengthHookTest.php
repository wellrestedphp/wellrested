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
        $this->response->withHeader(Argument::cetera())->will(
            function ($args) {
                $this->hasHeader($args[0])->willReturn(true);
                $this->getHeader($args[0])->willReturn([$args[1]]);
                $this->getHeaderLine($args[0])->willReturn($args[1]);
                return $this;
            }
        );
        $this->next = function ($request, $response) {
            return $response;
        };
    }

    public function testAddsContentLengthHeader()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");

        $hook = new ContentLengthHook();
        $response = $hook->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->assertEquals([1024], $response->getHeader("Content-length"));
    }

    public function testMultipleDispatchesHaveNoEffect()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");

        $hook = new ContentLengthHook();

        $response = $this->response->reveal();
        $response = $hook->dispatch($this->request->reveal(), $response, $this->next);
        $hook->dispatch($this->request->reveal(), $response, $this->next);

        $this->response->withHeader("Content-length", 1024)->shouldHaveBeenCalledTimes(1);
    }

    public function testDoesNotAddHeaderWhenContentLenghtIsAlreadySet()
    {
        $this->response->hasHeader("Content-length")->willReturn(true);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");

        $hook = new ContentLengthHook();
        $hook->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->response->withHeader(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testDoesNotAddHeaderWhenTransferEncodingIsChunked()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("CHUNKED");

        $hook = new ContentLengthHook();
        $hook->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->response->withHeader(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testDoesNotAddHeaderWhenBodySizeIsNull()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");
        $this->body->getSize()->willReturn(null);

        $hook = new ContentLengthHook();
        $hook->dispatch($this->request->reveal(), $this->response->reveal(), $this->next);

        $this->response->withHeader(Argument::cetera())->shouldNotHaveBeenCalled();
    }
}
