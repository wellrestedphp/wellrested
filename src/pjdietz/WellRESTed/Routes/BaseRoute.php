<?php

/**
 * pjdietz\WellRESTed\BaseRoute
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Routes;

use pjdietz\WellRESTed\HandlerUnpacker;
use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;

/**
 * Base class for Routes.
 */
abstract class BaseRoute implements HandlerInterface
{
    /** @var callable|string|HandlerInterface Handler to dispatch */
    private $target;

    /**
     * Create a new route that will dispatch an instance of the given handler.
     *
     * $target may be:
     * - A callable expecting no arguments that returns a HandlerInterface
     * - A string containing the fully qualified class of a HandlerInterface
     * - A HandlerInterface instance
     *
     * @param mixed $target Handler to dispatch
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * Return the handled response from the target.
     *
     * @param RequestInterface $request The request to respond to.
     * @param array|null $args Optional additional arguments.
     * @return ResponseInterface The response.
     */
    protected function getResponseFromTarget(RequestInterface $request, array $args = null)
    {
        $unpacker = new HandlerUnpacker();
        $target = $unpacker->unpack($this->target, $request, $args);
        if (!is_null($target) && $target instanceof HandlerInterface) {
            return $target->getResponse($request, $args);
        }
        return $target;
    }
}
