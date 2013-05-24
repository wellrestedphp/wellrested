<?php

namespace pjdietz\WellRESTed;

/**
 * Interface for a route to relate a pattern for matching a URI to a handler class.
 * @package pjdietz\WellRESTed
 */
interface RouteInterface
{
    /**
     * Return the regex pattern used to match the URI
     *
     * @return string
     */
    public function getPattern();

    /**
     * Provide a regex pattern that matches the URI
     *
     * @para string $pattern
     */
    public function setPattern($pattern);

    /**
     * Return the name of the class the route will dispatch.
     *
     * @return string
     */
    public function getHandler();

    /**
     * Provide the classname to instantiate to handle the route.
     *
     * @param string $className
     */
    public function setHandler($className);
}
