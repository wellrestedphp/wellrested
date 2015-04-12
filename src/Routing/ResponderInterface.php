<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;

interface ResponderInterface
{
    /**
     * Outputs a response.
     *
     * @param ResponseInterface $response Response to output
     */
    public function respond(ResponseInterface $response);
}
