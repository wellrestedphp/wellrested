<?php

namespace pjdietz\WellRESTed\Routes;

use pjdietz\WellRESTed\Interfaces\RoutableInterface;

class RegexRoute extends BaseRoute
{
    private $pattern;

    /**
     * @param string $pattern Regular expression the path must match.
     * @param string $targetClassName Fully qualified name to an autoloadable handler class.
     */
    public function __construct($pattern, $targetClassName)
    {
        parent::__construct($targetClassName);
        $this->pattern = $pattern;
    }

    // ------------------------------------------------------------------------
    /* DispatcherInterface */

    public function getResponse(RoutableInterface $request, $args = null)
    {
        if (preg_match($this->getPattern(), $request->getPath(), $matches)) {
            $target = $this->getTarget();
            if (is_null($args)) {
                $args = array();
            }
            $args = array_merge($args, $matches);
            return $target->getResponse($request, $args);
        }
        return null;
    }

    // ------------------------------------------------------------------------

    protected function getPattern()
    {
        return $this->pattern;
    }

}
