<?php

namespace WellRESTed\Test\Stream;

use WellRESTed\Stream\StreamStream;

class StreamStreamTest extends \PHPUnit_Framework_TestCase
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
     * @covers WellRESTed\Stream\StreamStream::__construct()
     */
    public function testCreatesInstance()
    {
        $stream = new StreamStream($this->handle);
        $this->assertNotNull($stream);
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::__construct()
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptiondWithoutHandle()
    {
        new StreamStream(null);
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::__toString()
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testCastsToString()
    {
        $content = "Hello, world!";

        $h = fopen("php://memory", "w+");
        fwrite($h, $content);
        rewind($h);

        $stream = new StreamStream($h);
        $this->assertEquals($content, (string) $stream);
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::__toString()
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testRewindsBeforeCastingToString()
    {
        $content = "Hello, world!";

        $h = fopen("php://memory", "w+");
        fwrite($h, $content);

        $stream = new StreamStream($h);
        $this->assertEquals($content, (string) $stream);
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::close()
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testClosesHandle()
    {
        $stream = new StreamStream($this->handle);
        $stream->close();
        $this->assertFalse(is_resource($this->handle));
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::detach()
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testDetachReturnsHandle()
    {
        $stream = new StreamStream($this->handle);
        $h = $stream->detach();
        $this->assertSame($this->handle, $h);
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::detach()
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testDetachUnsetsInstanceVariable()
    {
        $stream = new StreamStream($this->handle);
        $stream->detach();
        $this->assertNull($stream->detach());
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::getSize
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testReturnsNullForSize()
    {
        $stream = new StreamStream($this->handle);
        $this->assertNull($stream->getSize());
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::tell
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testTellReturnsHandlePosition()
    {
        $stream = new StreamStream($this->handle);
        fseek($this->handle, 10);
        $this->assertEquals(10, $stream->tell());
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::eof
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testReturnsOef()
    {
        $stream = new StreamStream($this->handle);
        $stream->rewind();
        $this->assertFalse($stream->eof());
        $stream->getContents();
        $this->assertTrue($stream->eof());
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::isSeekable
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testReadsSeekableStatusFromMetadata()
    {
        $stream = new StreamStream($this->handle);
        $metadata = stream_get_meta_data($this->handle);
        $seekable = $metadata["seekable"] == 1;
        $this->assertEquals($seekable, $stream->isSeekable());
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::seek
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testSeeksToPosition()
    {
        $stream = new StreamStream($this->handle);
        $stream->seek(10);
        $this->assertEquals(10, ftell($this->handle));
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::rewind
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testRewindReturnsToBeginning()
    {
        $stream = new StreamStream($this->handle);
        $stream->seek(10);
        $stream->rewind();
        $this->assertEquals(0, ftell($this->handle));
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::write
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testWritesToHandle()
    {
        $message = "\nThis is a stream.";
        $stream = new StreamStream($this->handle);
        $stream->write($message);
        $this->assertEquals($this->content . $message, (string) $stream);
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::read
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testReadsFromStream()
    {
        $stream = new StreamStream($this->handle);
        $stream->seek(7);
        $string = $stream->read(5);
        $this->assertEquals("world", $string);
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::getContents
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testReadsToEnd()
    {
        $stream = new StreamStream($this->handle);
        $stream->seek(7);
        $string = $stream->getContents();
        $this->assertEquals("world!", $string);
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::getMetadata
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testReturnsMetadataArray()
    {
        $stream = new StreamStream($this->handle);
        $this->assertEquals(stream_get_meta_data($this->handle), $stream->getMetadata());
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::getMetadata
     * @uses WellRESTed\Stream\StreamStream
     */
    public function testReturnsMetadataItem()
    {
        $stream = new StreamStream($this->handle);
        $metadata = stream_get_meta_data($this->handle);
        $this->assertEquals($metadata["mode"], $stream->getMetadata("mode"));
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::isReadable
     * @uses WellRESTed\Stream\StreamStream
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
        $stream = new StreamStream($this->handle);
        $this->assertEquals($readable, $stream->isReadable());
    }

    /**
     * @covers WellRESTed\Stream\StreamStream::isWritable
     * @uses WellRESTed\Stream\StreamStream
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
        $stream = new StreamStream($this->handle);
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
