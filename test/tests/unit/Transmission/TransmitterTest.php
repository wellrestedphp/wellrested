<?php

namespace WellRESTed\Transmission;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Test\TestCase;

class TransmitterTest extends TestCase
{
    use ProphecyTrait;

    private $request;
    private $response;
    private $body;

    protected function setUp(): void
    {
        HeaderStack::reset();

        $this->request = (new ServerRequest())
            ->withMethod('HEAD');

        $this->body = $this->prophesize(StreamInterface::class);
        $this->body->isReadable()->willReturn(false);
        $this->body->getSize()->willReturn(1024);
        /** @var StreamInterface $stream */
        $stream = $this->body->reveal();

        $this->response = (new Response())
            ->withStatus(200)
            ->withBody($stream);
    }

    public function testSendStatusCodeWithReasonPhrase(): void
    {
        $transmitter = new Transmitter();
        $transmitter->transmit($this->request, $this->response);
        $this->assertContains('HTTP/1.1 200 OK', HeaderStack::getHeaders());
    }

    public function testSendStatusCodeWithoutReasonPhrase(): void
    {
        $this->response = $this->response->withStatus(999);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request, $this->response);
        $this->assertContains('HTTP/1.1 999', HeaderStack::getHeaders());
    }

    /**
     * @dataProvider headerProvider
     * @param string $header
     */
    public function testSendsHeaders(string $header): void
    {
        $this->response = $this->response
            ->withHeader('Content-length', ['2048'])
            ->withHeader('X-foo', ['bar', 'baz']);

        $transmitter = new Transmitter();
        $transmitter->transmit($this->request, $this->response);
        $this->assertContains($header, HeaderStack::getHeaders());
    }

    public function headerProvider(): array
    {
        return [
            ['Content-length: 2048'],
            ['X-foo: bar'],
            ['X-foo: baz']
        ];
    }

    public function testOutputsBody(): void
    {
        $content = 'Hello, world!';

        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn($content);

        $transmitter = new Transmitter();
        $transmitter->setChunkSize(0);

        ob_start();
        $transmitter->transmit($this->request, $this->response);
        $captured = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($content, $captured);
    }

    public function testOutputsBodyInChunks(): void
    {
        $content = 'Hello, world!';
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

    public function testOutputsUnseekableStreamInChunks(): void
    {
        $content = 'Hello, world!';
        $chunkSize = 3;
        $position = 0;

        $this->body->isSeekable()->willReturn(false);
        $this->body->isReadable()->willReturn(true);
        $this->body->rewind()->willThrow(new RuntimeException());
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

    public function testAddContentLengthHeader(): void
    {
        $bodySize = 1024;
        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn('');
        $this->body->getSize()->willReturn($bodySize);

        $transmitter = new Transmitter();
        $transmitter->setChunkSize(0);
        $transmitter->transmit($this->request, $this->response);

        $this->assertContains("Content-length: $bodySize", HeaderStack::getHeaders());
    }

    public function testDoesNotReplaceContentLengthHeaderWhenContentLengthIsAlreadySet(): void
    {
        $streamSize = 1024;
        $headerSize = 2048;

        $this->response = $this->response->withHeader('Content-length', $headerSize);

        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn('');
        $this->body->getSize()->willReturn($streamSize);

        $transmitter = new Transmitter();
        $transmitter->setChunkSize(0);
        $transmitter->transmit($this->request, $this->response);

        $this->assertContains("Content-length: $headerSize", HeaderStack::getHeaders());
    }

    public function testDoesNotAddContentLengthHeaderWhenTransferEncodingIsChunked(): void
    {
        $bodySize = 1024;

        $this->response = $this->response->withHeader('Transfer-encoding', 'CHUNKED');

        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn('');
        $this->body->getSize()->willReturn($bodySize);

        $transmitter = new Transmitter();
        $transmitter->setChunkSize(0);
        $transmitter->transmit($this->request, $this->response);

        $this->assertArrayDoesNotContainValueWithPrefix(HeaderStack::getHeaders(), 'Content-length:');
    }

    public function testDoesNotAddContentLengthHeaderWhenBodySizeIsNull(): void
    {
        $this->body->isReadable()->willReturn(true);
        $this->body->__toString()->willReturn('');
        $this->body->getSize()->willReturn(null);

        $transmitter = new Transmitter();
        $transmitter->setChunkSize(0);
        $transmitter->transmit($this->request, $this->response);

        $this->assertArrayDoesNotContainValueWithPrefix(HeaderStack::getHeaders(), 'Content-length:');
    }

    private function assertArrayDoesNotContainValueWithPrefix(array $arr, string $prefix): void
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

// -----------------------------------------------------------------------------

// Declare header function in this namespace so the class under test will use
// this instead of the internal global functions during testing.

class HeaderStack
{
    private static $headers;

    public static function reset()
    {
        self::$headers = [];
    }

    public static function push($header)
    {
        self::$headers[] = $header;
    }

    public static function getHeaders()
    {
        return self::$headers;
    }
}

function header($string, $dummy = true)
{
    HeaderStack::push($string);
}
