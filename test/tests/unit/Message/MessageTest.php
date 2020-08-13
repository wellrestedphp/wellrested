<?php

namespace WellRESTed\Message;

use InvalidArgumentException;
use WellRESTed\Test\TestCase;

class MessageTest extends TestCase
{
    public function testSetsHeadersOnConstruction(): void
    {
        $headers = ['X-foo' => ['bar', 'baz']];
        $message = new Response(200, $headers);
        $this->assertEquals(['bar', 'baz'], $message->getHeader('X-foo'));
    }

    public function testSetsBodyOnConstruction(): void
    {
        $body = new Stream('Hello, world');
        $message = new Response(200, [], $body);
        $this->assertSame($body, $message->getBody());
    }

    public function testCloneMakesDeepCopyOfHeaders(): void
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

    // -------------------------------------------------------------------------
    // Protocol Version

    public function testGetProtocolVersionReturnsProtocolVersion1Point1ByDefault(): void
    {
        $message = new Response();
        $this->assertEquals('1.1', $message->getProtocolVersion());
    }

    public function testGetProtocolVersionReturnsProtocolVersion(): void
    {
        $message = (new Response())
            ->withProtocolVersion('1.0');
        $this->assertEquals('1.0', $message->getProtocolVersion());
    }

    public function testGetProtocolVersionReplacesProtocolVersion(): void
    {
        $message = (new Response())
            ->withProtocolVersion('1.0');
        $this->assertEquals('1.0', $message->getProtocolVersion());
    }

    // -------------------------------------------------------------------------
    // Headers

    /**
     * @dataProvider validHeaderValueProvider
     * @param array $expected
     * @param mixed $value
     */
    public function testWithHeaderReplacesHeader(array $expected, $value): void
    {
        $message = (new Response())
            ->withHeader('X-foo', 'Original value')
            ->withHeader('X-foo', $value);
        $this->assertEquals($expected, $message->getHeader('X-foo'));
    }

    public function validHeaderValueProvider(): array
    {
        return [
            [['0'], 0],
            [['molly','bear'],['molly','bear']]
        ];
    }

    /**
     * @dataProvider invalidHeaderProvider
     * @param mixed $name
     * @param mixed $value
     */
    public function testWithHeaderThrowsExceptionWithInvalidArgument($name, $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Response())
            ->withHeader($name, $value);
    }

    public function invalidHeaderProvider(): array
    {
        return [
            [0, 1024],
            ['Content-length', false],
            ['Content-length', [false]]
        ];
    }

    public function testWithAddedHeaderSetsHeader(): void
    {
        $message = (new Response())
            ->withAddedHeader('Content-type', 'application/json');
        $this->assertEquals(['application/json'], $message->getHeader('Content-type'));
    }

    public function testWithAddedHeaderAppendsValue(): void
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

    public function testWithoutHeaderRemovesHeader(): void
    {
        $message = (new Response())
            ->withHeader('Content-type', 'application/json')
            ->withoutHeader('Content-type');
        $this->assertFalse($message->hasHeader('Content-type'));
    }

    public function testGetHeaderReturnsEmptyArrayForUnsetHeader(): void
    {
        $message = new Response();
        $this->assertEquals([], $message->getHeader('X-name'));
    }

    public function testGetHeaderReturnsSingleHeader(): void
    {
        $message = (new Response())
            ->withAddedHeader('Content-type', 'application/json');
        $this->assertEquals(['application/json'], $message->getHeader('Content-type'));
    }

    public function testGetHeaderReturnsMultipleValuesForHeader(): void
    {
        $message = (new Response())
            ->withAddedHeader('X-name', 'cat=Molly')
            ->withAddedHeader('X-name', 'dog=Bear');
        $this->assertEquals(['cat=Molly', 'dog=Bear'], $message->getHeader('X-name'));
    }

    public function testGetHeaderLineReturnsEmptyStringForUnsetHeader(): void
    {
        $message = new Response();
        $this->assertSame('', $message->getHeaderLine('X-not-set'));
    }

    public function testGetHeaderLineReturnsMultipleHeadersJoinedByCommas(): void
    {
        $message = (new Response())
            ->withAddedHeader('X-name', 'cat=Molly')
            ->withAddedHeader('X-name', 'dog=Bear');
        $this->assertEquals('cat=Molly, dog=Bear', $message->getHeaderLine('X-name'));
    }

    public function testHasHeaderReturnsTrueWhenHeaderIsSet(): void
    {
        $message = (new Response())
            ->withHeader('Content-type', 'application/json');
        $this->assertTrue($message->hasHeader('Content-type'));
    }

    public function testHasHeaderReturnsFalseWhenHeaderIsNotSet(): void
    {
        $message = new Response();
        $this->assertFalse($message->hasHeader('Content-type'));
    }

    public function testGetHeadersReturnOriginalHeaderNamesAsKeys(): void
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

    public function testGetHeadersReturnOriginalHeaderNamesAndValues(): void
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

    // -------------------------------------------------------------------------
    // Body

    public function testGetBodyReturnsEmptyStreamByDefault(): void
    {
        $message = new Response();
        $this->assertEquals('', (string) $message->getBody());
    }

    public function testGetBodyReturnsAttachedStream(): void
    {
        $stream = new Stream('Hello, world!');

        $message = (new Response())
            ->withBody($stream);
        $this->assertSame($stream, $message->getBody());
    }
}
