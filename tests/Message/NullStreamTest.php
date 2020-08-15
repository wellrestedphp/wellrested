<?php

namespace WellRESTed\Message;

use RuntimeException;
use WellRESTed\Test\TestCase;

class NullStreamTest extends TestCase
{
    public function testCastsToString(): void
    {
        $stream = new NullStream();
        $this->assertEquals('', (string) $stream);
    }

    public function testCloseDoesNothing(): void
    {
        $stream = new NullStream();
        $stream->close();
        $this->assertTrue(true); // Asserting no exception occurred.
    }

    public function testDetachReturnsNull(): void
    {
        $stream = new NullStream();
        $this->assertNull($stream->detach());
    }

    public function testSizeReturnsZero(): void
    {
        $stream = new NullStream();
        $this->assertEquals(0, $stream->getSize());
    }

    public function testTellReturnsZero(): void
    {
        $stream = new NullStream();
        $this->assertEquals(0, $stream->tell());
    }

    public function testEofReturnsTrue(): void
    {
        $stream = new NullStream();
        $this->assertTrue($stream->eof());
    }

    public function testIsSeekableReturnsFalse(): void
    {
        $stream = new NullStream();
        $this->assertFalse($stream->isSeekable());
    }

    public function testSeekReturnsFalse(): void
    {
        $this->expectException(RuntimeException::class);
        $stream = new NullStream();
        $stream->seek(10);
    }

    public function testRewindThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $stream = new NullStream();
        $stream->rewind();
    }

    public function testIsWritableReturnsFalse(): void
    {
        $stream = new NullStream();
        $this->assertFalse($stream->isWritable());
    }

    public function testWriteThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $stream = new NullStream();
        $stream->write('');
    }

    public function testIsReadableReturnsTrue(): void
    {
        $stream = new NullStream();
        $this->assertTrue($stream->isReadable());
    }

    public function testReadReturnsEmptyString(): void
    {
        $stream = new NullStream();
        $this->assertEquals('', $stream->read(100));
    }

    public function testGetContentsReturnsEmptyString(): void
    {
        $stream = new NullStream();
        $this->assertEquals('', $stream->getContents());
    }

    public function testGetMetadataReturnsNull(): void
    {
        $stream = new NullStream();
        $this->assertNull($stream->getMetadata());
    }

    public function testGetMetadataReturnsNullWithKey(): void
    {
        $stream = new NullStream();
        $this->assertNull($stream->getMetadata('size'));
    }
}
