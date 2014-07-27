<?php

/**
 * pjdietz\WellRESTed\BaseRoute
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2014 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Routes;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;

/**
 * Base class for Routes.
 */
abstract class BaseRoute implements HandlerInterface
{
    /** @var string Fully qualified name for the interface for handlers */
    const HANDLER_INTERFACE = '\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface';

    /** @var string Fully qualified classname of the HandlerInterface to dispatch */
    private $targetClassName;

    /**
     * Create a new route that will dispatch an instance of the given handelr class.
     *
     * @param string $targetClassName Fully qualified name to a handler class.
     */
    public function __construct($targetClassName)
    {
        $this->targetClassName = $targetClassName;
    }

    /**
     * Instantiate and return an instance of the assigned HandlerInterface
     *
     * @throws \UnexpectedValueException
     * @return HandlerInterface
     */
    protected function getTarget()
    {
        if (is_subclass_of($this->targetClassName, self::HANDLER_INTERFACE)) {
            /** @var HandlerInterface $target */
            $target = new $this->targetClassName();
            return $target;
        } else {
            throw new \UnexpectedValueException("Target class must implement HandlerInterface");
        }
    }

}
