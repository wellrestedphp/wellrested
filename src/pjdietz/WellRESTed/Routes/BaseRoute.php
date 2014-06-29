<?php

namespace pjdietz\WellRESTed\Routes;

use pjdietz\WellRESTed\Interfaces\DispatcherInterface;

/**
 * Base class for Routes.
 * @package pjdietz\WellRESTed\Routes
 */
abstract class BaseRoute implements DispatcherInterface
{
    /** @var string  Fully qualified name for the interface for handlers */
    const DISPATCHER_INTERFACE = '\\pjdietz\\WellRESTed\\Interfaces\\DispatcherInterface';

    /** @var string */
    private $targetClassName;

    /**
     * @param string $targetClassName Fully qualified name to an autoloadable handler class.
     */
    public function __construct($targetClassName)
    {
        $this->targetClassName = $targetClassName;
    }

    /**
     * @return DispatcherInterface
     * @throws \UnexpectedValueException
     */
    protected function getTarget()
    {
        if (is_subclass_of($this->targetClassName, self::DISPATCHER_INTERFACE)) {
            /** @var DispatcherInterface $target */
            $target = new $this->targetClassName();
            return $target;
        } else {
            throw new \UnexpectedValueException("Target class must implement DispatcherInterface");
        }
    }

}
