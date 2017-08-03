<?php

namespace WellRESTed\Test\Unit\Transmission;

use Prophecy\Argument;
use Psr\Http\Message\StreamInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Test\TestCase;
use WellRESTed\Transmission\HeaderStack;
use WellRESTed\Transmission\Transmitter;

require_once __DIR__ . "/../../../src/HeaderStack.php";

class TransmitterTest extends TestCase
{
    private $request;
    private $response;
    private $body;

    public function setUp()
    {
        HeaderStack::reset();

        $this->request = (new ServerRequest())
            ->withMethod("HEAD");

        $this->body = $this->prophesize('\Psr\Http\Message\StreamInterface');
        $this->body->isReadable()->willReturn(false);
        $this->body->getSize()->willReturn(1024);
        /** @var StreamInterface $stream */
        $stream = $this->body->reveal();

        $this->response = (new Response())
            ->withStatus(200)
            ->withBody($stream);
    }

    public function testCreatesInstance()
    {
        $transmitter = new Transmitter();
        $this->assertNotNull($transmitter);
    }

    public function testSendStatusCodeWithReasonPhrase()
    {
        $transmitter = new Transmitter();
        $transmitter->transmit($this->request, $this->response);
        $this->assertContains("HTTP/1.1 200 OK", HeaderStack::getHeaders());
    }

    public function testSendStatusCodeWithoutReasonPhrase()
    {
        $this->response = $this->response->withStatus(999);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request, $this->response);
        $this->assertContains("HTTP/1.1 999", HeaderStack::getHeaders());
    }

    /** @dataProvider headerProvider */
    public function testSendsHeaders($header)
    {
        $this->response = $this->response
            ->withHeader("Content-length", ["2048"])
            ->withHeader("X-foo", ["bar", "baz"]);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request, $this->response);
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

        $transmitter = new Transmitter();

        ob_start();
        $transmitter->transmit($this->request, $this->response);
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($content, $captured);
    }

    public function testOutputsBodyInChunks()
    {
        $content = "Hello, world!";
        $chunkSize = 3;
        $position = 0;

        $this->body->isSeekable()->willReturn(true);
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
        $transmitter->transmit($this->request, $this->response);
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($content, $captured);
    }

    public function testOutputsUnseekableStreamInChunks()
    {
        $content = "Hello, world!";
        $chunkSize = 3;
        $position = 0;

        $this->body->isSeekable()->willReturn(false);
        $this->body->isReadable()->willReturn(true);
        $this->body->rewind()->willThrow(new \RuntimeException());
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
        $transmitter->transmit($this->request, $this->response);
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($content, $captured);
    }

    // ------------------------------------------------------------------------
    // Preparation

    public function testAddContentLengthHeader()
    {
        $bodySize = 1024;
        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn("");
        $this->body->getSize()->willReturn($bodySize);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request, $this->response);

        $this->assertContains("Content-length: $bodySize", HeaderStack::getHeaders());
    }

    public function testDoesNotReplaceContentLengthHeaderWhenContentLengthIsAlreadySet()
    {
        $streamSize = 1024;
        $headerSize = 2048;

        $this->response = $this->response->withHeader("Content-length", $headerSize);

        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn("");
        $this->body->getSize()->willReturn($streamSize);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request, $this->response);

        $this->assertContains("Content-length: $headerSize", HeaderStack::getHeaders());
    }

    public function testDoesNotAddContentLengthHeaderWhenTransferEncodingIsChunked()
    {
        $bodySize = 1024;

        $this->response = $this->response->withHeader("Transfer-encoding", "CHUNKED");

        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn("");
        $this->body->getSize()->willReturn($bodySize);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request, $this->response);

        $this->assertArrayDoesNotContainValueWithPrefix(HeaderStack::getHeaders(), "Content-length:");
    }

    public function testDoesNotAddContentLengthHeaderWhenBodySizeIsNull()
    {
        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn("");
        $this->body->getSize()->willReturn(null);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request, $this->response);

        $this->assertArrayDoesNotContainValueWithPrefix(HeaderStack::getHeaders(), "Content-length:");
    }

    private function assertArrayDoesNotContainValueWithPrefix($arr, $prefix)
    {
        $normalPrefix = strtolower($prefix);
        foreach ($arr as $item) {
            $normalItem = strtolower($item);
            if (substr($normalItem, 0, strlen($normalPrefix)) === $normalPrefix) {
                $this->assertTrue(false, "Array should not contain value beginning with '$prefix' but contained '$item'");
            }
        }
        $this->assertTrue(true);
    }
}
