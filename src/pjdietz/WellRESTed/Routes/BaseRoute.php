<?php

namespace pjdietz\WellRESTed\Routes;

use pjdietz\WellRESTed\Interfaces\DispatcherInterface;
use pjdietz\WellRESTed\Interfaces\RoutableInterface;
use pjdietz\WellRESTed\Interfaces\RouteTargetInterface;

/**
 * Base class for Routes.
 * @package pjdietz\WellRESTed\Routes
 */
abstract class BaseRoute implements DispatcherInterface
{
    /** @var string  Fully qualified name for the interface for handlers */
    const ROUTE_TARGET_INTERFACE = '\\pjdietz\\WellRESTed\\Interfaces\\RouteTargetInterface';

    /** @var string */
    private $targetClassName;

    /**
     * @param string $targetClassName Fully qualified name to an autoloadable handler class.
     */
    public function __construct($targetClassName)
    {
        $this->targetClassName = $targetClassName;
    }

    protected function getTarget(RoutableInterface $routable)
    {
        if (is_subclass_of($this->targetClassName, self::ROUTE_TARGET_INTERFACE)) {
            /** @var RouteTargetInterface $target */
            $target = new $this->targetClassName();
            $target->setRequest($routable);
            return $target;
        }
        return null;
    }

}
