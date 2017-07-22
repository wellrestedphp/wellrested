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
        $stream = new \WellRESTed\Message\NullStream();
        $stream->close();
        $this->assertTrue(true); // Asserting no exception occured.
    }

    public function testDetachReturnsNull()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertNull($stream->detach());
    }

    public function testSizeReturnsZero()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertEquals(0, $stream->getSize());
    }

    public function testTellReturnsZero()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertEquals(0, $stream->tell());
    }

    public function testEofReturnsTrue()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertTrue($stream->eof());
    }

    public function testIsSeekableReturnsFalse()
    {
        $stream = new \WellRESTed\Message\NullStream();
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
        $stream = new \WellRESTed\Message\NullStream();
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
        $stream = new \WellRESTed\Message\NullStream();
        $stream->write("");
    }

    public function testIsReadableReturnsTrue()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertTrue($stream->isReadable());
    }

    public function testReadReturnsEmptyString()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertEquals("", $stream->read(100));
    }

    public function testGetContentsReturnsEmptyString()
    {
        $stream = new NullStream();
        $this->assertEquals("", $stream->getContents());
    }

    public function testGetMetadataReturnsNull()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertNull($stream->getMetadata());
    }

    public function testGetMetadataReturnsNullWithKey()
    {
        $stream = new \WellRESTed\Message\NullStream();
        $this->assertNull($stream->getMetadata("size"));
    }
}
