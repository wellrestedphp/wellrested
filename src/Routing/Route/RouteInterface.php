<?php

namespace WellRESTed\Routing\Route;

use WellRESTed\MiddlewareInterface;
use WellRESTed\Routing\MethodMapInterface;

interface RouteInterface extends MiddlewareInterface
{
    /** Matches when path is an exact match only */
    const TYPE_STATIC = 0;
    /** Matches when path has the expected beginning */
    const TYPE_PREFIX = 1;
    /** Matches by pattern. Use matchesRequestTarget to test for matches */
    const TYPE_PATTERN = 2;

    /**
     * @return string
     */
    public function getTarget();

    /**
     * Return the RouteInterface::TYPE_ contants that identifies the type.
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
    public function getType();

    /**
     * Return an array of variables extracted from the path most recently
     * passed to  matchesRequestTarget.
     *
     * If the path does not contain variables, or if matchesRequestTarget
     * has not yet been called, this method MUST return an empty array.
     *
     * @return array
     */
    public function getPathVariables();

    /**
     * Return the instance mapping methods to middleware for this route.
     *
     * @return MethodMapInterface
     */
    public function getMethodMap();

    /**
     * Examines a request target to see if it is a match for the route.
     *
     * @param string $requestTarget
     * @return boolean
     * @throw  \RuntimeException Error occured testing the target such as an
     *      invalid regular expression
     */
    public function matchesRequestTarget($requestTarget);
}
