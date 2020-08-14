<?php

namespace WellRESTed\Routing\Route;

use WellRESTed\MiddlewareInterface;

/**
 * @internal
 */
interface RouteInterface extends MiddlewareInterface
{
    /** Matches when request path is an exact match to entire target */
    const TYPE_STATIC = 0;
    /** Matches when request path is an exact match to start of target */
    const TYPE_PREFIX = 1;
    /** Matches by request path by pattern and may extract matched varialbes */
    const TYPE_PATTERN = 2;

    /**
     * Path, partial path, or pattern to match request paths against.
     *
     * @return string
     */
    public function getTarget(): string;

    /**
     * Return the RouteInterface::TYPE_ constants that identifies the type.
     *
     * TYPE_STATIC indicates the route MUST match only when the path is an
     * exact match to the route's target. This route type SHOULD NOT
     * provide path variables.
     *
     * TYPE_PREFIX indicates the route MUST match when the route's target
     * appears in its entirety at the beginning of the path.
     *
     * TYPE_PATTERN indicates that matchesRequestTarget MUST be used
     * to determine a match against a given path. This route type SHOULD
     * provide path variables.
     *
     * @return int One of the RouteInterface::TYPE_ constants.
     */
    public function getType(): int;

    /**
     * Return an array of variables extracted from the path most recently
     * passed to matchesRequestTarget.
     *
     * If the path does not contain variables, or if matchesRequestTarget
     * has not yet been called, this method MUST return an empty array.
     *
     * @return array
     */
    public function getPathVariables(): array;

    /**
     * Examines a request target to see if it is a match for the route.
     *
     * @param string $requestTarget
     * @return boolean
     * @throw  \RuntimeException Error occurred testing the target such as an
     *      invalid regular expression
     */
    public function matchesRequestTarget(string $requestTarget): bool;

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
    public function register(string $method, $dispatchable): void;
}
