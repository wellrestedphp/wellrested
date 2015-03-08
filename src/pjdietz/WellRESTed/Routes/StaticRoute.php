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
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Interfaces\Routes\StaticRouteInterface;

/**
 * Maps a list of static URI paths to a Handler
 */
class StaticRoute extends BaseRoute implements StaticRouteInterface
{
    /** @var string[] List of static URI paths */
    private $paths;

    /**
     * Create a new StaticRoute for a given path or paths and a handler.
     *
     * @param string|array $path Path or list of paths the request must match
     * @param mixed $target Handler to dispatch
     * @throws \InvalidArgumentException
     *
     * @see BaseRoute for details about $target
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
     * Return the handled response.
     *
     * @param RequestInterface $request The request to respond to.
     * @param array|null $args Optional additional arguments.
     * @return ResponseInterface
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
     * Returns the paths the instance maps to a target handler.
     *
     * @return string[] List array of paths.
     */
    public function getPaths()
    {
        return $this->paths;
    }
}
