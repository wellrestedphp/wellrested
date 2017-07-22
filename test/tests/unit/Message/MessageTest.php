<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Test\TestCase;

class MessageTest extends TestCase
{
    public function testSetsHeadersOnConstruction()
    {
        $headers = ["X-foo" => ["bar", "baz"]];
        $body = null;
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message', [$headers, $body]);
        $this->assertEquals(["bar", "baz"], $message->getHeader("X-foo"));
    }

    public function testSetsBodyOnConstruction()
    {
        $headers = null;
        $body = $this->prophesize('\Psr\Http\Message\StreamInterface');
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message', [$headers, $body->reveal()]);
        $this->assertSame($body->reveal(), $message->getBody());
    }

   public function testCloneMakesDeepCopyOfHeaders()
    {
        $message1 = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message1 = $message1->withHeader("Content-type", "text/plain");
        $message2 = $message1->withHeader("Content-type", "application/json");
        $this->assertNotEquals($message1->getHeader("Content-type"), $message2->getHeader("Content-type"));
    }

    // ------------------------------------------------------------------------
    // Protocol Version

    public function testGetProtocolVersionReturnsProtocolVersion1Point1ByDefault()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $this->assertEquals("1.1", $message->getProtocolVersion());
    }

    public function testGetProtocolVersionReturnsProtocolVersion()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withProtocolVersion("1.0");
        $this->assertEquals("1.0", $message->getProtocolVersion());
    }

    public function testGetProtocolVersionReplacesProtocolVersion()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withProtocolVersion("1.0");
        $this->assertEquals("1.0", $message->getProtocolVersion());
    }

    // ------------------------------------------------------------------------
    // Headers

    /** @dataProvider validHeaderValueProvider */
    public function testWithHeaderReplacesHeader($expected, $value)
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withHeader("X-foo", "Original value");
        $message = $message->withHeader("X-foo", $value);
        $this->assertEquals($expected, $message->getHeader("X-foo"));
    }

    public function validHeaderValueProvider()
    {
        return [
            [["0"], 0],
            [["molly","bear"],["molly","bear"]]
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidHeaderProvider
     */
    public function testWithHeaderThrowsExceptionWithInvalidArgument($name, $value)
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message->withHeader($name, $value);
    }

    public function invalidHeaderProvider()
    {
        return [
            [0, 1024],
            ["Content-length", false],
            ["Content-length", [false]]
        ];
    }

    public function testWithAddedHeaderSetsHeader()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withAddedHeader("Content-type", "application/json");
        $this->assertEquals(["application/json"], $message->getHeader("Content-type"));
    }

    public function testWithAddedHeaderAppendsValue()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withAddedHeader("Set-Cookie", ["cat=Molly"]);
        $message = $message->withAddedHeader("Set-Cookie", ["dog=Bear"]);
        $cookies = $message->getHeader("Set-Cookie");
        $this->assertTrue(in_array("cat=Molly", $cookies) && in_array("dog=Bear", $cookies));
    }

    public function testWithoutHeaderRemovesHeader()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withHeader("Content-type", "application/json");
        $message = $message->withoutHeader("Content-type");
        $this->assertFalse($message->hasHeader("Content-type"));
    }

    public function testGetHeaderReturnsEmptyArrayForUnsetHeader()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $this->assertEquals([], $message->getHeader("X-name"));
    }

    public function testGetHeaderReturnsSingleHeader()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withAddedHeader("Content-type", "application/json");
        $this->assertEquals(["application/json"], $message->getHeader("Content-type"));
    }

    public function testGetHeaderReturnsMultipleValuesForHeader()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withAddedHeader("X-name", "cat=Molly");
        $message = $message->withAddedHeader("X-name", "dog=Bear");
        $this->assertEquals(["cat=Molly", "dog=Bear"], $message->getHeader("X-name"));
    }

    public function testGetHeaderLineReturnsEmptyStringForUnsetHeader()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $this->assertSame("", $message->getHeaderLine("X-not-set"));
    }

    public function testGetHeaderLineReturnsMultipleHeadersJoinedByCommas()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withAddedHeader("X-name", "cat=Molly");
        $message = $message->withAddedHeader("X-name", "dog=Bear");
        $this->assertEquals("cat=Molly, dog=Bear", $message->getHeaderLine("X-name"));
    }

    public function testHasHeaderReturnsTrueWhenHeaderIsSet()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withHeader("Content-type", "application/json");
        $this->assertTrue($message->hasHeader("Content-type"));
    }

    public function testHasHeaderReturnsFalseWhenHeaderIsNotSet()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $this->assertFalse($message->hasHeader("Content-type"));
    }

    public function testGetHeadersReturnOriginalHeaderNamesAsKeys()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withHeader("Set-Cookie", "cat=Molly");
        $message = $message->withAddedHeader("Set-Cookie", "dog=Bear");
        $message = $message->withHeader("Content-type", "application/json");

        $headers = [];
        foreach ($message->getHeaders() as $key => $values) {
            $headers[] = $key;
        }

        $expected = ["Content-type", "Set-Cookie"];
        $countUnmatched = count(array_diff($expected, $headers)) + count(array_diff($headers, $expected));
        $this->assertEquals(0, $countUnmatched);
    }

    public function testGetHeadersReturnOriginalHeaderNamesAndValues()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withHeader("Set-Cookie", "cat=Molly");
        $message = $message->withAddedHeader("Set-Cookie", "dog=Bear");
        $message = $message->withHeader("Content-type", "application/json");

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
            "Set-Cookie" => ["cat=Molly", "dog=Bear"],
            "Content-type" => ["application/json"]
        ];

        $this->assertEquals($expected, $headers);
    }

    // ------------------------------------------------------------------------
    // Body

    public function testGetBodyReturnsEmptyStreamByDefault()
    {
        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $this->assertEquals("", (string) $message->getBody());
    }

    public function testGetBodyReturnsAttachedStream()
    {
        $stream = $this->prophesize('\Psr\Http\Message\StreamInterface');
        $stream = $stream->reveal();

        $message = $this->getMockForAbstractClass('\WellRESTed\Message\Message');
        $message = $message->withBody($stream);
        $this->assertSame($stream, $message->getBody());
    }
}
