<?php

namespace WellRESTed\Responder;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ResponderInterface
{
    /**
     * Outputs a response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response Response to output
     */
    public function respond(ServerRequestInterface $request, ResponseInterface $response);
}
