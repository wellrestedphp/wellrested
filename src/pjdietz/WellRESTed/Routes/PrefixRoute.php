<?php

/**
 * pjdietz\WellRESTed\Routes\PrefixRoute
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Routes;

use InvalidArgumentException;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\Routes\PrefixRouteInterface;

/**
 * Maps a list of static URI paths to a Handler
 */
class PrefixRoute extends BaseRoute implements PrefixRouteInterface
{
    /** @var array List of static URI paths */
    private $prefixes;

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
            $this->prefixes = array($path);
        } elseif (is_array($path)) {
            $this->prefixes = $path;
        } else {
            throw new InvalidArgumentException("$path must be a string or array of string");
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
        foreach ($this->prefixes as $prefix) {
            if (strrpos($requestPath, $prefix, -strlen($requestPath)) !== false) {
                return $this->getResponseFromTarget($request, $args);
            }
        }
        return null;
    }

    // ------------------------------------------------------------------------
    /* PrefixRouteInterface */

    /**
     * Returns the path prefixes this maps to a target handler.
     *
     * @return array Array of path prefixes.
     */
    public function getPrefixes()
    {
        return $this->prefixes;
    }
}
