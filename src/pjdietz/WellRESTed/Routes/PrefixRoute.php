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
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Interfaces\Routes\PrefixRouteInterface;

/**
 * Maps a list of static URI paths to a Handler
 */
class PrefixRoute extends BaseRoute implements PrefixRouteInterface
{
    /** @var string[] List of static URI path prefixes*/
    private $prefixes;

    /**
     * Create a new PrefixRoute for a given prefix or prefixes and a handler class.
     *
     * @param string|string[] $prefix Path or list of paths the request must match
     * @param mixed $target Handler to dispatch
     * @throws \InvalidArgumentException
     *
     * @see BaseRoute for details about $target
     */
    public function __construct($prefix, $target)
    {
        parent::__construct($target);
        if (is_string($prefix)) {
            $this->prefixes = array($prefix);
        } elseif (is_array($prefix)) {
            $this->prefixes = $prefix;
        } else {
            throw new InvalidArgumentException("$prefix must be a string or array of string");
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
     * Returns the path prefixes the instance maps to a target handler.
     *
     * @return string[] List array of path prefixes.
     */
    public function getPrefixes()
    {
        return $this->prefixes;
    }
}
