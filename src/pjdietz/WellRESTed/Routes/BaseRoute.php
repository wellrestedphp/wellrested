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
     * Return an instance of the assigned handler
     *
     * @throws \UnexpectedValueException
     * @return HandlerInterface
     */
    protected function getTarget()
    {
        $unpacker = new HandlerUnpacker();
        return $unpacker->unpack($this->target);
    }
}
