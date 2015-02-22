<?php

/**
 * pjdietz\WellRESTed\StaticRoute
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Routes;

use InvalidArgumentException;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\Routes\StaticRouteInterface;

/**
 * Maps a list of static URI paths to a Handler
 */
class StaticRoute extends BaseRoute implements StaticRouteInterface
{
    /** @var array List of static URI paths */
    private $paths;

    /**
     * Create a new StaticRoute for a given path or paths and a handler class.
     *
     * @param string|array $path Path or list of paths the request must match
     * @param string $target Fully qualified name to an autoloadable handler class.
     * @throws \InvalidArgumentException
     */
    public function __construct($path, $target)
    {
        parent::__construct($target);
        if (is_string($path)) {
            $this->paths = array($path);
        } elseif (is_array($path)) {
            $this->paths = $path;
        } else {
            throw new InvalidArgumentException("$path must be a string or array of strings");
        }
    }

    // ------------------------------------------------------------------------
    /* HandlerInterface */

    /**
     * Return the response issued by the handler class or null.
     *
     * A null return value indicates that this route failed to match the request.
     *
     * @param RequestInterface $request
     * @param array $args
     * @return null|\pjdietz\WellRESTed\Interfaces\ResponseInterface
     */
    public function getResponse(RequestInterface $request, array $args = null)
    {
        if (in_array($request->getPath(), $this->paths)) {
            return $this->getResponseFromTarget($request, $args);
        }
        return null;
    }

    // ------------------------------------------------------------------------
    /* StaticRouteInterface */

    /**
     * Returns the paths this maps to a target handler.
     *
     * @return array Array of paths.
     */
    public function getPaths()
    {
        return $this->paths;
    }
}
