<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamableInterface;

class Responder
{
    public function respond(ResponseInterface $response, $chunkSize = 0)
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
            $this->outputBody($response->getBody(), $chunkSize);
        }
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

    private function outputBody(StreamableInterface $body, $chunkSize)
    {
        if ($chunkSize > 0) {
            $body->rewind();
            while (!$body->eof()) {
                print $body->read($chunkSize);
            }
        } else {
            print (string) $body;
        }
    }
}
