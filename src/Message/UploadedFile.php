<?php

namespace WellRESTed\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Value object representing a file uploaded through an HTTP request.
 */
class UploadedFile implements UploadedFileInterface
{
    private $clientFilename;
    private $clientMediaType;
    private $error;
    private $moved = false;
    private $size;
    private $stream;
    private $tmpName;

    /**
     * Create a new Uri. The arguments correspond with keys from arrays
     * provided by $_FILES. For example, given this structure for $_FILES:
     *
     *     array(
     *         'avatar' => array(
     *             'name' => 'my-avatar.png',
     *             'type' => 'image/png',
     *             'size' => 90996,
     *             'tmp_name' => 'phpUxcOty',
     *             'error' => 0
     *         )
     *     )
     *
     *  ...use this call:
     *
     *     new UploadedFile(
     *         $_FILES['avatar']['name'],
     *         $_FILES['avatar']['type'],
     *         $_FILES['avatar']['size'],
     *         $_FILES['avatar']['tmp_name'],
     *         $_FILES['avatar']['error']
     *     );
     *
     * @param string $name Name of the file; provided by the client
     * @param string $type Media type of the file; provided by the client
     * @param int $size The file size in bytes.
     * @param string $tmpName Local filesystem name of the file
     * @param int $error One of PHP's UPLOAD_ERR_XXX constants.
     *     @see http://php.net/manual/en/features.file-upload.errors.php
     */
    public function __construct($name, $type, $size, $tmpName, $error)
    {
        $this->clientFilename = $name;
        $this->error = $error;
        $this->clientMediaType = $type;
        $this->size = $size;

        if (file_exists($tmpName)) {
            $this->tmpName = $tmpName;
            $this->stream = new Stream(fopen($tmpName, "r"));
        } else {
            $this->stream = new NullStream();
        }
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * This method returns a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow using native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in
     * a native PHP stream wrapper to work with such functions).
     *
     * If the moveTo() method has been called previously, this method will
     * raise an exception.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can
     *     be created.
     */
    public function getStream()
    {
        if ($this->moved) {
            throw new \RuntimeException("File has already been moved");
        }
        if (php_sapi_name() !== "cli" && !is_uploaded_file($this->tmpName)) {
            throw new \RuntimeException("File is not an uploaded file.");
        }
        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * Use this method as an alternative to move_uploaded_file(). This method
     * is guaranteed to work in both SAPI and non-SAPI environments.
     *
     * The original file or stream will be removed on completion.
     *
     * If this method is called more than once, any subsequent calls will raise
     * an exception.
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $path Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($path)
    {
        if ($this->tmpName === null || !file_exists($this->tmpName)) {
            throw new \RuntimeException("File " . $this->tmpName . " does not exist.");
        }
        if (php_sapi_name() === "cli") {
            rename($this->tmpName, $path);
        } else {
            move_uploaded_file($this->tmpName, $path);
        }
        $this->moved = true;
    }

    /**
     * Retrieve the file size.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     *
     * The return value will be one of PHP's UPLOAD_ERR_XXX constants.
     *
     * If the file was uploaded successfully, this method will return
     * UPLOAD_ERR_OK.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious filename with the intention to corrupt or hack your
     * application.
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     *
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }
}
