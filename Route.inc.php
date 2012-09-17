<?php

namespace wellrested;

/*******************************************************************************
 * Route
 *
 * @package WellRESTed
 *
 ******************************************************************************/

class Route {

    const RE_SLUG = '[0-9a-zA-Z\-_]+';
    const RE_NUM = '[0-9]+';
    const RE_ALPHA = '[a-zA-Z]+';
    const RE_ALPHANUM = '[0-9a-zA-Z]+';

    const URI_TEMPLATE_EXPRESSION_RE = '/{([a-zA-Z]+)}/';

    /**
     * Regular Expression to use to validate a template variable.
     * @var string
     */
    static public $defaultVariablePattern = self::RE_SLUG;

    /**
     * Regular expression used to match a Request URI path component
     * @var string
     */
    public $pattern;

    /**
     * Name of the Handler class to use
     * @var string
     */
    public $handler;

    /**
     * The path to the source file defing the handler class.
     * @var string
     */
    public $handlerPath;

    /**
     * @param $pattern
     * @param $handler
     * @param $handlerPath
     */
    public function __construct($pattern, $handler, $handlerPath) {

        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->handlerPath = $handlerPath;

    } // __construct

    /**
     * Create a new Route using a URI template to generate the pattern.
     *
     * @param string $uriTemplate
     * @param string $handler
     * @param string $handlerPath
     * @param array $variables
     * @throws \Exception
     * @return Route
     */
    static public function newFromUriTemplate($uriTemplate, $handler,
                                                $handlerPath=null,
                                                $variables=null) {

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
            if (preg_match(self::URI_TEMPLATE_EXPRESSION_RE,
                $part, $matches)) {

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

                    $pattern .= sprintf('(?<%s>%s)', $variableName,
                        $variablePattern);

                } else {
                    // Not sure why this would happen.
                    throw new \Exception('Invalid URI Template.');
                }

            } else {
                // This part is a literal.
                $pattern .= $part;
            }

        }

        $pattern = '/^' . $pattern . '$/';

        $klass = __CLASS__;
        $route = new $klass($pattern, $handler, $handlerPath);
        return $route;

    } // newFromUriTemplate()

} // Route

?>
