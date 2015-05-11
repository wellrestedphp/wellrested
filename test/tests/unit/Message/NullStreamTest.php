<?php

namespace WellRESTed\Test\Message;

use WellRESTed\Message\NullStream;

/**
 * @coversDefaultClass WellRESTed\Message\NullStream
 * @uses WellRESTed\Message\NullStream
 * @group message
 */
class NullStreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__toString()
     */
    public function testCastsToString()
    {
        $stream = new NullStream();
        $this->assertEquals("", (string) $stream);
    }

    public function testCloseDoesNothing()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $stream->close();
        $this->assertTrue(true); // Asserting no exception occured.
    }

    /**
     * @covers ::detach()
     */
    public function testDetachReturnsNull()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertNull($stream->detach());
    }

    /**
     * @covers ::getSize
     */
    public function testSizeReturnsZero()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertEquals(0, $stream->getSize());
    }

    /**
     * @covers ::tell
     */
    public function testTellReturnsZero()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertEquals(0, $stream->tell());
    }

    /**
     * @covers ::eof
     */
    public function testEofReturnsTrue()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertTrue($stream->eof());
    }

    /**
     * @covers ::isSeekable
     */
    public function testIsSeekableReturnsFalse()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertFalse($stream->isSeekable());
    }

    /**
     * @covers ::seek
     * @expectedException \RuntimeException
     */
    public function testSeekReturnsFalse()
    {
        $stream = new NullStream();
        $stream->seek(10);
    }

    /**
     * @covers ::rewind
     * @expectedException \RuntimeException
     */
    public function testRewindThrowsException()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $stream->rewind();
    }

    /**
     * @covers ::isWritable
     */
    public function testIsWritableReturnsFalse()
    {
        $stream = new NullStream();
        $this->assertFalse($stream->isWritable());
    }

    /**
     * @covers ::write
     * @expectedException \RuntimeException
     */
    public function testWriteThrowsException()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $stream->write("");
    }

    /**
     * @covers ::isReadable
     */
    public function testIsReadableReturnsTrue()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertTrue($stream->isReadable());
    }

    /**
     * @covers ::read
     */
    public function testReadReturnsEmptyString()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertEquals("", $stream->read(100));
    }

    /**
     * @covers ::getContents
     */
    public function testGetContentsReturnsEmptyString()
    {
        $stream = new NullStream();
        $this->assertEquals("", $stream->getContents());
    }

    /**
     * @covers ::getMetadata
     */
    public function testGetMetadataReturnsNull()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertNull($stream->getMetadata());
    }

    /**
     * @covers ::getMetadata
     */
    public function testGetMetadataReturnsNullWithKey()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertNull($stream->getMetadata("size"));
    }
}
