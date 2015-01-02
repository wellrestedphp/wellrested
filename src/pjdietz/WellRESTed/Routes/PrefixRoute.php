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
     * @param string|array $prefixes Path or list of paths the request must match
     * @param string $targetClassName Fully qualified name to an autoloadable handler class.
     * @throws \InvalidArgumentException
     */
    public function __construct($prefixes, $targetClassName)
    {
        parent::__construct($targetClassName);
        if (is_string($prefixes)) {
            $this->prefixes = array($prefixes);
        } elseif (is_array($prefixes)) {
            $this->prefixes = $prefixes;
        } else {
            throw new InvalidArgumentException("$prefixes must be a string or array of string");
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
                $target = $this->getTarget();
                return $target->getResponse($request, $args);
            }
        }
        return null;
    }

    /**
     * Returns the path prefixes this maps to a target handler.
     *
     * @return array Array of path prefixes.
     */
    public function getPrefixes()
    {
        return $this->prefixes;
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
}
