<?php

namespace WellRESTed\Transmission;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\Transmission\Middleware\ContentLengthHandler;
use WellRESTed\Transmission\Middleware\HeadHandler;

class Transmitter implements TransmitterInterface
{
    /** @var int */
    private $chunkSize = 0;

    /** @var DispatcherInterface */
    private $dispatcher;

    public function __construct(DispatcherInterface $dispatcher = null)
    {
        if ($dispatcher === null) {
            $dispatcher = new Dispatcher();
        }
        $this->dispatcher = $dispatcher;
    }

    /**
     * Outputs a response to the client.
     *
     * This method outputs the status line, headers, and body to the client.
     *
     * This method will also provide a Content-length header if needed and
     * supress the body for HEAD requests.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response Response to output
     */
    public function transmit(ServerRequestInterface $request, ResponseInterface $response)
    {
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

    /**
     * @param int $chunkSize
     */
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;
    }

    protected function prepareResponse(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->dispatcher->dispatch(
            [
                new ContentLengthHandler(),
                new HeadHandler()
            ],
            $request,
            $response,
            function ($request, $response) {
                return $response;
            }
        );
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
