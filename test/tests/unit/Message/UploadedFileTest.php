<?php

namespace WellRESTed\Test\Message;

use WellRESTed\Message\UploadedFile;

/**
 * @uses WellRESTed\Message\UploadedFile
 * @uses WellRESTed\Message\Stream
 * @uses WellRESTed\Message\NullStream
 */
class UploadedFileTest extends \PHPUnit_Framework_TestCase
{
    private $tmpName;
    private $movePath;

    public function setUp()
    {
        parent::setUp();
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

    /**
     * @covers WellRESTed\Message\UploadedFile::__construct
     * @covers WellRESTed\Message\UploadedFile::getStream
     */
    public function testGetStreamReturnsStreamInterface()
    {
        $file = new UploadedFile("", "", 0, "", 0);
        $this->assertInstanceOf('\Psr\Http\Message\StreamInterface', $file->getStream());
    }

    /**
     * @covers WellRESTed\Message\UploadedFile::__construct
     * @covers WellRESTed\Message\UploadedFile::getStream
     */
    public function testGetStreamReturnsStreamWrappingUploadedFile()
    {
        $content = "Hello, World!";
        file_put_contents($this->tmpName, $content);

        $file = new UploadedFile("", "", 0, $this->tmpName, "");
        $stream = $file->getStream();
        $this->assertEquals($content, (string) $stream);
    }

    /**
     * @covers WellRESTed\Message\UploadedFile::__construct
     * @covers WellRESTed\Message\UploadedFile::getStream
     */
    public function testGetStreamReturnsEmptyStreamForNoFile()
    {
        $file = new UploadedFile("", "", 0, "", 0);
        $this->assertTrue($file->getStream()->eof());
    }

    /**
     * @covers WellRESTed\Message\UploadedFile::__construct
     * @covers WellRESTed\Message\UploadedFile::getStream
     * @expectedException \RuntimeException
     */
    public function testGetStreamThrowsExceptionAfterMove()
    {
        $content = "Hello, World!";
        file_put_contents($this->tmpName, $content);

        $file = new UploadedFile("", "", 0, $this->tmpName, "");
        $file->move($this->movePath);
        $file->getStream();
    }

    // ------------------------------------------------------------------------
    // move

    /**
     * @covers WellRESTed\Message\UploadedFile::move
     */
    public function testMoveRelocatesUploadedFileToDestiationIfExists()
    {
        $content = "Hello, World!";
        file_put_contents($this->tmpName, $content);
        $originalMd5 = md5_file($this->tmpName);

        $file = new UploadedFile("", "", 0, $this->tmpName, "");
        $file->move($this->movePath);

        $this->assertEquals($originalMd5, md5_file($this->movePath));
    }

    /**
     * @covers WellRESTed\Message\UploadedFile::move
     * @expectedException \RuntimeException
     */
    public function testThrowsExcpetionOnSubsequentCallToMove()
    {
        $content = "Hello, World!";
        file_put_contents($this->tmpName, $content);

        $file = new UploadedFile("", "", 0, $this->tmpName, "");
        $file->move($this->movePath);
        $file->move($this->movePath);
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
