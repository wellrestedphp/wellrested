<?php

namespace WellRESTed\Message;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    /** @var resource */
    private $resource;

    /**
     * Create a new Stream passing either a stream resource handle (e.g.,
     * from fopen) or a string.
     *
     * If $resource is a string, the Stream will open a php://temp stream,
     * write the string to the stream, and use that temp resource.
     *
     * @param resource|string $resource A file system pointer resource or
     *     string
     */
    public function __construct($resource = "")
    {
        if (is_resource($resource) && get_resource_type($resource) === "stream") {
            $this->resource = $resource;
        } elseif (is_string($resource)) {
            $this->resource = fopen("php://temp", "wb+");
            if ($resource !== "") {
                $this->write($resource);
            }
        } else {
            throw new \InvalidArgumentException("Expected a resource handler.");
        }
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method will attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        $string = "";
        try {
            if ($this->isSeekable()) {
                rewind($this->resource);
            }
            $string = $this->getContents();
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            // @codeCoverageIgnoreEnd
            // Silence exceptions in order to conform with PHP's string casting operations.
        }
        return $string;
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        fclose($this->resource);
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying file-pointer handler
     */
    public function detach()
    {
        $stream = $this->resource;
        $this->resource = null;
        return $stream;
    }

    /**
     * Get the size of the stream if known
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        $statistics = fstat($this->resource);
        return $statistics["size"] ?: null;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        $position = ftell($this->resource);
        if ($position === false) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException("Unable to retrieve current position of file pointer.");
            // @codeCoverageIgnoreEnd
        }
        return $position;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return feof($this->resource);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->getMetadata("seekable") == 1;
    }

    /**
     * Seek to a position in the stream.
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
        $result = -1;
        if ($this->isSeekable()) {
            $result = fseek($this->resource, $offset, $whence);
        }
        if ($result === -1) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException("Unable to seek to position.");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        $result = false;
        if ($this->isSeekable()) {
            $result = rewind($this->resource);
        }
        if ($result === false) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException("Unable to seek to position.");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        $mode = $this->getMetadata("mode");
        return $mode[0] !== "r" || strpos($mode, "+") !== false;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        $result = false;
        if ($this->isWritable()) {
            $result = fwrite($this->resource, $string);
        }
        if ($result === false) {
            throw new \RuntimeException("Unable to write to stream.");
        }
        return $result;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        $mode = $this->getMetadata("mode");
        return strpos($mode, "r") !== false || strpos($mode, "+") !== false;
    }

    /**
     * Read data from the stream.
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
        $result = false;
        if ($this->isReadable()) {
            $result = fread($this->resource, $length);
        }
        if ($result === false) {
            throw new \RuntimeException("Unable to read from stream.");
        }
        return $result;
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
        $result = false;
        if ($this->isReadable()) {
            $result = stream_get_contents($this->resource);
        }
        if ($result === false) {
            throw new \RuntimeException("Unable to read from stream.");
        }
        return $result;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        $metadata = stream_get_meta_data($this->resource);
        if ($key === null) {
            return $metadata;
        } else {
            return $metadata[$key];
        }
    }
}
