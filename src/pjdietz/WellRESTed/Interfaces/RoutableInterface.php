<?php

namespace pjdietz\WellRESTed\Interfaces;

interface RoutableInterface extends RequestInterface
{
    /** @return int The number of times a router has dispatched this Routable */
    public function getRouteDepth();

    /** Increase the instance's internal count of its depth in nested route tables */
    public function incrementRouteDepth();
}
