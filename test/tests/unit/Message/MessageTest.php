<?php

namespace WellRESTed\Test\Unit\Message;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testCreatesInstance()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $this->assertNotNull($message);
    }

    /**
     * @covers WellRESTed\Message\Message::getProtocolVersion
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testReturnsProtocolVersion11ByDefault()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $this->assertEquals("1.1", $message->getProtocolVersion());
    }

    /**
     * @covers WellRESTed\Message\Message::getProtocolVersion
     * @uses WellRESTed\Message\Message::withProtocolVersion
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testReturnsProtocolVersion()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withProtocolVersion("1.0");
        $this->assertEquals("1.0", $message->getProtocolVersion());
    }

    /**
     * @covers WellRESTed\Message\Message::withProtocolVersion
     * @uses WellRESTed\Message\Message::getProtocolVersion
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testReplacesProtocolVersion()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withProtocolVersion("1.0");
        $this->assertEquals("1.0", $message->getProtocolVersion());
    }

    /**
     * @covers WellRESTed\Message\Message::withHeader
     * @uses WellRESTed\Message\Message::getHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithHeaderSetsHeader()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withHeader("Content-type", "application/json");
        $this->assertEquals("application/json", $message->getHeader("Content-type"));
    }

    /**
     * @covers WellRESTed\Message\Message::withHeader
     * @uses WellRESTed\Message\Message::getHeaderLines
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithHeaderReplacesValue()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withHeader("Set-Cookie", "cat=Molly");
        $message = $message->withHeader("Set-Cookie", "dog=Bear");
        $cookies = $message->getHeaderLines("Set-Cookie");
        $this->assertNotContains("cat=Molly", $cookies);
        $this->assertContains("dog=Bear", $cookies);
    }

    /**
     * @covers WellRESTed\Message\Message::withAddedHeader
     * @uses WellRESTed\Message\Message::getHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithAddedHeaderSetsHeader()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withAddedHeader("Content-type", "application/json");
        $this->assertEquals("application/json", $message->getHeader("Content-type"));
    }

    /**
     * @covers WellRESTed\Message\Message::withAddedHeader
     * @uses WellRESTed\Message\Message::getHeaderLines
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithAddedHeaderAppendsValue()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withAddedHeader("Set-Cookie", "cat=Molly");
        $message = $message->withAddedHeader("Set-Cookie", "dog=Bear");
        $cookies = $message->getHeaderLines("Set-Cookie");
        $this->assertContains("cat=Molly", $cookies);
        $this->assertContains("dog=Bear", $cookies);
    }

    /**
     * @covers WellRESTed\Message\Message::withoutHeader
     * @uses WellRESTed\Message\Message::withHeader
     * @uses WellRESTed\Message\Message::hasHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithoutHeaderRemovesHeader()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withHeader("Content-type", "application/json");
        $message = $message->withoutHeader("Content-type");
        $this->assertFalse($message->hasHeader("Content-type"));
    }

    /**
     * @covers WellRESTed\Message\Message::getHeader
     * @uses WellRESTed\Message\Message::withAddedHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeaderReturnsSingleHeader()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withAddedHeader("Content-type", "application/json");
        $this->assertEquals("application/json", $message->getHeader("Content-type"));
    }

    /**
     * @covers WellRESTed\Message\Message::getHeader
     * @uses WellRESTed\Message\Message::withAddedHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeaderReturnsMultipleHeadersJoinedByCommas()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withAddedHeader("X-name", "cat=Molly");
        $message = $message->withAddedHeader("X-name", "dog=Bear");
        $this->assertEquals("cat=Molly, dog=Bear", $message->getHeader("X-name"));
    }

    /**
     * @covers WellRESTed\Message\Message::getHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeaderReturnsNullForUnsetHeader()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $this->assertNull($message->getHeader("X-not-set"));
    }

    /**
     * @covers WellRESTed\Message\Message::getHeaderLines
     * @uses WellRESTed\Message\Message::withAddedHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeaderLinesReturnsMultipleValuesForHeader()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withAddedHeader("X-name", "cat=Molly");
        $message = $message->withAddedHeader("X-name", "dog=Bear");
        $this->assertEquals(["cat=Molly", "dog=Bear"], $message->getHeaderLines("X-name"));
    }

    /**
     * @covers WellRESTed\Message\Message::getHeaderLines
     * @uses WellRESTed\Message\Message::withAddedHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeaderLinesReturnsEmptyArrayForUnsetHeader()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $this->assertEquals([], $message->getHeaderLines("X-name"));
    }

    /**
     * @covers WellRESTed\Message\Message::hasHeader
     * @uses WellRESTed\Message\Message::withHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testHasHeaderReturnsTrueWhenHeaderIsSet()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withHeader("Content-type", "application/json");
        $this->assertTrue($message->hasHeader("Content-type"));
    }

    /**
     * @covers WellRESTed\Message\Message::hasHeader
     * @uses WellRESTed\Message\Message::withHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testHasHeaderReturnsFalseWhenHeaderIsNotSet()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $this->assertFalse($message->hasHeader("Content-type"));
    }

    /**
     * @covers WellRESTed\Message\Message::getHeaders
     * @uses WellRESTed\Message\Message::withHeader
     * @uses WellRESTed\Message\Message::withAddedHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeadersReturnOriginalHeaderNamesAsKeys()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withHeader("Set-Cookie", "cat=Molly");
        $message = $message->withAddedHeader("Set-Cookie", "dog=Bear");
        $message = $message->withHeader("Content-type", "application/json");

        $headers = [];
        foreach ($message->getHeaders() as $key => $values) {
            $headers[] = $key;
        }

        $expected = ["Content-type", "Set-Cookie"];
        $this->assertEquals(0, count(array_diff($expected, $headers)));
        $this->assertEquals(0, count(array_diff($headers, $expected)));
    }

    /**
     * @covers WellRESTed\Message\Message::getHeaders
     * @uses WellRESTed\Message\Message::withHeader
     * @uses WellRESTed\Message\Message::withAddedHeader
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetHeadersReturnOriginalHeaderNamesAndValues()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
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

    /**
     * @covers WellRESTed\Message\Message::getBody
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetBodyReturnsNullByDefalt()
    {
        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $this->assertNull($message->getBody());
    }

    /**
     * @covers WellRESTed\Message\Message::getBody
     * @covers WellRESTed\Message\Message::withBody
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetBodyReturnsAttachedStream()
    {
        $stream = $this->prophesize("\\Psr\\Http\\Message\\StreamableInterface");
        $stream = $stream->reveal();

        $message = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message = $message->withBody($stream);
        $this->assertSame($stream, $message->getBody());
    }

    /**
     * @covers WellRESTed\Message\Message::__clone
     * @uses WellRESTed\Message\Message::__construct
     * @uses WellRESTed\Message\Message::withHeader
     * @uses WellRESTed\Message\Message::getHeader
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testCloneMakesDeepCopyOfHeaders()
    {
        $message1 = $this->getMockForAbstractClass("\\WellRESTed\\Message\\Message");
        $message1 = $message1->withHeader("Content-type", "text/plain");
        $message2 = $message1->withHeader("Content-type", "application/json");
        $this->assertEquals("text/plain", $message1->getHeader("Content-type"));
        $this->assertEquals("application/json", $message2->getHeader("Content-type"));
    }
}
