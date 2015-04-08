<?php

namespace WellRESTed\Test\Stream;

use WellRESTed\Stream\NullStream;

class NullStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Stream\NullStream::__toString()
     */
    public function testCastsToString()
    {
        $stream = new NullStream();
        $this->assertEquals("", (string) $stream);
    }

    public function testCloseDoesNothing()
    {
        $stream = new NullStream();
        $this->assertNull($stream->close());
    }
    /**
     * @covers WellRESTed\Stream\NullStream::detach()
     * @uses WellRESTed\Stream\Stream
     */
    public function testDetachReturnsNull()
    {
        $stream = new NullStream();
        $this->assertNull($stream->detach());
    }

    /**
     * @covers WellRESTed\Stream\NullStream::getSize
     * @uses WellRESTed\Stream\Stream
     */
    public function testSizeReturnsZero()
    {
        $stream = new NullStream();
        $this->assertEquals(0, $stream->getSize());
    }

    /**
     * @covers WellRESTed\Stream\NullStream::tell
     */
    public function testTellReturnsFalse()
    {
        $stream = new NullStream();
        $this->assertFalse($stream->tell());
    }

    /**
     * @covers WellRESTed\Stream\NullStream::eof
     */
    public function testEofReturnsReturnsTrue()
    {
        $stream = new NullStream();
        $this->assertTrue($stream->eof());
    }

    /**
     * @covers WellRESTed\Stream\NullStream::isSeekable
     * @uses WellRESTed\Stream\Stream
     */
    public function testIsSeekableReturnsFalse()
    {
        $stream = new NullStream();
        $this->assertFalse($stream->isSeekable());
    }

    /**
     * @covers WellRESTed\Stream\NullStream::seek
     */
    public function testSeekReturnsFalse()
    {
        $stream = new NullStream();
        $this->assertFalse($stream->seek(10));
    }

    /**
     * @covers WellRESTed\Stream\NullStream::rewind
     */
    public function testRewindReturnsFalse()
    {
        $stream = new NullStream();
        $this->assertFalse($stream->rewind());
    }

    /**
     * @covers WellRESTed\Stream\NullStream::isWritable
     */
    public function testIsWritableReturnsFalse()
    {
        $stream = new NullStream();
        $this->assertFalse($stream->isWritable());
    }

    /**
     * @covers WellRESTed\Stream\NullStream::write
     */
    public function testWriteReturnsFalse()
    {
        $stream = new NullStream();
        $this->assertFalse($stream->write(""));
    }

    /**
     * @covers WellRESTed\Stream\NullStream::isReadable
     */
    public function testIsReableReturnsTrue()
    {
        $stream = new NullStream();
        $this->assertTrue($stream->isReadable());
    }

    /**
     * @covers WellRESTed\Stream\NullStream::read
     */
    public function testReadReturnsEmptyString()
    {
        $stream = new NullStream();
        $this->assertEquals("", $stream->read(100));
    }

    /**
     * @covers WellRESTed\Stream\NullStream::getContents
     */
    public function testGetContentsReturnsEmptyString()
    {
        $stream = new NullStream();
        $this->assertEquals("", $stream->getContents());
    }

    /**
     * @covers WellRESTed\Stream\NullStream::getMetadata
     */
    public function testGetMetadataReturnsNull()
    {
        $stream = new NullStream();
        $this->assertNull($stream->getMetadata());
    }

    /**
     * @covers WellRESTed\Stream\NullStream::getMetadata
     */
    public function testGetMetadataReturnsNullWithKey()
    {
        $stream = new NullStream();
        $this->assertNull($stream->getMetadata("size"));
    }
}
