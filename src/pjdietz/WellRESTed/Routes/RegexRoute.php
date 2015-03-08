<?php

/**
 * pjdietz\WellRESTed\RegexRout
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2015 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed\Routes;

use pjdietz\WellRESTed\Exceptions\ParseException;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;

/**
 * Maps a regular expression pattern for a URI path to a Handler
 */
class RegexRoute extends BaseRoute
{
    /** @var string Regular expression pattern for the route. */
    private $pattern;

    /**
     * Create a new route mapping a regex pattern to a handler.
     *
     * @param string $pattern Regular expression the path must match.
     * @param mixed $target Handler to dispatch
     * @throws \InvalidArgumentException
     *
     * @see BaseRoute for details about $target
     */
    public function __construct($pattern, $target)
    {
        parent::__construct($target);
        $this->pattern = $pattern;
    }

    // ------------------------------------------------------------------------
    /* HandlerInterface */

    /**
     * Return the handled response or null.
     *
     * A null return value indicates that this route failed to match the request.
     *
     * @param RequestInterface $request The request to respond to.
     * @param array|null $args Optional additional arguments.
     * @return ResponseInterface The handled response.
     * @throws ParseException
     */
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $matched = @preg_match($this->getPattern(), $request->getPath(), $matches);
        if ($matched) {
            if (is_null($args)) {
                $args = array();
            }
            $args = array_merge($args, $matches);
            return $this->getResponseFromTarget($request, $args);
        } elseif ($matched === false) {
            throw new ParseException("Invalid regular expression: " . $this->getPattern());
        }
        return null;
    }

    // ------------------------------------------------------------------------

    /**
     * Return the regex pattern for the route.
     *
     * @return string Regex pattern
     */
    protected function getPattern()
    {
        return $this->pattern;
    }
}
