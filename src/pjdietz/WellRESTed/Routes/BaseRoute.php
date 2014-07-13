<?php

namespace pjdietz\WellRESTed\Routes;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;

/**
 * Base class for Routes.
 */
abstract class BaseRoute implements HandlerInterface
{
    /** @var string  Fully qualified name for the interface for handlers */
    const DISPATCHER_INTERFACE = '\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface';

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
     * @return HandlerInterface
     * @throws \UnexpectedValueException
     */
    protected function getTarget()
    {
        if (is_subclass_of($this->targetClassName, self::DISPATCHER_INTERFACE)) {
            /** @var HandlerInterface $target */
            $target = new $this->targetClassName();
            return $target;
        } else {
            throw new \UnexpectedValueException("Target class must implement HandlerInterface");
        }
    }

}
