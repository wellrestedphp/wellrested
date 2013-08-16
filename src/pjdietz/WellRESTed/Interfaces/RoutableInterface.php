<?php

namespace pjdietz\WellRESTed\Interfaces;

interface RoutableInterface
{
    /** @return string  HTTP request method (e.g., GET, POST, PUT). */
    public function getMethod();

    /** @return string The path component of a URI for this Routeable */
    public function getPath();

    /** @return int The number of times a router has dispatched this Routable */
    public function getRouteDepth();

    /** Increase the instance's internal count of its depth in nested route tables */
    public function incrementRouteDepth();
}
