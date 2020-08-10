<?php

namespace WellRESTed\Message;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Stream implements StreamInterface
{
    private const READABLE_MODES = ['r', 'r+', 'w+', 'a+', 'x+', 'c+'];
    private const WRITABLE_MODES = ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'];

    /** @var resource|null */
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
    public function __construct($resource = '')
    {
        if (is_resource($resource) && get_resource_type($resource) === 'stream') {
            $this->resource = $resource;
        } elseif (is_string($resource)) {
            $this->resource = fopen('php://temp', 'wb+');
            if ($resource !== '') {
                $this->write($resource);
            }
        } else {
            throw new InvalidArgumentException('Expected a resource handler.');
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
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (Exception $e) {
            // Silence exceptions in order to conform with PHP's string casting
            // operations.
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        if ($this->resource === null) {
            return;
        }

        $resource = $this->resource;
        fclose($resource);
        $this->resource = null;
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
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    /**
     * Get the size of the stream if known
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if ($this->resource === null) {
            return null;
        }

        $statistics = fstat($this->resource);
        if ($statistics && $statistics['size']) {
            return $statistics['size'];
        }
        return null;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws RuntimeException on error.
     */
    public function tell()
    {
        if ($this->resource === null) {
            throw new RuntimeException('Unable to retrieve current position of detached stream.');
        }

        $position = ftell($this->resource);
        if ($position === false) {
            throw new RuntimeException('Unable to retrieve current position of file pointer.');
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
        if ($this->resource === null) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        if ($this->resource === null) {
            return false;
        }

        return $this->getMetadata('seekable') == 1;
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
     * @return void
     * @throws RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($this->resource === null) {
            throw new RuntimeException('Unable to seek detached stream.');
        }

        $result = -1;
        if ($this->isSeekable()) {
            $result = fseek($this->resource, $offset, $whence);
        }
        if ($result === -1) {
            throw new RuntimeException('Unable to seek to position.');
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
     * @return void
     * @throws RuntimeException on failure.
     */
    public function rewind()
    {
        if ($this->resource === null) {
            throw new RuntimeException('Unable to seek detached stream.');
        }

        $result = false;
        if ($this->isSeekable()) {
            $result = rewind($this->resource);
        }
        if ($result === false) {
            throw new RuntimeException('Unable to rewind.');
        }
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if ($this->resource === null) {
            return false;
        }

        $mode = $this->getBasicMode();
        return in_array($mode, self::WRITABLE_MODES);
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws RuntimeException on failure.
     */
    public function write($string)
    {
        if ($this->resource === null) {
            throw new RuntimeException('Unable to write to detached stream.');
        }

        $result = false;
        if ($this->isWritable()) {
            $result = fwrite($this->resource, $string);
        }
        if ($result === false) {
            throw new RuntimeException('Unable to write to stream.');
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
        if ($this->resource === null) {
            return false;
        }

        $mode = $this->getBasicMode();
        return in_array($mode, self::READABLE_MODES);
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if ($this->resource === null) {
            throw new RuntimeException('Unable to read to detached stream.');
        }

        $result = false;
        if ($this->isReadable()) {
            $result = fread($this->resource, $length);
        }
        if ($result === false) {
            throw new RuntimeException('Unable to read from stream.');
        }
        return $result;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        if ($this->resource === null) {
            throw new RuntimeException('Unable to read to detached stream.');
        }

        $result = false;
        if ($this->isReadable()) {
            $result = stream_get_contents($this->resource);
        }
        if ($result === false) {
            throw new RuntimeException('Unable to read from stream.');
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
     * @param string|null $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if ($this->resource === null) {
            return null;
        }

        $metadata = stream_get_meta_data($this->resource);
        if ($key === null) {
            return $metadata;
        } else {
            return $metadata[$key];
        }
    }

    /**
     * @return string Mode for the resource reduced to only the characters
     *   r, w, a, x, c, and + needed to determine readable and writeable status.
     */
    private function getBasicMode()
    {
        $mode = $this->getMetadata('mode') ?? '';
        return preg_replace('/[^rwaxc+]/', '', $mode);
    }
}
