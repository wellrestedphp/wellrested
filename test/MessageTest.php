<?php

namespace pjdietz\WellRESTed\Test;

/**
 * @covers pjdietz\WellRESTed\Message
 */
class MessageTest extends \PHPUnit_Framework_TestCase
{
    public function testSetsBody()
    {
        $message = $this->getMockForAbstractClass("\\pjdietz\\WellRESTed\\Message");
        $body = "This is the body";
        $message->setBody($body);
        $this->assertEquals($body, $message->getBody());
    }

    public function testBodyIsNullByDefault()
    {
        $message = $this->getMockForAbstractClass("\\pjdietz\\WellRESTed\\Message");
        $this->assertNull($message->getBody());
    }

    /**
     * @dataProvider headerProvider
     */
    public function testSetsHeader($headerKey, $headerValue, $badCapsKey)
    {
        $message = $this->getMockForAbstractClass("\\pjdietz\\WellRESTed\\Message");
        $message->setHeader($headerKey, $headerValue);
        $this->assertEquals($headerValue, $message->getHeader($badCapsKey));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testUpdatesHeader($headerKey, $headerValue, $testName)
    {
        $message = $this->getMockForAbstractClass("\\pjdietz\\WellRESTed\\Message");
        $message->setHeader($headerKey, $headerValue);
        $newValue = "newvalue";
        $message->setHeader($testName, "newvalue");
        $this->assertEquals($newValue, $message->getHeader($testName));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testNonsetHeaderIsNull()
    {
        $message = $this->getMockForAbstractClass("\\pjdietz\\WellRESTed\\Message");
        $this->assertNull($message->getHeader("no-header"));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testUnsetHeaderIsNull($headerKey, $headerValue, $testName)
    {
        $message = $this->getMockForAbstractClass("\\pjdietz\\WellRESTed\\Message");
        $message->setHeader($headerKey, $headerValue);
        $message->unsetHeader($testName);
        $this->assertNull($message->getHeader($headerKey));
    }

    /**
     * @dataProvider headerProvider
     */
    public function testChecksIfHeaderIsSet($headerKey, $headerValue, $testName)
    {
        $message = $this->getMockForAbstractClass("\\pjdietz\\WellRESTed\\Message");
        $message->setHeader($headerKey, $headerValue);
        $this->assertTrue($message->issetHeader($testName));
    }

    public function headerProvider()
    {
        return [
            ["Accept-Charset", "utf-8", "accept-charset"],
            ["Accept-Encoding", "gzip, deflate", "ACCEPT-ENCODING"],
            ["Cache-Control", "no-cache", "Cache-Control"],
        ];
    }

    public function testReturnsListOfHeaders()
    {
        $message = $this->getMockForAbstractClass("\\pjdietz\\WellRESTed\\Message");
        $headers = $this->headerProvider();
        foreach ($headers as $header) {
            $message->setHeader($header[0], $header[1]);
        }
        $this->assertEquals(count($headers), count($message->getHeaders()));
    }

}
