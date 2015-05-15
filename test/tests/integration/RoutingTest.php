<?php

namespace WellRESTed\Test\Integration;

use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Message\Stream;
use WellRESTed\MiddlewareInterface;
use WellRESTed\Server;
use WellRESTed\Transmission\TransmitterInterface;

/**
 * @coversNothing
 */
class ServerTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatchesMiddleware()
    {
        $server = new Server();
        $server->add(function ($rqst, $resp, $next) {
            $resp = $resp->withStatus(200)
                ->withBody(new Stream("Hello, world!"));
            return $next($rqst, $resp);
        });

        $request = new ServerRequest();
        $response = new Response();
        $transmitter = new CallableTransmitter(function ($request, $response) {
            $this->assertEquals("Hello, world!", (string) $response->getBody());
        });
        $server->respond($request, $response, $transmitter);
    }

    public function testDispatchesMiddlewareChain()
    {
        $server = new Server();
        $server->add(function ($rqst, $resp, $next) {
           return $next($rqst, $resp);
        });
        $server->add(function ($rqst, $resp, $next) {
            $resp = $resp->withStatus(200)
                ->withBody(new Stream("Hello, world!"));
            return $next($rqst, $resp);
        });

        $request = new ServerRequest();
        $response = new Response();
        $transmitter = new CallableTransmitter(function ($request, $response) {
            $this->assertEquals("Hello, world!", (string) $response->getBody());
        });
        $server->respond($request, $response, $transmitter);
    }

    /**
     * @dataProvider routeProvider
     */
    public function testDispatchesAssortedMiddlewareTypesByPath($requestTarget, $expectedBody)
    {
        $stringMiddlewareWrapper = function ($string) {
            return new StringMiddleware($string);
        };

        $server = new Server();
        $server->add(function ($rqst, $resp, $next) {
            return $next($rqst, $resp);
        });
        $server->add($server->createRouter()
            ->register("GET", "/fry", [
                    new StringMiddleware("Philip "),
                    new StringMiddleware("J. "),
                    new StringMiddleware("Fry")
                ])
            ->register("GET", "/leela", new StringMiddleware("Turanga Leela"))
            ->register("GET", "/bender", __NAMESPACE__ . '\BenderMiddleware')
            ->register("GET", "/professor", $stringMiddlewareWrapper("Professor Hubert J. Farnsworth"))
            ->register("GET", "/amy", function ($request, $response, $next) {
                $message = "Amy Wong";
                $body = $response->getBody();
                if ($body->isWritable()) {
                    $body->write($message);
                } else {
                    $response = $response->withBody(new Stream($message));
                }
                return $next($request, $response);
            })
            ->register("GET", "/hermes", [
                    new StringMiddleware("Hermes "),
                    new StringMiddleware("Conrad", false),
                    new StringMiddleware(", CPA")
                ])
            ->register("GET", "/zoidberg", [
                function ($request, $response, $next) {
                    // Prepend "Doctor " to the dispatched response on the return trip.
                    $response = $next($request, $response);
                    $message = "Doctor " . (string) $response->getBody();
                    return $response->withBody(new Stream($message));
                },
                new StringMiddleware("John "),
                new StringMiddleware("Zoidberg")
            ])
        );
        $server->add(function ($rqst, $resp, $next) {
            $resp = $resp->withStatus(200);
            return $next($rqst, $resp);
        });

        $request = (new ServerRequest())->withRequestTarget($requestTarget);
        $response = new Response();

        $transmitter = new CallableTransmitter(function ($request, $response) use ($expectedBody) {
            $this->assertEquals($expectedBody, (string) $response->getBody());
        });
        $server->respond($request, $response, $transmitter);
    }

    public function routeProvider()
    {
        return [
            ["/fry", "Philip J. Fry"],
            ["/leela", "Turanga Leela"],
            ["/bender", "Bender Bending Rodriguez"],
            ["/professor", "Professor Hubert J. Farnsworth"],
            ["/amy", "Amy Wong"],
            ["/hermes", "Hermes Conrad"],
            ["/zoidberg", "Doctor John Zoidberg"]
        ];
    }

}

class CallableTransmitter implements TransmitterInterface
{
    private $callable;

    public function __construct($callable)
    {
        $this->callable = $callable;
    }

    public function transmit(ServerRequestInterface $request, ResponseInterface $response)
    {
        $callable = $this->callable;
        $callable($request, $response);
    }
}

class StringMiddleware implements MiddlewareInterface
{
    private $string;
    private $propagate;

    public function __construct($string, $propagate = true)
    {
        $this->string = $string;
        $this->propagate = $propagate;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $body = $response->getBody();
        if ($body->isWritable()) {
            $body->write($this->string);
        } else {
            $response = $response->withBody(new Stream($this->string));
        }
        if ($this->propagate) {
            return $next($request, $response);
        } else {
            return $response;
        }
    }
}

class BenderMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $message = "Bender Bending Rodriguez";
        $body = $response->getBody();
        if ($body->isWritable()) {
            $body->write($message);
        } else {
            $response = $response->withBody(new Stream($message));
        }
        return $next($request, $response);
    }
}
