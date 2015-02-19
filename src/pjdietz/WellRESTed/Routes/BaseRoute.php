<?php

/**
 * pjdietz\WellRESTed\BaseRoute
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Routes;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;

/**
 * Base class for Routes.
 */
abstract class BaseRoute implements HandlerInterface
{
    /** @var callable|string|HandlerInterface HandlerInterface to dispatch */
    private $target;

    /**
     * Create a new route that will dispatch an instance of the given handelr class.
     *
     * $target may be:
     * - A callable expecting no arguments that returns a HandlerInterface
     * - A string containing the fully qualified class of a HandlerInterface
     * - A HandlerInterface
     *
     * @param callable|string|HandlerInterface $target HandlerInterface to dispatch
     */
    public function __construct($target)
    {
        $this->target = $target;
    }

    /**
     * Instantiate and return an instance of the assigned HandlerInterface
     *
     * @throws \UnexpectedValueException
     * @return HandlerInterface
     */
    protected function getTarget()
    {
        if (is_callable($this->target)) {
            $callable = $this->target;
            $target = $callable();
        } elseif (is_string($this->target)) {
            $className = $this->target;
            $target = new $className();
        } else {
            $target = $this->target;
        }
        if ($target instanceof HandlerInterface) {
            return $target;
        } else {
            throw new \UnexpectedValueException("Target class must implement HandlerInterface");
        }
    }
}
