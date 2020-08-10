<?php

namespace WellRESTed\Test\Unit\Message;

use InvalidArgumentException;
use WellRESTed\Message\Message;
use WellRESTed\Message\Response;
use WellRESTed\Message\Stream;
use WellRESTed\Test\TestCase;

class MessageTest extends TestCase
{
    /** @var Message */
    private $message;

    public function setUp(): void
    {
        $this->message = new Response();
    }

    public function testSetsHeadersOnConstruction()
    {
        $headers = ['X-foo' => ['bar', 'baz']];
        $message = new Response(200, $headers);
        $this->assertEquals(['bar', 'baz'], $message->getHeader('X-foo'));
    }

    public function testSetsBodyOnConstruction()
    {
        $body = new Stream('Hello, world');
        $message = new Response(200, [], $body);
        $this->assertSame($body, $message->getBody());
    }

    public function testCloneMakesDeepCopyOfHeaders()
    {
        $message1 = (new Response())
            ->withHeader('Content-type', 'text/plain');
        $message2 = $message1
            ->withHeader('Content-type', 'application/json');

        $this->assertNotEquals(
            $message1->getHeader('Content-type'),
            $message2->getHeader('Content-type')
        );
    }

    // ------------------------------------------------------------------------
    // Protocol Version

    public function testGetProtocolVersionReturnsProtocolVersion1Point1ByDefault()
    {
        $message = new Response();
        $this->assertEquals('1.1', $message->getProtocolVersion());
    }

    public function testGetProtocolVersionReturnsProtocolVersion()
    {
        $message = (new Response())
            ->withProtocolVersion('1.0');
        $this->assertEquals('1.0', $message->getProtocolVersion());
    }

    public function testGetProtocolVersionReplacesProtocolVersion()
    {
        $message = (new Response())
            ->withProtocolVersion('1.0');
        $this->assertEquals('1.0', $message->getProtocolVersion());
    }

    // ------------------------------------------------------------------------
    // Headers

    /** @dataProvider validHeaderValueProvider */
    public function testWithHeaderReplacesHeader($expected, $value)
    {
        $message = (new Response())
            ->withHeader('X-foo', 'Original value')
            ->withHeader('X-foo', $value);
        $this->assertEquals($expected, $message->getHeader('X-foo'));
    }

    public function validHeaderValueProvider()
    {
        return [
            [['0'], 0],
            [['molly','bear'],['molly','bear']]
        ];
    }

    /**
     * @dataProvider invalidHeaderProvider
     */
    public function testWithHeaderThrowsExceptionWithInvalidArgument($name, $value)
    {
        $this->expectException(InvalidArgumentException::class);
        $message = (new Response())
            ->withHeader($name, $value);
    }

    public function invalidHeaderProvider()
    {
        return [
            [0, 1024],
            ['Content-length', false],
            ['Content-length', [false]]
        ];
    }

    public function testWithAddedHeaderSetsHeader()
    {
        $message = (new Response())
            ->withAddedHeader('Content-type', 'application/json');
        $this->assertEquals(['application/json'], $message->getHeader('Content-type'));
    }

    public function testWithAddedHeaderAppendsValue()
    {
        $message = (new Response())
            ->withAddedHeader('Set-Cookie', ['cat=Molly'])
            ->withAddedHeader('Set-Cookie', ['dog=Bear']);
        $cookies = $message->getHeader('Set-Cookie');
        $this->assertTrue(
            in_array('cat=Molly', $cookies) &&
            in_array('dog=Bear', $cookies)
        );
    }

    public function testWithoutHeaderRemovesHeader()
    {
        $message = (new Response())
            ->withHeader('Content-type', 'application/json')
            ->withoutHeader('Content-type');
        $this->assertFalse($message->hasHeader('Content-type'));
    }

    public function testGetHeaderReturnsEmptyArrayForUnsetHeader()
    {
        $message = new Response();
        $this->assertEquals([], $message->getHeader('X-name'));
    }

    public function testGetHeaderReturnsSingleHeader()
    {
        $message = (new Response())
            ->withAddedHeader('Content-type', 'application/json');
        $this->assertEquals(['application/json'], $message->getHeader('Content-type'));
    }

    public function testGetHeaderReturnsMultipleValuesForHeader()
    {
        $message = (new Response())
            ->withAddedHeader('X-name', 'cat=Molly')
            ->withAddedHeader('X-name', 'dog=Bear');
        $this->assertEquals(['cat=Molly', 'dog=Bear'], $message->getHeader('X-name'));
    }

    public function testGetHeaderLineReturnsEmptyStringForUnsetHeader()
    {
        $message = new Response();
        $this->assertSame('', $message->getHeaderLine('X-not-set'));
    }

    public function testGetHeaderLineReturnsMultipleHeadersJoinedByCommas()
    {
        $message = (new Response())
            ->withAddedHeader('X-name', 'cat=Molly')
            ->withAddedHeader('X-name', 'dog=Bear');
        $this->assertEquals('cat=Molly, dog=Bear', $message->getHeaderLine('X-name'));
    }

    public function testHasHeaderReturnsTrueWhenHeaderIsSet()
    {
        $message = (new Response())
            ->withHeader('Content-type', 'application/json');
        $this->assertTrue($message->hasHeader('Content-type'));
    }

    public function testHasHeaderReturnsFalseWhenHeaderIsNotSet()
    {
        $message = new Response();
        $this->assertFalse($message->hasHeader('Content-type'));
    }

    public function testGetHeadersReturnOriginalHeaderNamesAsKeys()
    {
        $message = (new Response())
            ->withHeader('Set-Cookie', 'cat=Molly')
            ->withAddedHeader('Set-Cookie', 'dog=Bear')
            ->withHeader('Content-type', 'application/json');

        $headers = [];
        foreach ($message->getHeaders() as $key => $values) {
            $headers[] = $key;
        }

        $expected = ['Content-type', 'Set-Cookie'];
        $countUnmatched
            = count(array_diff($expected, $headers))
            + count(array_diff($headers, $expected));
        $this->assertEquals(0, $countUnmatched);
    }

    public function testGetHeadersReturnOriginalHeaderNamesAndValues()
    {
        $message = (new Response())
            ->withHeader('Set-Cookie', 'cat=Molly')
            ->withAddedHeader('Set-Cookie', 'dog=Bear')
            ->withHeader('Content-type', 'application/json');

        $headers = [];

        foreach ($message->getHeaders() as $key => $values) {
            foreach ($values as $value) {
                if (isset($headers[$key])) {
                    $headers[$key][] = $value;
                } else {
                    $headers[$key] = [$value];
                }
            }
        }

        $expected = [
            'Set-Cookie' => ['cat=Molly', 'dog=Bear'],
            'Content-type' => ['application/json']
        ];

        $this->assertEquals($expected, $headers);
    }

    // ------------------------------------------------------------------------
    // Body

    public function testGetBodyReturnsEmptyStreamByDefault()
    {
        $message = new Response();
        $this->assertEquals('', (string) $message->getBody());
    }

    public function testGetBodyReturnsAttachedStream()
    {
        $stream = new Stream('Hello, world!');

        $message = (new Response())
            ->withBody($stream);
        $this->assertSame($stream, $message->getBody());
    }
}
