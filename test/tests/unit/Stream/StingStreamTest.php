<?php

namespace WellRESTed\Test\Stream;

use WellRESTed\Stream\StringStream;

/**
 * @covers WellRESTed\Stream\StringStream
 * @uses WellRESTed\Stream\Stream
 */
class StringStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatesInstance()
    {
        $stream = new StringStream();
        $this->assertNotNull($stream);
    }

    public function testCreatesInstanceWithString()
    {
        $message = "Hello, World!";
        $stream = new StringStream($message);
        $this->assertEquals($message, (string) $stream);
    }

    public function testAllowsWriting()
    {
        $message = "Hello, World!";
        $stream = new StringStream();
        $stream->write("Hello,");
        $stream->write(" World!");
        $this->assertEquals($message, (string) $stream);
    }
}
