<?php

namespace WellRESTed\Routing;

interface RouterInterface extends MiddlewareInterface
{
    /**
     * Register middleware with the router for a given path and method.
     *
     * $method may be:
     * - A single verb ("GET"),
     * - A comma-separated list of verbs ("GET,PUT,DELETE")
     * - "*" to indicate any method.
     * @see MethodMapInterface::register
     *
     * $target may be:
     * - An exact path (e.g., "/path/")
     * - An prefix path ending with "*"" ("/path/*"")
     * - A URI template with variables enclosed in "{}" ("/path/{id}")
     * - A regular expression ("~/cat/([0-9]+)~")
     *
     * $middleware may be:
     * - An instance implementing MiddlewareInterface
     * - A string containing the fully qualified class name of a class
     *     implementing MiddlewareInterface
     * - A callable that returns an instance implementing MiddleInterface
     * - A callable maching the signature of MiddlewareInteraface::dispatch
     * @see DispatchedInterface::dispatch
     *
     * @param string $target Request target or pattern to match
     * @param string $method HTTP method(s) to match
     * @param mixed $middleware Middleware to dispatch
     * @return self
     */
    public function register($method, $target, $middleware);
}
