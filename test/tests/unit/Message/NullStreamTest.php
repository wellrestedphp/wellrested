<?php

namespace WellRESTed\Test\Message;

use WellRESTed\Message\NullStream;

class NullStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Message\NullStream::__toString()
     */
    public function testCastsToString()
    {
        $stream = new NullStream();
        $this->assertEquals("", (string) $stream);
    }

    public function testCloseDoesNothing()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertNull($stream->close());
    }

    /**
     * @covers WellRESTed\Message\NullStream::detach()
     * @uses WellRESTed\Message\Stream
     */
    public function testDetachReturnsNull()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertNull($stream->detach());
    }

    /**
     * @covers WellRESTed\Message\NullStream::getSize
     * @uses WellRESTed\Message\Stream
     */
    public function testSizeReturnsZero()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertEquals(0, $stream->getSize());
    }

    /**
     * @covers WellRESTed\Message\NullStream::tell
     */
    public function testTellReturnsZero()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertEquals(0, $stream->tell());
    }

    /**
     * @covers WellRESTed\Message\NullStream::eof
     */
    public function testEofReturnsReturnsTrue()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertTrue($stream->eof());
    }

    /**
     * @covers WellRESTed\Message\NullStream::isSeekable
     * @uses WellRESTed\Message\Stream
     */
    public function testIsSeekableReturnsFalse()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertFalse($stream->isSeekable());
    }

    /**
     * @covers WellRESTed\Message\NullStream::seek
     * @expectedException \RuntimeException
     */
    public function testSeekReturnsFalse()
    {
        $stream = new NullStream();
        $stream->seek(10);
    }

    /**
     * @covers WellRESTed\Message\NullStream::rewind
     * @expectedException \RuntimeException
     */
    public function testRewindReturnsFalse()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $stream->rewind();
    }

    /**
     * @covers WellRESTed\Message\NullStream::isWritable
     */
    public function testIsWritableReturnsFalse()
    {
        $stream = new NullStream();
        $this->assertFalse($stream->isWritable());
    }

    /**
     * @covers WellRESTed\Message\NullStream::write
     * @expectedException \RuntimeException
     */
    public function testWriteThrowsException()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $stream->write("");
    }

    /**
     * @covers WellRESTed\Message\NullStream::isReadable
     */
    public function testIsReableReturnsTrue()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertTrue($stream->isReadable());
    }

    /**
     * @covers WellRESTed\Message\NullStream::read
     */
    public function testReadReturnsEmptyString()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertEquals("", $stream->read(100));
    }

    /**
     * @covers WellRESTed\Message\NullStream::getContents
     */
    public function testGetContentsReturnsEmptyString()
    {
        $stream = new NullStream();
        $this->assertEquals("", $stream->getContents());
    }

    /**
     * @covers WellRESTed\Message\NullStream::getMetadata
     */
    public function testGetMetadataReturnsNull()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertNull($stream->getMetadata());
    }

    /**
     * @covers WellRESTed\Message\NullStream::getMetadata
     */
    public function testGetMetadataReturnsNullWithKey()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertNull($stream->getMetadata("size"));
    }
}
