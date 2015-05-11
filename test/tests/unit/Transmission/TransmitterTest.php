<?php

namespace WellRESTed\Test\Unit\Transmission;

use Prophecy\Argument;
use WellRESTed\Transmission\HeaderStack;
use WellRESTed\Transmission\Transmitter;

require_once __DIR__ . "/../../../src/HeaderStack.php";

/**
 * @coversDefaultClass WellRESTed\Transmission\Transmitter
 * @uses WellRESTed\Transmission\Transmitter
 * @uses WellRESTed\Transmission\Middleware\ContentLengthHandler
 * @uses WellRESTed\Transmission\Middleware\HeadHandler
 * @uses WellRESTed\Dispatching\Dispatcher
 * @uses WellRESTed\Dispatching\DispatchStack
 * @group transmission
 */
class TransmitterTest extends \PHPUnit_Framework_TestCase
{
    private $request;
    private $response;
    private $body;

    public function setUp()
    {
        HeaderStack::reset();
        $this->body = $this->prophesize('\Psr\Http\Message\StreamInterface');
        $this->body->isReadable()->willReturn(false);
        $this->body->getSize()->willReturn(1024);
        $this->request = $this->prophesize('\Psr\Http\Message\ServerRequestInterface');
        $this->request->getMethod()->willReturn("HEAD");
        $this->response = $this->prophesize('\Psr\Http\Message\ResponseInterface');
        $this->response->getHeaders()->willReturn([]);
        $this->response->hasHeader("Content-length")->willReturn(true);
        $this->response->getHeaderLine("Transfer-encoding")->willReturn("");
        $this->response->getProtocolVersion()->willReturn("1.1");
        $this->response->getStatusCode()->willReturn("200");
        $this->response->getReasonPhrase()->willReturn("Ok");
        $this->response->getBody()->willReturn($this->body->reveal());
        $this->response->withHeader(Argument::cetera())->willReturn($this->response->reveal());
        $this->response->withBody(Argument::any())->willReturn($this->response->reveal());
    }

    /**
     * @covers ::__construct
     */
    public function testCreatesInstance()
    {
        $transmitter = new Transmitter();
        $this->assertNotNull($transmitter);
    }

    /**
     * @covers ::transmit
     * @covers ::getStatusLine
     */
    public function testSendStatusCodeWithReasonPhrase()
    {
        $this->response->getStatusCode()->willReturn("200");
        $this->response->getReasonPhrase()->willReturn("Ok");

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request->reveal(), $this->response->reveal());
        $this->assertContains("HTTP/1.1 200 Ok", HeaderStack::getHeaders());
    }

    /**
     * @covers ::transmit
     * @covers ::getStatusLine
     */
    public function testSendStatusCodeWithoutReasonPhrase()
    {
        $this->response->getStatusCode()->willReturn("999");
        $this->response->getReasonPhrase()->willReturn(null);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request->reveal(), $this->response->reveal());
        $this->assertContains("HTTP/1.1 999", HeaderStack::getHeaders());
    }

    /**
     * @covers ::transmit
     * @dataProvider headerProvider
     */
    public function testSendsHeaders($header)
    {
        $this->response->getHeaders()->willReturn([
            "Content-length" => ["2048"],
            "X-foo" => ["bar", "baz"],
        ]);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request->reveal(), $this->response->reveal());
        $this->assertContains($header, HeaderStack::getHeaders());
    }

    public function headerProvider()
    {
        return [
            ["Content-length: 2048"],
            ["X-foo: bar"],
            ["X-foo: baz"]
        ];
    }

    /**
     * @covers ::transmit
     * @covers ::outputBody
     */
    public function testOutputsBody()
    {
        $content = "Hello, world!";

        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn($content);

        $transmitter = new Transmitter();

        ob_start();
        $transmitter->transmit($this->request->reveal(), $this->response->reveal());
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($content, $captured);
    }

    /**
     * @covers ::transmit
     * @covers ::setChunkSize
     * @covers ::outputBody
     */
    public function testOutputsBodyInChunks()
    {
        $content = "Hello, world!";
        $chunkSize = 3;
        $position = 0;

        $this->body->isReadable()->willReturn(true);
        $this->body->rewind()->willReturn(true);
        $this->body->eof()->willReturn(false);
        $this->body->read(Argument::any())->will(
            function ($args) use ($content, &$position) {
                $chunkSize = $args[0];
                $chunk = substr($content, $position, $chunkSize);
                $position += $chunkSize;
                if ($position >= strlen($content)) {
                    $this->eof()->willReturn(true);
                }
                return $chunk;
            }
        );

        $transmitter = new Transmitter();
        $transmitter->setChunkSize($chunkSize);

        ob_start();
        $transmitter->transmit($this->request->reveal(), $this->response->reveal(), $chunkSize);
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($content, $captured);
    }

    /**
     * @covers ::prepareResponse
     */
    public function testAddContentLengthHeader()
    {
        $bodySize = 1024;
        $this->response->getStatusCode()->willReturn("200");
        $this->response->getReasonPhrase()->willReturn("Ok");
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn("");
        $this->body->getSize()->willReturn($bodySize);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request->reveal(), $this->response->reveal());
        $this->response->withHeader("Content-length", $bodySize)->shouldHaveBeenCalled();
    }

    /**
     * @covers ::prepareResponse
     */
    public function testReplacesBodyForHeadRequeset()
    {
        $this->response->getStatusCode()->willReturn("200");
        $this->response->getReasonPhrase()->willReturn("Ok");
        $this->response->hasHeader("Content-length")->willReturn(false);
        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn("");

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request->reveal(), $this->response->reveal());
        $this->response->withBody(Argument::any())->shouldHaveBeenCalled();
    }
}
