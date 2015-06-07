<?php

namespace WellRESTed;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequest;
use WellRESTed\Routing\Router;
use WellRESTed\Transmission\Transmitter;
use WellRESTed\Transmission\TransmitterInterface;

class Server
{
    /** @var array */
    protected $attributes;

    /** @var string ServerRequestInterface attribute name for matched path variables */
    protected $pathVariablesAttributeName;

    /** @var mixed[] List array of middleware */
    protected $stack;

    /** @var DispatcherInterface */
    private $dispatcher;

    /**
     * Create a new server.
     *
     * By default, when a route containing path variables matches, the path
     * variables are stored individually as attributes on the
     * ServerRequestInterface.
     *
     * When $pathVariablesAttributeName is set, a single attribute will be
     * stored with the name. The value will be an array containing all of the
     * path variables.
     *
     * @param array $attributes key-value pairs to register as attributes
     *     with the server request.
     * @param DispatcherInterface $dispatcher Dispatches middleware. If no
     *     object is passed, the Server will create a
     *     WellRESTed\Dispatching\Dispatcher
     * @param string|null $pathVariablesAttributeName Attribute name for
     *     matched path variables. A null value sets attributes directly.
     */
    public function __construct(
        array $attributes = null,
        DispatcherInterface $dispatcher = null,
        $pathVariablesAttributeName = null
    ) {
        if ($attributes === null) {
            $attributes = [];
        }
        $this->attributes = $attributes;
        if ($dispatcher === null) {
            $dispatcher = $this->getDefaultDispatcher();
        }
        $this->dispatcher = $dispatcher;
        $this->pathVariablesAttributeName = $pathVariablesAttributeName;
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
        return new Router($this->getDispatcher(), $this->pathVariablesAttributeName);
    }

    /**
     * Return the dispatched used by the server.
     *
     * @return DispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Perform the request-response cycle.
     *
     * This method reads a server request, dispatches the request through the
     * server's stack of middleware, and outputs the response.
     *
     * @param ServerRequestInterface $request Request provided by the client
     * @param ResponseInterface $response Initial starting place response to
     *     propagate to middleware.
     * @param TransmitterInterface $transmitter Instance to outputting the
     *     final response to the client.
     */
    public function respond(
        ServerRequestInterface $request = null,
        ResponseInterface $response = null,
        TransmitterInterface $transmitter = null
    ) {
        if ($request === null) {
            $request = $this->getRequest();
        }
        foreach ($this->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }
        if ($response === null) {
            $response = $this->getResponse();
        }
        if ($transmitter === null) {
            $transmitter = $this->getTransmitter();
        }

        $next = function ($request, $response) {
            return $response;
        };
        $response = $this->dispatch($request, $response, $next);
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
    protected function getDefaultDispatcher()
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
