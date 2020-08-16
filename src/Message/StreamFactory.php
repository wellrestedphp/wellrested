<?php

namespace WellRESTed\Message;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class StreamFactory implements StreamFactoryInterface
{
    /**
     * Create a new stream from a string.
     *
     * @param string $content String content with which to populate the stream.
     * @return StreamInterface
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    /**
     * Create a stream from an existing file.
     *
     * @param string $filename Filename or stream URI to use as basis of stream.
     * @param string $mode Mode with which to open the underlying file/stream.
     *
     * @return StreamInterface
     * @throws RuntimeException If the file cannot be opened.
     */
    public function createStreamFromFile(
        string $filename,
        string $mode = 'r'
    ): StreamInterface {
        $f = fopen($filename, $mode);
        if ($f === false) {
            throw new RuntimeException();
        }
        return new Stream($f);
    }

    /**
     * Create a new stream from an existing resource.
     *
     * @param resource $resource PHP resource to use as basis of stream.
     *
     * @return StreamInterface
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
}
