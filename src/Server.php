<?php

declare(strict_types=1);

namespace WellRESTed;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WellRESTed\Dispatching\Dispatcher;
use WellRESTed\Dispatching\DispatcherInterface;
use WellRESTed\Dispatching\MiddlewareQueue;
use WellRESTed\Message\Response;
use WellRESTed\Message\ServerRequestMarshaller;
use WellRESTed\Routing\Router;
use WellRESTed\Routing\TrailingSlashMode;
use WellRESTed\Transmission\Transmitter;
use WellRESTed\Transmission\TransmitterInterface;

class Server implements RequestHandlerInterface
{
    private ?ContainerInterface $container = null;

    private ?string $pathVariablesAttributeName = null;

    private TrailingSlashMode $trailingSlashMode = TrailingSlashMode::STRICT;

    /** @var array<string, mixed> */
    private array $attributes = [];

    private ?DispatcherInterface $dispatcher = null;

    private ?ServerRequestInterface $request = null;

    private ResponseInterface $response;

    private TransmitterInterface $transmitter;

    private MiddlewareQueue $middlewareQueue;

    public function __construct()
    {
        $this->middlewareQueue = new MiddlewareQueue($this);
        $this->response = new Response();
        $this->transmitter = new Transmitter();
    }

    /**
     * Push a new middleware onto the queue.
     *
     * @param mixed $middleware Middleware to dispatch in sequence
     * @return self
     */
    public function add($middleware): self
    {
        $this->middlewareQueue->add($middleware);
        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middlewareQueue->getMiddleware();
    }

    /** Return a new Router that uses the server's configuration. */
    public function createRouter(): Router
    {
        $router = new Router($this);
        return $router;
    }

    /** Return a new Router and add it to the Server's middleware queue */
    public function addRouter(): Router
    {
        $router = $this->createRouter();
        $this->add($router);
        return $router;
    }

    /**
     * Perform the request-response cycle.
     *
     * This method reads a server request, dispatches the request through the
     * server's queue of middleware, and outputs the response via a Transmitter.
     */
    public function respond(): void
    {
        $request = $this->getRequest();
        foreach ($this->attributes as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $response = $this->handle($request);

        $this->transmitter->transmit($request, $response);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $next = function (
            ServerRequestInterface $rqst,
            ResponseInterface $resp
        ): ResponseInterface {
            return $resp;
        };

        return call_user_func(
            $this->middlewareQueue,
            $request,
            $this->response,
            $next
        );
    }

    // -------------------------------------------------------------------------
    /* Configuration */

    /**
     * @param array<string, mixed> $attributes
     * @return self
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function setContainer(?ContainerInterface $container): self
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @param DispatcherInterface $dispatcher
     * @return self
     */
    public function setDispatcher(DispatcherInterface $dispatcher): self
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }

    public function getDispatcher(): DispatcherInterface
    {
        if (!$this->dispatcher) {
            $this->dispatcher = new Dispatcher($this);
        }
        return $this->dispatcher;
    }

    public function getPathVariablesAttributeName(): ?string
    {
        return $this->pathVariablesAttributeName;
    }

    public function setPathVariablesAttributeName(?string $name): self
    {
        $this->pathVariablesAttributeName = $name;
        return $this;
    }

    public function getTrailingSlashMode(): TrailingSlashMode
    {
        return $this->trailingSlashMode;
    }

    public function setTrailingSlashMode(TrailingSlashMode $mode): self
    {
        $this->trailingSlashMode = $mode;
        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @return self
     */
    public function setRequest(ServerRequestInterface $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @param ResponseInterface $response
     * @return self
     */
    public function setResponse(ResponseInterface $response): self
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @param TransmitterInterface $transmitter
     * @return self
     */
    public function setTransmitter(TransmitterInterface $transmitter): self
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
