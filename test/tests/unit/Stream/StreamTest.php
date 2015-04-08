<?php

namespace WellRESTed\Test\Stream;

use WellRESTed\Stream\Stream;

class StreamTest extends \PHPUnit_Framework_TestCase
{
    private $handle;
    private $content = "Hello, world!";

    public function setUp()
    {
        $this->handle = fopen("php://memory", "w+");
        fwrite($this->handle, $this->content);
    }

    public function tearDown()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * @covers WellRESTed\Stream\Stream::__construct()
     */
    public function testCreatesInstance()
    {
        $stream = new Stream($this->handle);
        $this->assertNotNull($stream);
    }

    /**
     * @covers WellRESTed\Stream\Stream::__construct()
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptiondWithoutHandle()
    {
        new Stream(null);
    }

    /**
     * @covers WellRESTed\Stream\Stream::__toString()
     * @uses WellRESTed\Stream\Stream
     */
    public function testCastsToString()
    {
        $content = "Hello, world!";

        $h = fopen("php://memory", "w+");
        fwrite($h, $content);
        rewind($h);

        $stream = new Stream($h);
        $this->assertEquals($content, (string) $stream);
    }

    /**
     * @covers WellRESTed\Stream\Stream::__toString()
     * @uses WellRESTed\Stream\Stream
     */
    public function testRewindsBeforeCastingToString()
    {
        $content = "Hello, world!";

        $h = fopen("php://memory", "w+");
        fwrite($h, $content);

        $stream = new Stream($h);
        $this->assertEquals($content, (string) $stream);
    }

    /**
     * @covers WellRESTed\Stream\Stream::close()
     * @uses WellRESTed\Stream\Stream
     */
    public function testClosesHandle()
    {
        $stream = new Stream($this->handle);
        $stream->close();
        $this->assertFalse(is_resource($this->handle));
    }

    /**
     * @covers WellRESTed\Stream\Stream::detach()
     * @uses WellRESTed\Stream\Stream
     */
    public function testDetachReturnsHandle()
    {
        $stream = new Stream($this->handle);
        $h = $stream->detach();
        $this->assertSame($this->handle, $h);
    }

    /**
     * @covers WellRESTed\Stream\Stream::detach()
     * @uses WellRESTed\Stream\Stream
     */
    public function testDetachUnsetsInstanceVariable()
    {
        $stream = new Stream($this->handle);
        $stream->detach();
        $this->assertNull($stream->detach());
    }

    /**
     * @covers WellRESTed\Stream\Stream::getSize
     * @uses WellRESTed\Stream\Stream
     */
    public function testReturnsSize()
    {
        $stream = new Stream($this->handle);
        $this->assertEquals(strlen($this->content), $stream->getSize());
    }

    /**
     * @covers WellRESTed\Stream\Stream::tell
     * @uses WellRESTed\Stream\Stream
     */
    public function testTellReturnsHandlePosition()
    {
        $stream = new Stream($this->handle);
        fseek($this->handle, 10);
        $this->assertEquals(10, $stream->tell());
    }

    /**
     * @covers WellRESTed\Stream\Stream::eof
     * @uses WellRESTed\Stream\Stream
     */
    public function testReturnsOef()
    {
        $stream = new Stream($this->handle);
        $stream->rewind();
        $this->assertFalse($stream->eof());
        $stream->getContents();
        $this->assertTrue($stream->eof());
    }

    /**
     * @covers WellRESTed\Stream\Stream::isSeekable
     * @uses WellRESTed\Stream\Stream
     */
    public function testReadsSeekableStatusFromMetadata()
    {
        $stream = new Stream($this->handle);
        $metadata = stream_get_meta_data($this->handle);
        $seekable = $metadata["seekable"] == 1;
        $this->assertEquals($seekable, $stream->isSeekable());
    }

    /**
     * @covers WellRESTed\Stream\Stream::seek
     * @uses WellRESTed\Stream\Stream
     */
    public function testSeeksToPosition()
    {
        $stream = new Stream($this->handle);
        $stream->seek(10);
        $this->assertEquals(10, ftell($this->handle));
    }

    /**
     * @covers WellRESTed\Stream\Stream::rewind
     * @uses WellRESTed\Stream\Stream
     */
    public function testRewindReturnsToBeginning()
    {
        $stream = new Stream($this->handle);
        $stream->seek(10);
        $stream->rewind();
        $this->assertEquals(0, ftell($this->handle));
    }

    /**
     * @covers WellRESTed\Stream\Stream::write
     * @uses WellRESTed\Stream\Stream
     */
    public function testWritesToHandle()
    {
        $message = "\nThis is a stream.";
        $stream = new Stream($this->handle);
        $stream->write($message);
        $this->assertEquals($this->content . $message, (string) $stream);
    }

    /**
     * @covers WellRESTed\Stream\Stream::read
     * @uses WellRESTed\Stream\Stream
     */
    public function testReadsFromStream()
    {
        $stream = new Stream($this->handle);
        $stream->seek(7);
        $string = $stream->read(5);
        $this->assertEquals("world", $string);
    }

    /**
     * @covers WellRESTed\Stream\Stream::getContents
     * @uses WellRESTed\Stream\Stream
     */
    public function testReadsToEnd()
    {
        $stream = new Stream($this->handle);
        $stream->seek(7);
        $string = $stream->getContents();
        $this->assertEquals("world!", $string);
    }

    /**
     * @covers WellRESTed\Stream\Stream::getMetadata
     * @uses WellRESTed\Stream\Stream
     */
    public function testReturnsMetadataArray()
    {
        $stream = new Stream($this->handle);
        $this->assertEquals(stream_get_meta_data($this->handle), $stream->getMetadata());
    }

    /**
     * @covers WellRESTed\Stream\Stream::getMetadata
     * @uses WellRESTed\Stream\Stream
     */
    public function testReturnsMetadataItem()
    {
        $stream = new Stream($this->handle);
        $metadata = stream_get_meta_data($this->handle);
        $this->assertEquals($metadata["mode"], $stream->getMetadata("mode"));
    }

    /**
     * @covers WellRESTed\Stream\Stream::isReadable
     * @uses WellRESTed\Stream\Stream
     * @dataProvider modeProvider
     */
    public function testReturnsIsReadableForReadableStreams($mode, $readable, $writeable)
    {
        $tmp = tempnam(sys_get_temp_dir(), "php");
        if ($mode[0] === "x") {
            unlink($tmp);
        }
        fclose($this->handle);
        $this->handle = fopen($tmp, $mode);
        $stream = new Stream($this->handle);
        $this->assertEquals($readable, $stream->isReadable());
    }

    /**
     * @covers WellRESTed\Stream\Stream::isWritable
     * @uses WellRESTed\Stream\Stream
     * @dataProvider modeProvider
     */
    public function testReturnsIsWritableForWritableStreams($mode, $readable, $writeable)
    {
        $tmp = tempnam(sys_get_temp_dir(), "php");
        if ($mode[0] === "x") {
            unlink($tmp);
        }
        fclose($this->handle);
        $this->handle = fopen($tmp, $mode);
        $stream = new Stream($this->handle);
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
