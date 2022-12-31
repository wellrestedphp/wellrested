<?php

namespace WellRESTed\Test\Doubles;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Transmission\TransmitterInterface;

class TransmitterDouble implements TransmitterInterface
{
    public ?ServerRequestInterface $request;
    public ?ResponseInterface $response;

    public function transmit(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): void {
        $this->request = $request;
        $this->response = $response;
    }
}
