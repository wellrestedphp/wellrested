<?php

namespace WellRESTed\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Message\Stream;
use WellRESTed\Server;
use WellRESTed\Test\TestCase;
use WellRESTed\Transmission\TransmitterInterface;

/**
 * Integration test for Routing components
 * @coversNothing
 */
class RoutingTest extends TestCase
{
    /** @var Server */
    private $server;
    /** @var TransmitterMock */
    private $transmitter;
    /** @var ServerRequestInterface */
    private $request;
    /** @var ResponseInterface */
    private $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transmitter = new TransmitterMock();
        $this->request = new ServerRequest();
        $this->response = new Response();

        $this->server = new Server();

        $this->server->setTransmitter($this->transmitter);
    }

    private function respond(): ResponseInterface
    {
        $this->server->setRequest($this->request);
        $this->server->setResponse($this->response);
        $this->server->respond();

        return $this->transmitter->response;
    }

    // -------------------------------------------------------------------------

    public function testDispatchesMiddleware()
    {
        $expectedResponse = (new Response())
            ->withStatus(200)
            ->withBody(new Stream('Hello, world!'));

        $this->server->add(function () use ($expectedResponse) {
            return $expectedResponse;
        });

        $actualResponse = $this->respond();

        $this->assertSame($expectedResponse, $actualResponse);
    }

    public function testDispatchesMiddlewareChain()
    {
        $expectedResponse = (new Response())
            ->withStatus(200)
            ->withBody(new Stream('Hello, world!'));

        $this->server->add(function ($rqst, $resp, $next) {
            return $next($rqst, $resp);
        });
        $this->server->add(function ($rqst, $resp, $next) {
            return $next($rqst, $resp);
        });
        $this->server->add(function () use ($expectedResponse) {
            return $expectedResponse;
        });

        $actualResponse = $this->respond();

        $this->assertSame($expectedResponse, $actualResponse);
    }

    public function testDispatchesByRoute()
    {
        $router = $this->server->createRouter()
            ->register('GET', '/molly', new StringHandler('Molly'))
            ->register('GET', '/oscar', new StringHandler('Oscar'));
        $this->server->add($router);

        $this->request = $this->request
            ->withMethod('GET')
            ->withRequestTarget('/molly');

        $response = $this->respond();

        $this->assertEquals('Molly', (string) $response->getBody());
    }

    public function testDispatchesMiddlewareBeforeByRouteHandler()
    {
        $router = $this->server->createRouter()
            ->register('GET', '/molly', new StringHandler('Molly'))
            ->register('GET', '/oscar', new StringHandler('Oscar'));

        $this->server->add(new HeaderAdderMiddleware(
            'Content-type',
            'application/cat'
        ));
        $this->server->add($router);

        $this->request = $this->request
            ->withMethod('GET')
            ->withRequestTarget('/molly');

        $response = $this->respond();

        $this->assertEquals('Molly', (string) $response->getBody());
        $this->assertEquals(
            'application/cat',
            $response->getHeaderLine('Content-type')
        );
    }

    public function testDispatchesMiddlewareSpecificToRouter()
    {
        $catRouter =  $this->server->createRouter()
            ->add(new HeaderAdderMiddleware(
                'Content-type',
                'application/cat'
            ))
            ->register('GET', '/molly', new StringHandler('Molly'))
            ->register('GET', '/oscar', new StringHandler('Oscar'))
            ->continueOnNotFound();
        $this->server->add($catRouter);

        $dogRouter =  $this->server->createRouter()
            ->add(new HeaderAdderMiddleware(
                'Content-type',
                'application/dog'
            ))
            ->register('GET', '/bear', new StringHandler('Bear'));
        $this->server->add($dogRouter);

        $this->request = $this->request
            ->withMethod('GET')
            ->withRequestTarget('/bear');

        $response = $this->respond();

        $this->assertEquals('Bear', (string) $response->getBody());
        $this->assertEquals(
            'application/dog',
            $response->getHeaderLine('Content-type')
        );
    }

    public function testResponds404WhenNoRouteMatched()
    {
        $catRouter =  $this->server->createRouter()
            ->add(new HeaderAdderMiddleware(
                'Content-type',
                'application/cat'
            ))
            ->register('GET', '/molly', new StringHandler('Molly'))
            ->register('GET', '/oscar', new StringHandler('Oscar'))
            ->continueOnNotFound();
        $this->server->add($catRouter);

        $dogRouter =  $this->server->createRouter()
            ->add(new HeaderAdderMiddleware(
                'Content-type',
                'application/dog'
            ))
            ->register('GET', '/bear', new StringHandler('Bear'));
        $this->server->add($dogRouter);

        $this->request = $this->request
            ->withMethod('GET')
            ->withRequestTarget('/arfus');

        $response = $this->respond();

        $this->assertEquals(404, $response->getStatusCode());
    }
}

// -----------------------------------------------------------------------------

class TransmitterMock implements TransmitterInterface
{
    /** @var ResponseInterface */
    public $response;

    public function transmit(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): void {
        $this->response = $response;
    }
}

class StringHandler implements RequestHandlerInterface
{
    /** @var string */
    private $body;

    public function __construct(string $body)
    {
        $this->body = $body;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return (new Response(200))
            ->withBody(new Stream($this->body));
    }
}

class HeaderAdderMiddleware implements MiddlewareInterface
{
    /** @var string */
    private $name;
    /** @var string */
    private $value;

    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);
        $response = $response->withHeader($this->name, $this->value);
        return $response;
    }
}
