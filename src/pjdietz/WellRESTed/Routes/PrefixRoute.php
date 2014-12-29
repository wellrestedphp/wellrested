<?php

/**
 * pjdietz\WellRESTed\PrefixRoute
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Routes;

use pjdietz\WellRESTed\Interfaces\RequestInterface;

/**
 * Maps a list of static URI paths to a Handler
 */
class PrefixRoute extends StaticRoute
{
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
            if (substr($requestPath, 0, strlen($path)) === $path) {
                $target = $this->getTarget();
                return $target->getResponse($request, $args);
            }
        }
        return null;
    }
}
