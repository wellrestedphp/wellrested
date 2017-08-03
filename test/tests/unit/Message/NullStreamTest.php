<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\NullStream;
use WellRESTed\Test\TestCase;

class NullStreamTest extends TestCase
{
    public function testCastsToString()
    {
        $stream = new NullStream();
        $this->assertEquals("", (string) $stream);
    }

    public function testCloseDoesNothing()
    {
        $stream = new NullStream();
        $stream->close();
        $this->assertTrue(true); // Asserting no exception occurred.
    }

    public function testDetachReturnsNull()
    {
        $stream = new NullStream();
        $this->assertNull($stream->detach());
    }

    public function testSizeReturnsZero()
    {
        $stream = new NullStream();
        $this->assertEquals(0, $stream->getSize());
    }

    public function testTellReturnsZero()
    {
        $stream = new NullStream();
        $this->assertEquals(0, $stream->tell());
    }

    public function testEofReturnsTrue()
    {
        $stream = new NullStream();
        $this->assertTrue($stream->eof());
    }

    public function testIsSeekableReturnsFalse()
    {
        $stream = new NullStream();
        $this->assertFalse($stream->isSeekable());
    }

    /** @expectedException \RuntimeException */
    public function testSeekReturnsFalse()
    {
        $stream = new NullStream();
        $stream->seek(10);
    }

    /** @expectedException \RuntimeException */
    public function testRewindThrowsException()
    {
        $stream = new NullStream();
        $stream->rewind();
    }

    public function testIsWritableReturnsFalse()
    {
        $stream = new NullStream();
        $this->assertFalse($stream->isWritable());
    }

    /** @expectedException \RuntimeException */
    public function testWriteThrowsException()
    {
        $stream = new NullStream();
        $stream->write("");
    }

    public function testIsReadableReturnsTrue()
    {
        $stream = new NullStream();
        $this->assertTrue($stream->isReadable());
    }

    public function testReadReturnsEmptyString()
    {
        $stream = new NullStream();
        $this->assertEquals("", $stream->read(100));
    }

    public function testGetContentsReturnsEmptyString()
    {
        $stream = new NullStream();
        $this->assertEquals("", $stream->getContents());
    }

    public function testGetMetadataReturnsNull()
    {
        $stream = new NullStream();
        $this->assertNull($stream->getMetadata());
    }

    public function testGetMetadataReturnsNullWithKey()
    {
        $stream = new NullStream();
        $this->assertNull($stream->getMetadata("size"));
    }
}
