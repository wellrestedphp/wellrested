<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\UploadedFile;
use WellRESTed\Message\UploadedFileState;

// Hides several php core functions for testing.
require_once __DIR__ . "/../../../src/UploadedFileState.php";

/**
 * @covers WellRESTed\Message\UploadedFile
 * @group message
 */
class UploadedFileTest extends \PHPUnit_Framework_TestCase
{
    private $tmpName;
    private $movePath;

    public function setUp()
    {
        parent::setUp();
        UploadedFileState::$php_sapi_name = "cli";
        $this->tmpName = tempnam(sys_get_temp_dir(), "tst");
        $this->movePath = tempnam(sys_get_temp_dir(), "tst");
    }

    public function tearDown()
    {
        parent::tearDown();
        if (file_exists($this->tmpName)) {
            unlink($this->tmpName);
        }
        if (file_exists($this->movePath)) {
            unlink($this->movePath);
        }
    }

    // ------------------------------------------------------------------------
    // getStream

    public function testGetStreamReturnsStreamInterface()
    {
        $file = new UploadedFile("", "", 0, "", 0);
        $this->assertInstanceOf('\Psr\Http\Message\StreamInterface', $file->getStream());
    }

    public function testGetStreamReturnsStreamWrappingUploadedFile()
    {
        $content = "Hello, World!";
        file_put_contents($this->tmpName, $content);
        $file = new UploadedFile("", "", 0, $this->tmpName, "");
        $stream = $file->getStream();
        $this->assertEquals($content, (string) $stream);
    }

    public function testGetStreamReturnsEmptyStreamForNoFile()
    {
        $file = new UploadedFile("", "", 0, "", 0);
        $this->assertTrue($file->getStream()->eof());
    }

    /** @expectedException \RuntimeException */
    public function testGetStreamThrowsExceptionAfterMoveTo()
    {
        $content = "Hello, World!";
        file_put_contents($this->tmpName, $content);
        $file = new UploadedFile("", "", 0, $this->tmpName, "");
        $file->moveTo($this->movePath);
        $file->getStream();
    }

    /** @expectedException \RuntimeException */
    public function testGetStreamThrowsExceptionForNonUploadedFile()
    {
        UploadedFileState::$php_sapi_name = "apache";
        UploadedFileState::$is_uploaded_file = false;
        $file = new UploadedFile("", "", 0, "", 0);
        $file->getStream();
    }

    // ------------------------------------------------------------------------
    // moveTo

    public function testMoveToSapiRelocatesUploadedFileToDestiationIfExists()
    {
        UploadedFileState::$php_sapi_name = "fpm-fcgi";

        $content = "Hello, World!";
        file_put_contents($this->tmpName, $content);
        $originalMd5 = md5_file($this->tmpName);

        $file = new UploadedFile("", "", 0, $this->tmpName, "");
        $file->moveTo($this->movePath);

        $this->assertEquals($originalMd5, md5_file($this->movePath));
    }

    public function testMoveToNonSapiRelocatesUploadedFileToDestiationIfExists()
    {
        $content = "Hello, World!";
        file_put_contents($this->tmpName, $content);
        $originalMd5 = md5_file($this->tmpName);

        $file = new UploadedFile("", "", 0, $this->tmpName, "");
        $file->moveTo($this->movePath);

        $this->assertEquals($originalMd5, md5_file($this->movePath));
    }

    /** @expectedException \RuntimeException */
    public function testMoveToThrowsExceptionOnSubsequentCall()
    {
        $content = "Hello, World!";
        file_put_contents($this->tmpName, $content);

        $file = new UploadedFile("", "", 0, $this->tmpName, "");
        $file->moveTo($this->movePath);
        $file->moveTo($this->movePath);
    }

    // ------------------------------------------------------------------------
    // getSize

    public function testGetSizeReturnsSize()
    {
        $file = new UploadedFile("", "", 1024, "", 0);
        $this->assertEquals(1024, $file->getSize());
    }

    // ------------------------------------------------------------------------
    // getError

    public function testGetErrorReturnsError()
    {
        $file = new UploadedFile("", "", 1024, "", UPLOAD_ERR_INI_SIZE);
        $this->assertEquals(UPLOAD_ERR_INI_SIZE, $file->getError());
    }

    // ------------------------------------------------------------------------
    // clientFilename

    public function testGetClientFilenameReturnsClientFilename()
    {
        $file = new UploadedFile("clientFilename", "", 0, "", 0);
        $this->assertEquals("clientFilename", $file->getClientFilename());
    }

    // ------------------------------------------------------------------------
    // clientMediaType

    public function testGetClientMediaTypeReturnsClientMediaType()
    {
        $file = new UploadedFile("", "clientMediaType", 0, "", 0);
        $this->assertEquals("clientMediaType", $file->getClientMediaType());
    }
}
