<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Routing\ResponsePrep\ContentLengthPrep;

/**
 * @covers WellRESTed\Routing\ResponsePrep\ContentLengthPrep
 */
class ContentLengthPrepTest extends \PHPUnit_Framework_TestCase
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
        $this->response->withHeader(Argument::cetera())->willReturn($this->response->reveal());
    }

    public function testAddContentLengthHeader()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $prep = new ContentLengthPrep();
        $prep->dispatch($request, $response);

        $this->response->withHeader("Content-length", 1024)->shouldHaveBeenCalled();
    }

    public function testDoesNotAddHeaderWhenContentLenghtIsAlreadySet()
    {
        $this->response->hasHeader("Content-length")->willReturn(true);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $prep = new ContentLengthPrep();
        $prep->dispatch($request, $response);

        $this->response->withHeader(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testDoesNotAddHeaderWhenTransferEncodingIsChunked()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("CHUNKED");

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $prep = new ContentLengthPrep();
        $prep->dispatch($request, $response);

        $this->response->withHeader(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testDoesNotAddHeaderWhenBodySizeIsNull()
    {
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");
        $this->body->getSize()->willReturn(null);

        $request = $this->request->reveal();
        $response = $this->response->reveal();
        $prep = new ContentLengthPrep();
        $prep->dispatch($request, $response);

        $this->response->withHeader(Argument::cetera())->shouldNotHaveBeenCalled();
    }
}
