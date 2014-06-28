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

    public function getResponse(RoutableInterface $request)
    {
        if (preg_match($this->getPattern(), $request->getPath(), $matches)) {
            $target = $this->getTarget($request);
            if ($target) {
                $target->setArguments($matches);
                return $target->getResponse($request);
            }
        }
        return null;
    }

    // ------------------------------------------------------------------------

    protected function getPattern()
    {
        return $this->pattern;
    }

}
