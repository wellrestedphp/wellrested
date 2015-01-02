<?php

/**
 * pjdietz\WellRESTed\Interfaces\ResponseInterface
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Interfaces\Routes;

interface StaticRouteInterface
{
    /**
     * Returns the target class this maps to.
     *
     * @return string Fully qualified name for a HandlerInterface
     */
    public function getHandler();

    /**
     * Returns the paths this maps to a target handler.
     *
     * @return array Array of paths.
     */
    public function getPaths();
}
