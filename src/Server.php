<?php

namespace WellRESTed;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\Dispatching\DispatchStackInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Routing\Router;
use WellRESTed\Transmission\Transmitter;
use WellRESTed\Transmission\TransmitterInterface;

class Server implements DispatchStackInterface
{
    /** @var DispatcherInterface */
    private $dispatcher;

    /** @var mixed[] List array of middleware */
    private $stack;

    public function __construct()
    {
        $this->dispatcher = $this->getDispatcher();
        $this->stack = [];
    }

    /**
     * Push a new middleware onto the stack.
     *
     * @param mixed $middleware Middleware to dispatch in sequence
     * @return self
     */
    public function add($middleware)
    {
        $this->stack[] = $middleware;
        return $this;
    }

    /**
     * Dispatch the contained middleware in the order in which they were added.
     *
     * The first middleware added to the stack is the first to be dispatched.
     *
     * Each middleware, when dispatched, will receive a $next callable that
     * dispatches the middleware that follows it. The only exception to this is
     * the last middleware in the stack which much receive a $next callable the
     * returns the response unchanged.
     *
     * If the instance is dispatched with no middleware added, the instance
     * MUST call $next passing $request and $response and return the returned
     * response.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return ResponseInterface
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        return $this->dispatcher->dispatch($this->stack, $request, $response, $next);
    }

    // ------------------------------------------------------------------------

    /**
     * Return a new Router that uses the server's dispatcher.
     *
     * @return Router
     */
    public function createRouter()
    {
        return new Router($this->dispatcher);
    }

    /**
     * Perform the request-response cycle.
     *
     * This method reads a server request, dispatches the request through the
     * server's stack of middleware, and outputs the response.
     */
    public function respond()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $next = function ($request, $response) {
            return $response;
        };
        $response = $this->dispatch($request, $response, $next);
        $transmitter = $this->getTransmitter();
        $transmitter->transmit($request, $response);
    }

    // ------------------------------------------------------------------------
    // The following method provide instances using default classes. To use
    // custom classes, subclass Server and override methods as needed.

    /**
     * Return an instance to dispatch middleware.
     *
     * @return DispatcherInterface
     */
    protected function getDispatcher()
    {
        return new Dispatcher();
    }

    // @codeCoverageIgnoreStart

    /**
     * Return an instance representing the request submitted to the server.
     *
     * @return ServerRequestInterface
     */
    protected function getRequest()
    {
        return ServerRequest::getServerRequest();
    }

    /**
     * Return an instance that will output the response to the client.
     *
     * @return TransmitterInterface
     */
    protected function getTransmitter()
    {
        return new Transmitter();
    }

    /**
     * Return a "blank" response instance to populate.
     *
     * The response will be dispatched through the middleware and eventually
     * output to the client.
     *
     * @return ResponseInterface
     */
    protected function getResponse()
    {
        return new Response();
    }

    // @codeCoverageIgnoreEnd
}
