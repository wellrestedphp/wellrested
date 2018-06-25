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
    /** @var DispatcherInterface */
    private $dispatcher;
    /** @var string ServerRequestInterface attribute name for matched path variables */
    private $pathVariablesAttributeName;
    /** @var ServerRequestInterface */
    private $request;
    /** @var ResponseInterface */
    private $response;
    /** @var TransmitterInterface */
    private $transmitter;
    /** @var mixed[] List array of middleware */
    private $stack;
    /** @var ResponseInterface */
    private $unhandledResponse;

    public function __construct() {
        $this->stack = [];
    }

    /**
     * Push a new middleware onto the stack.
     *
     * @param mixed $middleware Middleware to dispatch in sequence
     * @return Server
     */
    public function add($middleware)
    {
        $this->stack[] = $middleware;
        return $this;
    }

    /**
     * Return a new Router that uses the server's dispatcher.
     *
     * @return Router
     */
    public function createRouter()
    {
        return new Router(
            $this->getDispatcher(),
            $this->pathVariablesAttributeName
        );
    }

    /**
     * Perform the request-response cycle.
     *
     * This method reads a server request, dispatches the request through the
     * server's stack of middleware, and outputs the response via a Transmitter.
     */
    public function respond()
    {
        $request = $this->getRequest();
        foreach ($this->getAttributes() as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $response = $this->getResponse();

        $next = function () {
            return $this->getUnhandledResponse();
        };

        $dispatcher = $this->getDispatcher();
        $response = $dispatcher->dispatch(
            $this->stack, $request, $response, $next);

        $transmitter = $this->getTransmitter();
        $transmitter->transmit($request, $response);
    }

    // -------------------------------------------------------------------------
    /* Configuration */

    /**
     * @param array $attributes
     * @return Server
     */
    public function setAttributes(array $attributes): Server
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @param DispatcherInterface $dispatcher
     * @return Server
     */
    public function setDispatcher(DispatcherInterface $dispatcher): Server
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    /**
     * @param string $name
     * @return Server
     */
    public function setPathVariablesAttributeName(string $name): Server {
        $this->pathVariablesAttributeName = $name;
        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @return Server
     */
    public function setRequest(ServerRequestInterface $request): Server
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @param ResponseInterface $response
     * @return Server
     */
    public function setResponse(ResponseInterface $response): Server
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @param TransmitterInterface $transmitter
     * @return Server
     */
    public function setTransmitter(TransmitterInterface $transmitter): Server
    {
        $this->transmitter = $transmitter;
        return $this;
    }

    /**
     * @param ResponseInterface $response
     * @return Server
     */
    public function setUnhandledResponse(ResponseInterface $response): Server {
        $this->unhandledResponse = $response;
        return $this;
    }

    // -------------------------------------------------------------------------
    /* Defaults */

    private function getAttributes()
    {
        if (!$this->attributes) {
            $this->attributes = [];
        }
        return $this->attributes;
    }

    private function getDispatcher()
    {
        if (!$this->dispatcher) {
            $this->dispatcher = new Dispatcher();
        }
        return $this->dispatcher;
    }

    private function getRequest()
    {
        if (!$this->request) {
            $this->request = ServerRequest::getServerRequest();
        }
        return $this->request;
    }

    private function getResponse()
    {
        if (!$this->response) {
            $this->response = new Response();
        }
        return $this->response;
    }

    private function getTransmitter()
    {
        if (!$this->transmitter) {
            $this->transmitter = new Transmitter();
        }
        return $this->transmitter;
    }

    private function getUnhandledResponse()
    {
        if (!$this->unhandledResponse) {
            $this->unhandledResponse = new Response(404);
        }
        return $this->unhandledResponse;
    }
}
