<?php

namespace WellRESTed\Routing\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
abstract class Route implements RouteInterface
{
    /** @var string */
    protected $target;
    /** @var MethodMap  */
    protected $methodMap;

    public function __construct(string $target, MethodMap $methodMap)
    {
        $this->target = $target;
        $this->methodMap = $methodMap;
    }

    /**
     * Path, partial path, or pattern to match request paths against.
     *
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Register a dispatchable (handler or middleware) with a method.
     *
     * $method may be:
     * - A single verb ("GET"),
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method.
     *
     * $dispatchable may be anything a Dispatcher can dispatch.
     * @see DispatcherInterface::dispatch
     *
     * @param string $method
     * @param mixed $dispatchable
     */
    public function register(string $method, $dispatchable): void
    {
        $this->methodMap->register($method, $dispatchable);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $map = $this->methodMap;
        return $map($request, $response, $next);
    }
}
