<?php

namespace WellRESTed\Message;

use InvalidArgumentException;
use RuntimeException;
use WellRESTed\Test\TestCase;

class StreamTest extends TestCase
{
    private $resource;
    private $resourceDevNull;
    private $content = 'Hello, world!';

    protected function setUp(): void
    {
        $this->resource = fopen('php://memory', 'w+');
        $this->resourceDevNull = fopen('/dev/zero', 'r');
        fwrite($this->resource, $this->content);
        StreamHelper::$fail = false;
    }

    protected function tearDown(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }

    public function testCreatesInstanceWithStreamResource(): void
    {
        $stream = new Stream($this->resource);
        $this->assertNotNull($stream);
    }

    public function testCreatesInstanceWithString(): void
    {
        $stream = new Stream('Hello, world!');
        $this->assertNotNull($stream);
    }

    /**
     * @dataProvider invalidResourceProvider
     * @param mixed $resource
     */
    public function testThrowsExceptionWithInvalidResource($resource): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Stream($resource);
    }

    public function invalidResourceProvider(): array
    {
        return [
            [null],
            [true],
            [4],
            [[]]
        ];
    }

    public function testCastsToString(): void
    {
        $stream = new Stream($this->resource);
        $this->assertEquals($this->content, (string) $stream);
    }

    public function testClosesHandle(): void
    {
        $stream = new Stream($this->resource);
        $stream->close();
        $this->assertFalse(is_resource($this->resource));
    }

    public function testDetachReturnsHandle(): void
    {
        $stream = new Stream($this->resource);
        $this->assertSame($this->resource, $stream->detach());
    }

    public function testDetachUnsetsInstanceVariable(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->assertNull($stream->detach());
    }

    public function testReturnsSize(): void
    {
        $stream = new Stream($this->resource);
        $this->assertEquals(strlen($this->content), $stream->getSize());
    }

    public function testReturnsNullForSizeWhenUnableToReadFromFstat(): void
    {
        $stream = new Stream($this->resourceDevNull);
        $this->assertNull($stream->getSize());
    }

    public function testTellReturnsHandlePosition(): void
    {
        $stream = new Stream($this->resource);
        fseek($this->resource, 10);
        $this->assertEquals(10, $stream->tell());
    }

    public function testTellThrowsRuntimeExceptionWhenUnableToReadStreamPosition(): void
    {
        StreamHelper::$fail = true;
        $stream = new Stream($this->resource);
        $this->expectException(RuntimeException::class);
        $stream->tell();
    }

    public function testReturnsOef(): void
    {
        $stream = new Stream($this->resource);
        $stream->rewind();
        $stream->getContents();
        $this->assertTrue($stream->eof());
    }

    public function testReadsSeekableStatusFromMetadata(): void
    {
        $stream = new Stream($this->resource);
        $metadata = stream_get_meta_data($this->resource);
        $seekable = $metadata['seekable'] == 1;
        $this->assertEquals($seekable, $stream->isSeekable());
    }

    public function testSeeksToPosition(): void
    {
        $stream = new Stream($this->resource);
        $stream->seek(10);
        $this->assertEquals(10, ftell($this->resource));
    }

    public function testSeekThrowsRuntimeExceptionWhenUnableToSeek(): void
    {
        StreamHelper::$fail = true;
        $stream = new Stream($this->resource);
        $this->expectException(RuntimeException::class);
        $stream->seek(10);
    }

    public function testRewindReturnsToBeginning(): void
    {
        $stream = new Stream($this->resource);
        $stream->seek(10);
        $stream->rewind();
        $this->assertEquals(0, ftell($this->resource));
    }

    public function testRewindThrowsRuntimeExceptionWhenUnableToRewind(): void
    {
        StreamHelper::$fail = true;
        $stream = new Stream($this->resource);
        $this->expectException(RuntimeException::class);
        $stream->rewind();
    }

    public function testWritesToHandle(): void
    {
        $message = "\nThis is a stream.";
        $stream = new Stream($this->resource);
        $stream->write($message);
        $this->assertEquals($this->content . $message, (string) $stream);
    }

    public function testThrowsExceptionOnErrorWriting(): void
    {
        $this->expectException(RuntimeException::class);
        $filename = tempnam(sys_get_temp_dir(), 'php');
        $handle = fopen($filename, 'r');
        $stream = new Stream($handle);
        $stream->write('Hello, world!');
    }

    public function testThrowsExceptionOnErrorReading(): void
    {
        $this->expectException(RuntimeException::class);
        $filename = tempnam(sys_get_temp_dir(), 'php');
        $handle = fopen($filename, 'w');
        $stream = new Stream($handle);
        $stream->read(10);
    }

    public function testReadsFromStream(): void
    {
        $stream = new Stream($this->resource);
        $stream->seek(7);
        $string = $stream->read(5);
        $this->assertEquals('world', $string);
    }

    public function testThrowsExceptionOnErrorReadingToEnd(): void
    {
        $this->expectException(RuntimeException::class);
        $filename = tempnam(sys_get_temp_dir(), 'php');
        $handle = fopen($filename, 'w');
        $stream = new Stream($handle);
        $stream->getContents();
    }

    public function testReadsToEnd(): void
    {
        $stream = new Stream($this->resource);
        $stream->seek(7);
        $string = $stream->getContents();
        $this->assertEquals('world!', $string);
    }

    public function testReturnsMetadataArray(): void
    {
        $stream = new Stream($this->resource);
        $this->assertEquals(stream_get_meta_data($this->resource), $stream->getMetadata());
    }

    public function testReturnsMetadataItem(): void
    {
        $stream = new Stream($this->resource);
        $metadata = stream_get_meta_data($this->resource);
        $this->assertEquals($metadata['mode'], $stream->getMetadata('mode'));
    }

    /**
     * @dataProvider modeProvider
     * @param string $mode Access type used to open the stream
     * @param bool $readable The stream should be readable
     * @param bool $writable The stream should be writeable
     */
    public function testReturnsIsReadableForReadableStreams(string $mode, bool $readable, bool $writable): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'php');
        if ($mode[0] === 'x') {
            unlink($tmp);
        }
        $resource = fopen($tmp, $mode);
        $stream = new Stream($resource);
        $this->assertEquals($readable, $stream->isReadable());
    }

    /**
     * @dataProvider modeProvider
     * @param string $mode Access type used to open the stream
     * @param bool $readable The stream should be readable
     * @param bool $writable The stream should be writeable
     */
    public function testReturnsIsWritableForWritableStreams(string $mode, bool $readable, bool $writable): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'php');
        if ($mode[0] === 'x') {
            unlink($tmp);
        }
        $resource = fopen($tmp, $mode);
        $stream = new Stream($resource);
        $this->assertEquals($writable, $stream->isWritable());
    }

    public function modeProvider(): array
    {
        return [
            ['r',  true,  false],
            ['r+', true,  true],
            ['w',  false, true],
            ['w+', true,  true],
            ['a',  false, true],
            ['a+', true,  true],
            ['x',  false, true],
            ['x+', true,  true],
            ['c',  false, true],
            ['c+', true,  true]
        ];
    }

    // -------------------------------------------------------------------------
    // After Detach

    public function testAfterDetachToStringReturnsEmptyString(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->assertEquals('', (string) $stream);
    }

    public function testAfterDetachCloseDoesNothing(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $stream->close();
        $this->assertTrue(true);
    }

    public function testAfterDetachDetachReturnsNull(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->assertNull($stream->detach());
    }

    public function testAfterDetachGetSizeReturnsNull(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->assertNull($stream->getSize());
    }

    public function testAfterDetachTellThrowsRuntimeException(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->tell();
    }

    public function testAfterDetachEofReturnsTrue(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->assertTrue($stream->eof());
    }

    public function testAfterDetachIsSeekableReturnsFalse(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->assertFalse($stream->isSeekable());
    }

    public function testAfterDetachSeekThrowsRuntimeException(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->seek(0);
    }

    public function testAfterDetachRewindThrowsRuntimeException(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->rewind();
    }

    public function testAfterDetachIsWritableReturnsFalse(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->assertFalse($stream->isWritable());
    }

    public function testAfterDetachWriteThrowsRuntimeException(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->write('bork');
    }

    public function testAfterDetachIsReadableReturnsFalse(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->assertFalse($stream->isReadable());
    }

    public function testAfterDetachReadThrowsRuntimeException(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->read(10);
    }

    public function testAfterDetachGetContentsThrowsRuntimeException(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->expectException(RuntimeException::class);
        $stream->getContents();
    }

    public function testAfterDetachGetMetadataReturnsNull(): void
    {
        $stream = new Stream($this->resource);
        $stream->detach();
        $this->assertNull($stream->getMetadata());
    }
}

// -----------------------------------------------------------------------------

// Declare functions in this namespace so the class under test will use these
// instead of the internal global functions during testing.

class StreamHelper
{
    public static $fail = false;
}

function fseek($resource, $offset, $whence = SEEK_SET)
{
    if (StreamHelper::$fail) {
        return -1;
    }
    return \fseek($resource, $offset, $whence);
}

function ftell($resource)
{
    if (StreamHelper::$fail) {
        return false;
    }
    return \ftell($resource);
}

function rewind($resource)
{
    if (StreamHelper::$fail) {
        return false;
    }
    return \rewind($resource);
}
