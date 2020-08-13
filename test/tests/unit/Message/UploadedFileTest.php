<?php

namespace WellRESTed\Message;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use WellRESTed\Test\TestCase;

class UploadedFileTest extends TestCase
{
    private $tmpName;
    private $movePath;

    public function setUp(): void
    {
        parent::setUp();
        UploadedFileState::$php_sapi_name = 'cli';
        $this->tmpName = tempnam(sys_get_temp_dir(), 'tst');
        $this->movePath = tempnam(sys_get_temp_dir(), 'tst');
    }

    public function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->tmpName)) {
            unlink($this->tmpName);
        }
        if (file_exists($this->movePath)) {
            unlink($this->movePath);
        }
    }

    // -------------------------------------------------------------------------
    // getStream

    public function testGetStreamReturnsStreamInterface(): void
    {
        $file = new UploadedFile('', '', 0, $this->tmpName, 0);
        $this->assertInstanceOf(StreamInterface::class, $file->getStream());
    }

    public function testGetStreamReturnsStreamWrappingUploadedFile(): void
    {
        $content = 'Hello, World!';
        file_put_contents($this->tmpName, $content);
        $file = new UploadedFile('', '', 0, $this->tmpName, '');
        $stream = $file->getStream();
        $this->assertEquals($content, (string) $stream);
    }

    public function testGetStreamThrowsRuntimeExceptionForNoFile(): void
    {
        $file = new UploadedFile('', '', 0, '', 0);
        $this->expectException(RuntimeException::class);
        $file->getStream();
    }

    public function testGetStreamThrowsExceptionAfterMoveTo(): void
    {
        $this->expectException(RuntimeException::class);
        $content = 'Hello, World!';
        file_put_contents($this->tmpName, $content);
        $file = new UploadedFile('', '', 0, $this->tmpName, '');
        $file->moveTo($this->movePath);
        $file->getStream();
    }

    public function testGetStreamThrowsExceptionForNonUploadedFile(): void
    {
        $this->expectException(RuntimeException::class);
        UploadedFileState::$php_sapi_name = 'apache';
        UploadedFileState::$is_uploaded_file = false;
        $file = new UploadedFile('', '', 0, '', 0);
        $file->getStream();
    }

    // -------------------------------------------------------------------------
    // moveTo

    public function testMoveToSapiRelocatesUploadedFileToDestinationIfExists(): void
    {
        UploadedFileState::$php_sapi_name = 'fpm-fcgi';

        $content = 'Hello, World!';
        file_put_contents($this->tmpName, $content);
        $originalMd5 = md5_file($this->tmpName);

        $file = new UploadedFile('', '', 0, $this->tmpName, '');
        $file->moveTo($this->movePath);

        $this->assertEquals($originalMd5, md5_file($this->movePath));
    }

    public function testMoveToNonSapiRelocatesUploadedFileToDestinationIfExists(): void
    {
        $content = 'Hello, World!';
        file_put_contents($this->tmpName, $content);
        $originalMd5 = md5_file($this->tmpName);

        $file = new UploadedFile('', '', 0, $this->tmpName, '');
        $file->moveTo($this->movePath);

        $this->assertEquals($originalMd5, md5_file($this->movePath));
    }

    public function testMoveToThrowsExceptionOnSubsequentCall(): void
    {
        $this->expectException(RuntimeException::class);

        $content = 'Hello, World!';
        file_put_contents($this->tmpName, $content);

        $file = new UploadedFile('', '', 0, $this->tmpName, '');
        $file->moveTo($this->movePath);
        $file->moveTo($this->movePath);
    }

    // -------------------------------------------------------------------------
    // getSize

    public function testGetSizeReturnsSize(): void
    {
        $file = new UploadedFile('', '', 1024, '', 0);
        $this->assertEquals(1024, $file->getSize());
    }

    // -------------------------------------------------------------------------
    // getError

    public function testGetErrorReturnsError(): void
    {
        $file = new UploadedFile('', '', 1024, '', UPLOAD_ERR_INI_SIZE);
        $this->assertEquals(UPLOAD_ERR_INI_SIZE, $file->getError());
    }

    // -------------------------------------------------------------------------
    // clientFilename

    public function testGetClientFilenameReturnsClientFilename(): void
    {
        $file = new UploadedFile('clientFilename', '', 0, '', 0);
        $this->assertEquals('clientFilename', $file->getClientFilename());
    }

    // -------------------------------------------------------------------------
    // clientMediaType

    public function testGetClientMediaTypeReturnsClientMediaType(): void
    {
        $file = new UploadedFile('', 'clientMediaType', 0, '', 0);
        $this->assertEquals('clientMediaType', $file->getClientMediaType());
    }
}

// -----------------------------------------------------------------------------

// Declare functions in this namespace so the class under test will use these
// instead of the internal global functions during testing.

class UploadedFileState
{
    public static $php_sapi_name;
    public static $is_uploaded_file;
}

function php_sapi_name()
{
    return UploadedFileState::$php_sapi_name;
}

function move_uploaded_file($source, $target)
{
    return rename($source, $target);
}

function is_uploaded_file($file)
{
    return UploadedFileState::$is_uploaded_file;
}
