<?php

namespace WellRESTed\Routing;

interface MethodMapInterface extends MiddlewareInterface
{
    /**
     * Register middleware with a method.
     *
     * $method may be:
     * - A single verb ("GET"),
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method.
     *
     * $middleware may be:
     * - An instance implementing MiddlewareInterface
     * - A string containing the fully qualified class name of a class
     *     implementing MiddlewareInterface
     * - A callable that returns an instance implementing MiddleInterface
     * - A callable maching the signature of MiddlewareInteraface::dispatch
     * @see DispatchedInterface::dispatch
     *
     * $middleware may also be null, in which case any previously set
     * middleware for that method or methods will be unset.
     *
     * @param string $method
     * @param mixed $middleware
     */
    public function setMethod($method, $middleware);
}
