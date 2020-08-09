<?php

namespace WellRESTed\Transmission;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface TransmitterInterface
{
    /**
     * Outputs a response to the client.
     *
     * This method MUST output the status line, headers, and body to the client.
     *
     * Implementations MAY add response headers to ensure expected headers are
     * presents but MUST NOT alter existing headers.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response Response to output
     */
    public function transmit(ServerRequestInterface $request, ResponseInterface $response): void;
}
