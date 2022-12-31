<?php

namespace WellRESTed\Transmission;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class Transmitter implements TransmitterInterface
{
    /** @var int */
    private $chunkSize = 8192;

    /**
     * Outputs a response to the client.
     *
     * This method outputs the status line, headers, and body to the client.
     *
     * This method will also provide a Content-length header if:
     *   - Response does not have a Content-length header
     *   - Response does not have a Transfer-encoding: chunked header
     *   - Response body stream is readable and reports a non-null size
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response Response to output
     */
    public function transmit(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): void {
        // Prepare the response for output.
        $response = $this->prepareResponse($request, $response);

        // Status Line
        header($this->getStatusLine($response));
        // Headers
        foreach ($response->getHeaders() as $key => $headers) {
            $replace = true;
            foreach ($headers as $header) {
                header("$key: $header", $replace);
                $replace = false;
            }
        }
        // Body
        $body = $response->getBody();
        if ($body->isReadable()) {
            $this->outputBody($response->getBody());
        }
    }

    public function setChunkSize(int $chunkSize): void
    {
        $this->chunkSize = $chunkSize;
    }

    private function prepareResponse(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Add Content-length header to the response when all of these are true:
        //
        // - Response does not have a Content-length header
        // - Response does not have a Transfer-encoding: chunked header
        // - Response body stream is readable and reports a non-null size
        //
        $contentLengthMissing = !$response->hasHeader('Content-length');
        $notChunked = strtolower($response->getHeaderLine('Transfer-encoding'))
            !== 'chunked';
        $size = $response->getBody()->getSize();

        if ($contentLengthMissing && $notChunked && $size !== null) {
            $response = $response->withHeader('Content-length', (string) $size);
        }

        return $response;
    }

    private function getStatusLine(ResponseInterface $response): string
    {
        $protocol = $response->getProtocolVersion();
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();
        if ($reasonPhrase) {
            return "HTTP/$protocol $statusCode $reasonPhrase";
        } else {
            return "HTTP/$protocol $statusCode";
        }
    }

    private function outputBody(StreamInterface $body): void
    {
        if ($this->chunkSize > 0) {
            if ($body->isSeekable()) {
                $body->rewind();
            }
            while (!$body->eof()) {
                print $body->read($this->chunkSize);
            }
        } else {
            print (string) $body;
        }
    }
}
