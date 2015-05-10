<?php

namespace WellRESTed\Responder;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class Responder implements ResponderInterface
{
    private $chunkSize = 0;

    /**
     * Outputs a response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response Response to output
     */
    public function respond(ServerRequestInterface $request, ResponseInterface $response)
    {
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

    /**
     * @param int $chunkSize
     */
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;
    }

    private function getStatusLine(ResponseInterface $response)
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

    private function outputBody(StreamInterface $body)
    {
        if ($this->chunkSize > 0) {
            $body->rewind();
            while (!$body->eof()) {
                print $body->read($this->chunkSize);
            }
        } else {
            print (string) $body;
        }
    }
}
