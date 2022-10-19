<?php

namespace WellRESTed\Integration;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Server;
use WellRESTed\Test\Doubles\TransmitterDouble;
use WellRESTed\Test\TestCase;

class IntegrationTest extends TestCase
{
    private Server $server;
    private TransmitterDouble $transmitter;

    public function setUp(): void
    {
        parent::setUp();

        $this->server = new Server();
        $this->transmitter = new TransmitterDouble();
        $this->server->setTransmitter($this->transmitter);

        $router = $this->server->createRouter()
            ->register('GET', '/', new Response(200));

        $this->server->add($router);
    }

    /** @dataProvider requestProvider */
    public function testRequestReturnsExpectedResponse(
        ServerRequestInterface $request,
        ResponseInterface $expected
    ): void {
        // Arrange
        $this->server->setRequest($request);

        // Act
        $this->server->respond();

        // Assert
        $actual = $this->transmitter->response;
        $this->assertEquals($expected->getStatusCode(), $actual->getStatusCode());
    }

    public function requestProvider(): array
    {
        return [
            'Static Route' => [
                new ServerRequest('GET', '/'),
                new Response(200)
            ]
        ];
    }
}
