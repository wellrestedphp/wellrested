<?php

namespace WellRESTed\Test\Stream;

use WellRESTed\Stream\Stream;

/**
 * @uses WellRESTed\Stream\Stream
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
     * @covers WellRESTed\Stream\Stream::__construct()
     */
    public function testCreatesInstanceWithStreamResource()
    {
        $stream = new Stream($this->resource);
        $this->assertNotNull($stream);
    }

    public function testCreatesInstanceWithString()
    {
        $stream = new Stream("Hello, world!");
        $this->assertNotNull($stream);
    }

    /**
     * @covers WellRESTed\Stream\Stream::__construct()
     * @expectedException \InvalidArgumentException
     * @dataProvider invalidResourceProvider
     */
    public function testThrowsExceptiondWithInvalidResource($resource)
    {
        new Stream($resource);
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
     * @covers WellRESTed\Stream\Stream::__toString()
     */
    public function testCastsToString()
    {
        $stream = new Stream($this->resource);
        $this->assertEquals($this->content, (string) $stream);
    }

    /**
     * @covers WellRESTed\Stream\Stream::close()
     */
    public function testClosesHandle()
    {
        $stream = new Stream($this->resource);
        $stream->close();
        $this->assertFalse(is_resource($this->resource));
    }

    /**
     * @covers WellRESTed\Stream\Stream::detach()
     */
    public function testDetachReturnsHandle()
    {
        $stream = new Stream($this->resource);
        $this->assertSame($this->resource, $stream->detach());
    }

    /**
     * @covers WellRESTed\Stream\Stream::detach()
     */
    public function testDetachUnsetsInstanceVariable()
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->assertNull($stream->detach());
    }

    /**
     * @covers WellRESTed\Stream\Stream::getSize
     */
    public function testReturnsSize()
    {
        $stream = new Stream($this->resource);
        $this->assertEquals(strlen($this->content), $stream->getSize());
    }

    /**
     * @covers WellRESTed\Stream\Stream::tell
     */
    public function testTellReturnsHandlePosition()
    {
        $stream = new Stream($this->resource);
        fseek($this->resource, 10);
        $this->assertEquals(10, $stream->tell());
    }

    /**
     * @covers WellRESTed\Stream\Stream::eof
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
     * @covers WellRESTed\Stream\Stream::isSeekable
     */
    public function testReadsSeekableStatusFromMetadata()
    {
        $stream = new Stream($this->resource);
        $metadata = stream_get_meta_data($this->resource);
        $seekable = $metadata["seekable"] == 1;
        $this->assertEquals($seekable, $stream->isSeekable());
    }

    /**
     * @covers WellRESTed\Stream\Stream::seek
     */
    public function testSeeksToPosition()
    {
        $stream = new Stream($this->resource);
        $stream->seek(10);
        $this->assertEquals(10, ftell($this->resource));
    }

    /**
     * @covers WellRESTed\Stream\Stream::rewind
     */
    public function testRewindReturnsToBeginning()
    {
        $stream = new Stream($this->resource);
        $stream->seek(10);
        $stream->rewind();
        $this->assertEquals(0, ftell($this->resource));
    }

    /**
     * @covers WellRESTed\Stream\Stream::write
     */
    public function testWritesToHandle()
    {
        $message = "\nThis is a stream.";
        $stream = new Stream($this->resource);
        $stream->write($message);
        $this->assertEquals($this->content . $message, (string) $stream);
    }

    /**
     * @covers WellRESTed\Stream\Stream::read
     */
    public function testReadsFromStream()
    {
        $stream = new Stream($this->resource);
        $stream->seek(7);
        $string = $stream->read(5);
        $this->assertEquals("world", $string);
    }

    /**
     * @covers WellRESTed\Stream\Stream::getContents
     */
    public function testReadsToEnd()
    {
        $stream = new Stream($this->resource);
        $stream->seek(7);
        $string = $stream->getContents();
        $this->assertEquals("world!", $string);
    }

    /**
     * @covers WellRESTed\Stream\Stream::getMetadata
     */
    public function testReturnsMetadataArray()
    {
        $stream = new Stream($this->resource);
        $this->assertEquals(stream_get_meta_data($this->resource), $stream->getMetadata());
    }

    /**
     * @covers WellRESTed\Stream\Stream::getMetadata
     */
    public function testReturnsMetadataItem()
    {
        $stream = new Stream($this->resource);
        $metadata = stream_get_meta_data($this->resource);
        $this->assertEquals($metadata["mode"], $stream->getMetadata("mode"));
    }

    /**
     * @covers WellRESTed\Stream\Stream::isReadable
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
     * @covers WellRESTed\Stream\Stream::isWritable
     * @dataProvider modeProvider
     */
    public function testReturnsIsWritableForWritableStreams($mode, $readable, $writeable)
    {
        $tmp = tempnam(sys_get_temp_dir(), "php");
        if ($mode[0] === "x") {
            unlink($tmp);
        }
        $resource = fopen($tmp, $mode);
        $stream = new Stream($resource);
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
