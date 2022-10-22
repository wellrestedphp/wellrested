<?php

declare(strict_types=1);

namespace WellRESTed;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequestMarshaller;
use WellRESTed\Routing\Router;
use WellRESTed\Transmission\Transmitter;
use WellRESTed\Transmission\TransmitterInterface;

class Server
{
    private Configuration $configuration;

    /** @var array<string, mixed> */
    private array $attributes = [];

    private ?DispatcherInterface $dispatcher = null;

    private ?ServerRequestInterface $request = null;

    private ResponseInterface $response;

    private TransmitterInterface $transmitter;

    /** @var mixed[] List array of middleware */
    private array $stack = [];

    public function __construct()
    {
        $this->configuration = new Configuration();
        $this->response = new Response();
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
     * Return a new Router that uses the server's configuration.
     *
     * @return Router
     */
    public function createRouter(): Router
    {
        $router = new Router($this);
        return $router;
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

        $response = $this->getDispatcher()->dispatch(
            $this->stack,
            $request,
            $response,
            $next
        );

        $this->transmitter->transmit($request, $response);
    }

    // -------------------------------------------------------------------------
    /* Configuration */

    /**
     * @param array<string, mixed> $attributes
     * @return Server
     */
    public function setAttributes(array $attributes): Server
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function setContainer(ContainerInterface $container): Server
    {
        $this->configuration->setContainer($container);
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

    public function getDispatcher(): DispatcherInterface
    {
        if (!$this->dispatcher) {
            $this->dispatcher = new Dispatcher($this->configuration);
        }
        return $this->dispatcher;
    }

    public function getPathVariablesAttributeName(): ?string
    {
        return $this->configuration->getPathVariablesAttributeName();
    }

    public function setPathVariablesAttributeName(?string $name): Server
    {
        $this->configuration->setPathVariablesAttributeName($name);
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

    private function getRequest(): ServerRequestInterface
    {
        if (!$this->request) {
            $marshaller = new ServerRequestMarshaller();
            return $marshaller->getServerRequest();
        }
        return $this->request;
    }
}
