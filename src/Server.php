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
    /** @var mixed[] */
    protected $attributes = [];
    /** @var DispatcherInterface */
    private $dispatcher;
    /** @var string|null attribute name for matched path variables */
    private $pathVariablesAttributeName = null;
    /** @var ServerRequestInterface|null */
    private $request = null;
    /** @var ResponseInterface */
    private $response;
    /** @var TransmitterInterface */
    private $transmitter;
    /** @var mixed[] List array of middleware */
    private $stack;

    public function __construct() {
        $this->stack = [];
        $this->response = new Response();
        $this->dispatcher = new Dispatcher();
        $this->transmitter = new Transmitter();
    }

    /**
     * Push a new middleware onto the stack.
     *
     * @param mixed $middleware Middleware to dispatch in sequence
     * @return Server
     */
    public function add($middleware): Server
    {
        $this->stack[] = $middleware;
        return $this;
    }

    /**
     * Return a new Router that uses the server's dispatcher.
     *
     * @return Router
     */
    public function createRouter(): Router
    {
        return new Router(
            $this->dispatcher,
            $this->pathVariablesAttributeName
        );
    }

    /**
     * Perform the request-response cycle.
     *
     * This method reads a server request, dispatches the request through the
     * server's stack of middleware, and outputs the response via a Transmitter.
     */
    public function respond(): void
    {
        $request = $this->getRequest();
        foreach ($this->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $next = function (
            ServerRequestInterface $rqst,
            ResponseInterface $resp
        ): ResponseInterface {
            return $resp;
        };

        $response = $this->response;

        $response = $this->dispatcher->dispatch(
            $this->stack, $request, $response, $next);

        $this->transmitter->transmit($request, $response);
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

    // -------------------------------------------------------------------------
    /* Defaults */

    private function getRequest(): ServerRequestInterface
    {
        if (!$this->request) {
            $this->request = ServerRequest::getServerRequest();
        }
        return $this->request;
    }
}
