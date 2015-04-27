<?php

namespace WellRESTed\Test\Message;

use WellRESTed\Message\Stream;

/**
 * @uses WellRESTed\Message\Stream
 */
class StreamTest extends \PHPUnit_Framework_TestCase
{
    private $resource;
    private $content = "Hello, world!";

    public function setUp()
    {
        $this->resource = fopen("php://memory", "w+");
        fwrite($this->resource, $this->content);
    }

    public function tearDown()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }

    /**
     * @covers WellRESTed\Message\Stream::__construct()
     */
    public function testCreatesInstanceWithStreamResource()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $this->assertNotNull($stream);
    }

    public function testCreatesInstanceWithString()
    {
        $stream = new \WellRESTed\Message\Stream("Hello, world!");
        $this->assertNotNull($stream);
    }

    /**
     * @covers WellRESTed\Message\Stream::__construct()
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidResourceProvider
     */
    public function testThrowsExceptiondWithInvalidResource($resource)
    {
        new \WellRESTed\Message\Stream($resource);
    }

    public function invalidResourceProvider()
    {
        return [
            [null],
            [true],
            [4],
            [[]]
        ];
    }

    /**
     * @covers WellRESTed\Message\Stream::__toString()
     */
    public function testCastsToString()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $this->assertEquals($this->content, (string) $stream);
    }

    /**
     * @covers WellRESTed\Message\Stream::close()
     */
    public function testClosesHandle()
    {
        $stream = new Stream($this->resource);
        $stream->close();
        $this->assertFalse(is_resource($this->resource));
    }

    /**
     * @covers WellRESTed\Message\Stream::detach()
     */
    public function testDetachReturnsHandle()
    {
        $stream = new Stream($this->resource);
        $this->assertSame($this->resource, $stream->detach());
    }

    /**
     * @covers WellRESTed\Message\Stream::detach()
     */
    public function testDetachUnsetsInstanceVariable()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $stream->detach();
        $this->assertNull($stream->detach());
    }

    /**
     * @covers WellRESTed\Message\Stream::getSize
     */
    public function testReturnsSize()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $this->assertEquals(strlen($this->content), $stream->getSize());
    }

    /**
     * @covers WellRESTed\Message\Stream::tell
     */
    public function testTellReturnsHandlePosition()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        fseek($this->resource, 10);
        $this->assertEquals(10, $stream->tell());
    }

    /**
     * @covers WellRESTed\Message\Stream::eof
     */
    public function testReturnsOef()
    {
        $stream = new Stream($this->resource);
        $stream->rewind();
        $this->assertFalse($stream->eof());
        $stream->getContents();
        $this->assertTrue($stream->eof());
    }

    /**
     * @covers WellRESTed\Message\Stream::isSeekable
     */
    public function testReadsSeekableStatusFromMetadata()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $metadata = stream_get_meta_data($this->resource);
        $seekable = $metadata["seekable"] == 1;
        $this->assertEquals($seekable, $stream->isSeekable());
    }

    /**
     * @covers WellRESTed\Message\Stream::seek
     */
    public function testSeeksToPosition()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $stream->seek(10);
        $this->assertEquals(10, ftell($this->resource));
    }

    /**
     * @covers WellRESTed\Message\Stream::rewind
     */
    public function testRewindReturnsToBeginning()
    {
        $stream = new Stream($this->resource);
        $stream->seek(10);
        $stream->rewind();
        $this->assertEquals(0, ftell($this->resource));
    }

    /**
     * @covers WellRESTed\Message\Stream::write
     */
    public function testWritesToHandle()
    {
        $message = "\nThis is a stream.";
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $stream->write($message);
        $this->assertEquals($this->content . $message, (string) $stream);
    }

    /**
     * @covers WellRESTed\Message\Stream::write
     * @expectedException \RuntimeException
     */
    public function testThrowsExceptionOnErrorWriting()
    {
        $filename = tempnam(sys_get_temp_dir(), "php");
        $handle = fopen($filename, "r");
        $stream = new \WellRESTed\Message\Stream($handle);
        $stream->write("Hello, world!");
    }

    /**
     * @covers WellRESTed\Message\Stream::read
     * @expectedException \RuntimeException
     */
    public function testThrowsExceptionOnErrorReading()
    {
        $filename = tempnam(sys_get_temp_dir(), "php");
        $handle = fopen($filename, "w");
        $stream = new \WellRESTed\Message\Stream($handle);
        $stream->read(10);
    }

    /**
     * @covers WellRESTed\Message\Stream::read
     */
    public function testReadsFromStream()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $stream->seek(7);
        $string = $stream->read(5);
        $this->assertEquals("world", $string);
    }

    /**
     * @covers WellRESTed\Message\Stream::getContents
     * @expectedException \RuntimeException
     */
    public function testThrowsExceptionOnErrorReadingToEnd()
    {
        $filename = tempnam(sys_get_temp_dir(), "php");
        $handle = fopen($filename, "w");
        $stream = new \WellRESTed\Message\Stream($handle);
        $stream->getContents();
    }

    /**
     * @covers WellRESTed\Message\Stream::getContents
     */
    public function testReadsToEnd()
    {
        $stream = new Stream($this->resource);
        $stream->seek(7);
        $string = $stream->getContents();
        $this->assertEquals("world!", $string);
    }

    /**
     * @covers WellRESTed\Message\Stream::getMetadata
     */
    public function testReturnsMetadataArray()
    {
        $stream = new \WellRESTed\Message\Stream($this->resource);
        $this->assertEquals(stream_get_meta_data($this->resource), $stream->getMetadata());
    }

    /**
     * @covers WellRESTed\Message\Stream::getMetadata
     */
    public function testReturnsMetadataItem()
    {
        $stream = new Stream($this->resource);
        $metadata = stream_get_meta_data($this->resource);
        $this->assertEquals($metadata["mode"], $stream->getMetadata("mode"));
    }

    /**
     * @covers WellRESTed\Message\Stream::isReadable
     * @dataProvider modeProvider
     */
    public function testReturnsIsReadableForReadableStreams($mode, $readable, $writeable)
    {
        $tmp = tempnam(sys_get_temp_dir(), "php");
        if ($mode[0] === "x") {
            unlink($tmp);
        }
        $resource = fopen($tmp, $mode);
        $stream = new Stream($resource);
        $this->assertEquals($readable, $stream->isReadable());
    }

    /**
     * @covers WellRESTed\Message\Stream::isWritable
     * @dataProvider modeProvider
     */
    public function testReturnsIsWritableForWritableStreams($mode, $readable, $writeable)
    {
        $tmp = tempnam(sys_get_temp_dir(), "php");
        if ($mode[0] === "x") {
            unlink($tmp);
        }
        $resource = fopen($tmp, $mode);
        $stream = new \WellRESTed\Message\Stream($resource);
        $this->assertEquals($writeable, $stream->isWritable());
    }

    public function modeProvider()
    {
        return [
            ["r",  true,  false],
            ["r+", true,  true],
            ["w",  false, true],
            ["w+", true,  true],
            ["a",  false, true],
            ["a+", true,  true],
            ["x",  false, true],
            ["x+", true,  true],
            ["c",  false, true],
            ["c+", true,  true]
        ];
    }
}
