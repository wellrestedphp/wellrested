<?php

namespace WellRESTed\Integration;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Server;
use WellRESTed\Test\Doubles\ContainerDouble;
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
            ->register('GET', '/', new Response(200))
            ->register('GET', '/status', StatusCodeHandler::class)
            ->register('GET', '/cats/{name}', AttributeHandler::class)
            ->register('GET', '/dogs/{name}', AttributeArrayHandler::class);

        $this->server->add($router);
    }

    /** @dataProvider requestProvider */
    public function testRequestReturnsExpectedResponse(
        ServerRequestInterface $request,
        ResponseInterface $expected,
        ?callable $setup = null
    ): void {
        // Arrange
        $this->server->setRequest($request);
        if ($setup) {
            $setup($this->server);
        }

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
            ],
            'Not found for path that doesn\'t match' => [
                new ServerRequest('GET', '/not/a/real/path'),
                new Response(404)
            ],
            'Bad method for method not assigned to path' => [
                new ServerRequest('POST', '/'),
                new Response(405)
            ],
            'Resolves handler by FQDN' => [
                new ServerRequest('GET', '/status'),
                new Response(200)
            ],
            'Resolves handler by DI service name' => [
                new ServerRequest('GET', '/status'),
                new Response(204),
                function (Server $server) {
                    $handler = new StatusCodeHandler(204);
                    $container = new ContainerDouble([
                        StatusCodeHandler::class => $handler
                    ]);
                    $server->setContainer($container);
                }
            ],
            'Path variables as attributes' => [
                new ServerRequest('GET', '/cats/aggie'),
                new Response(200),
            ],
            'Path variables as attributes array' => [
                new ServerRequest('GET', '/dogs/louisa'),
                new Response(200),
                function (Server $server) {
                    $server->setPathVariablesAttributeName('vars');
                }
            ]
        ];
    }
}

// -----------------------------------------------------------------------------

class StatusCodeHandler implements RequestHandlerInterface
{
    private int $statusCode;

    public function __construct(int $statusCode = 200)
    {
        $this->statusCode = $statusCode;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response($this->statusCode);
    }
}

class AttributeHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getAttribute('name')) {
            return new Response(200);
        }
        return new Response(404);
    }
}

class AttributeArrayHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $vars = $request->getAttribute('vars', []);
        $name = $vars['name'] ?? '';
        if ($name) {
            return new Response(200);
        }
        return new Response(404);
    }
}
