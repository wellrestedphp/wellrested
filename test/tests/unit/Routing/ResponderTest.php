<?php

namespace WellRESTed\Test\Unit\Routing;

use Prophecy\Argument;
use WellRESTed\Routing\HeaderStack;
use WellRESTed\Routing\Responder;

require_once(__DIR__ . "/../../../src/HeaderStack.php");

/**
 * @covers WellRESTed\Routing\Responder
 */
class ResponderTest extends \PHPUnit_Framework_TestCase
{
    private $response;
    private $body;

    public function setUp()
    {
        HeaderStack::reset();
        $this->body = $this->prophesize('\Psr\Http\Message\StreamableInterface');
        $this->body->isReadable()->willReturn(false);
        $this->response = $this->prophesize('\Psr\Http\Message\ResponseInterface');
        $this->response->getHeaders()->willReturn([]);
        $this->response->getProtocolVersion()->willReturn("1.1");
        $this->response->getStatusCode()->willReturn("200");
        $this->response->getReasonPhrase()->willReturn("Ok");
        $this->response->getBody()->willReturn($this->body->reveal());
    }

    public function testSendStatusCodeWithReasonPhrase()
    {
        $this->response->getStatusCode()->willReturn("200");
        $this->response->getReasonPhrase()->willReturn("Ok");

        $responder = new Responder();
        $responder->respond($this->response->reveal());
        $this->assertContains("HTTP/1.1 200 Ok", HeaderStack::getHeaders());
    }

    public function testSendStatusCodeWithoutReasonPhrase()
    {
        $this->response->getStatusCode()->willReturn("999");
        $this->response->getReasonPhrase()->willReturn(null);

        $responder = new Responder();
        $responder->respond($this->response->reveal());
        $this->assertContains("HTTP/1.1 999", HeaderStack::getHeaders());
    }

    /**
     * @dataProvider headerProvider
     */
    public function testSendsHeaders($header)
    {
        $this->response->getHeaders()->willReturn([
            "Content-length" => ["2048"],
            "X-foo" => ["bar", "baz"],
        ]);

        $responder = new Responder();
        $responder->respond($this->response->reveal());
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

    public function testOutputsBody()
    {
        $content = "Hello, world!";

        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn($content);

        $responder = new Responder();

        ob_start();
        $responder->respond($this->response->reveal());
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($content, $captured);
    }

    public function testOutputsBodyInChunks()
    {
        $content = "Hello, world!";
        $chunkSize = 3;
        $position = 0;

        $this->body->isReadable()->willReturn(true);
        $this->body->rewind()->willReturn(true);
        $this->body->eof()->willReturn(false);
        $this->body->read(Argument::any())->will(function ($args) use ($content, &$position) {
            $chunkSize = $args[0];
            $chunk = substr($content, $position, $chunkSize);
            $position += $chunkSize;
            if ($position >= strlen($content)) {
                $this->eof()->willReturn(true);
            }
            return $chunk;
        });

        $responder = new Responder();
        $responder->setChunkSize($chunkSize);

        ob_start();
        $responder->respond($this->response->reveal(), $chunkSize);
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($content, $captured);
    }
}
