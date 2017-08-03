<?php

namespace WellRESTed\Message;

use Psr\Http\Message\StreamInterface;

/**
 * NullStream is a minimal, always-empty, non-writable stream.
 *
 * Use this for messages with no body.
 */
class NullStream implements StreamInterface
{
    /**
     * Returns an empty string
     *
     * @return string
     */
    public function __toString()
    {
        return "";
    }

    /**
     * No-op
     *
     * @return void
     */
    public function close()
    {
    }

    /**
     * No-op
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        return null;
    }

    /**
     * Returns 0
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        return 0;
    }

    /**
     * Returns 0
     *
     * @return int|bool Position of the file pointer or false on error.
     */
    public function tell()
    {
        return 0;
    }

    /**
     * Returns true
     *
     * @return bool
     */
    public function eof()
    {
        return true;
    }

    /**
     * Returns false
     *
     * @return bool
     */
    public function isSeekable()
    {
        return false;
    }

    /**
     * Always throws exception
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException("Unable to seek to position.");
    }

    /**
     * Always throws exception
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        throw new \RuntimeException("Unable to rewind stream.");
    }

    /**
     * Returns false.
     *
     * @return bool
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * Always throws exception
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        throw new \RuntimeException("Unable to write to stream.");
    }

    /**
     * Returns true
     *
     * @return bool
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * Returns an empty string
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        return "";
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        return "";
    }

    /**
     * Returns null
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        return null;
    }
}
