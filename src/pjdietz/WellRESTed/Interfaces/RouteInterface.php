<?php

/**
 * pjdietz\WellRESTed\Interfaces\RouteInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces;

/**
 * Interface for a route to relate a pattern for matching a URI to a handler class.
 * @package pjdietz\WellRESTed
 */
interface RouteInterface
{
    /** @return string Regex pattern used to match the URI */
    public function getPattern();

    /** @para string $pattern Regex pattern used to match the URI */
    public function setPattern($pattern);

    /** @return string Fully qualified name of the class the route will dispatch. */
    public function getTarget();

    /** @param string $className  Fully qualified name of the class the route will dispatch. */
    public function setTarget($className);
}
