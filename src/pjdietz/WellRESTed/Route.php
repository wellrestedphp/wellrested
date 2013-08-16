<?php

/**
 * pjdietz\WellRested\Route
 *
 * @author PJ Dietz <pj@pjdietz.com>
 * @copyright Copyright 2013 by PJ Dietz
 * @license MIT
 */

namespace pjdietz\WellRESTed;

use pjdietz\WellRESTed\Exceptions\WellRESTedException;
use pjdietz\WellRESTed\Interfaces\RouteInterface;

/**
 * A Route connects a URI pattern to a Handler.
 */
class Route implements RouteInterface
{
    /**
     * Regular expression matching URL friendly characters (i.e., letters,
     * digits, hyphen and underscore)
     */
    const RE_SLUG = '[0-9a-zA-Z\-_]+';
    /** Regular expression matching digitis */
    const RE_NUM = '[0-9]+';
    /** Regular expression matching letters */
    const RE_ALPHA = '[a-zA-Z]+';
    /** Regular expression matching letters and digits */
    const RE_ALPHANUM = '[0-9a-zA-Z]+';
    /** Regular expression matching a URI template variable (e.g., {id}) */
    const URI_TEMPLATE_EXPRESSION_RE = '/{([a-zA-Z]+)}/';
    /**
     * Default regular expression used to match template variable
     *
     * @property string
     */
    static public $defaultVariablePattern = self::RE_SLUG;
    /**
     * Regular expression used to match a Request URI path component
     *
     * @var string
     */
    private $pattern;
    /**
     * Name of the RouteTarget class to dispatch when the pattern is matched.
     *
     * @var string
     */
    private $target;

    /**
     * Create a new Route
     *
     * @param string $pattern Regex string to match against the request path
     * @param string $target String name of the RouteTargetInterface to dispatch
     */
    public function __construct($pattern, $target)
    {
        $this->pattern = $pattern;
        $this->target = $target;
    }

    /**
     * Create a new Route using a URI template to generate the pattern.
     *
     * @param string $uriTemplate
     * @param string $target
     * @param array $variables
     * @throws WellRESTedException
     * @return Route
     */
    static public function newFromUriTemplate(
        $uriTemplate,
        $target,
        $variables = null
    ) {

        $pattern = '';

        // Explode the template into an array of path segments.
        if ($uriTemplate[0] === '/') {
            $parts = explode('/', substr($uriTemplate, 1));
        } else {
            $parts = explode('/', $uriTemplate);
        }

        foreach ($parts as $part) {

            $pattern .= '\/';

            // Is this part an expression or a literal?
            if (preg_match(self::URI_TEMPLATE_EXPRESSION_RE, $part, $matches)) {

                // This part of the path is an expresion.

                if (count($matches) === 2) {

                    // Locate the name for the variable from the template.
                    $variableName = $matches[1];

                    // If the caller passed an array with this variable name
                    // as a key, use its value for the pattern here.
                    // Otherwise, use the class's current default.
                    if (isset($variables[$variableName])) {
                        $variablePattern = $variables[$variableName];
                    } else {
                        $variablePattern = self::$defaultVariablePattern;
                    }

                    $pattern .= sprintf(
                        '(?<%s>%s)',
                        $variableName,
                        $variablePattern
                    );

                } else {
                    // Not sure why this would happen.
                    throw new WellRESTedException('Invalid URI Template.');
                }

            } else {
                // This part is a literal.
                $pattern .= $part;
            }

        }

        $pattern = '/^' . $pattern . '$/';

        return new self($pattern, $target);
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @param string $pattern
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }

    /** @return string  fully qualified name of the RouteTargetInterface to dispatch */
    public function getTarget()
    {
        return $this->target;
    }

    /** @param string $target fully qualified name of the RouteTargetInterface to dispatch */
    public function setTarget($target)
    {
        $this->target = $target;
    }

    /**
     * @return string
     * @deprecated 1.3.0
     * @see \pjdietz\WellRESTed\Route::getTarget()
     */
    public function getHandler()
    {
        return $this->getTarget();
    }

    /**
     * @param string $handler
     * @deprecated 1.3.0
     * @see \pjdietz\WellRESTed\Route::setTarget()
     */
    public function setHandler($handler)
    {
        $this->setTarget($handler);
    }

}
