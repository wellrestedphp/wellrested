<?php

namespace pjdietz\WellRESTed\Routes;

use InvalidArgumentException;
use pjdietz\WellRESTed\Interfaces\RoutableInterface;

/**
 * Class StaticRoute
 * @package pjdietz\WellRESTed\Routes
 */
class StaticRoute extends BaseRoute
{
    private $paths;

    /**
     * @param string|array $paths Path or list of paths the request must match
     * @param string $targetClassName Fully qualified name to an autoloadable handler class.
     * @throws \InvalidArgumentException
     */
    public function __construct($paths, $targetClassName)
    {
        parent::__construct($targetClassName);
        if (is_string($paths)) {
            $this->paths = array($paths);
        } elseif (is_array($paths)) {
            $this->paths = $paths;
        } else {
            throw new InvalidArgumentException("$paths must be a string or array of string");
        }
    }

    // ------------------------------------------------------------------------
    /* DispatcherInterface */

    public function getResponse(RoutableInterface $request, $args = null)
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

}
