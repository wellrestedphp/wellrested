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
        $requestPath = $request->getPath();
        foreach ($this->paths as $path) {
            if ($path === $requestPath) {
                $target = $this->getTarget();
                return $target->getResponse($request, $args);
            }
        }
        return null;
    }

    /**
     * Returns the target class this maps to.
     *
     * @return string Fully qualified name for a HandlerInterface
     */
    public function getHandler()
    {
        return $this->getTarget();
    }

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
