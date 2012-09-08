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

    static public $defaultVariablePattern = self::RE_SLUG;

    public $pattern;
    public $handler;
    public $handlerPath;
    public $uriTemplate;

    public function __construct($pattern, $handler, $handlerPath=null) {

        $this->pattern = $pattern;
        $this->handler = $handler;
        $this->handlerPath = $handlerPath;

    } // __construct

    static public function newFromUriTemplate($uriTemplate, $handler, $handlerPath=null, $variables=null) {

        $pattern = '';

        if ($uriTemplate[0] === '/') {
            $parts = explode('/', substr($uriTemplate, 1));
        } else {
            $parts = explode('/', $uriTemplate);
        }

        $expressionPattern = '/{([a-zA-Z]+)}/';

        foreach ($parts as $part) {

            $pattern .= '\/';

            if (preg_match($expressionPattern, $part, $matches)) {

                $variablePattern = self::$defaultVariablePattern;

                if (count($matches) === 2) {

                    $variableName = $matches[1];

                    if (isset($groups[$variableName])) {
                        $variablePattern = $groups[$variableName];
                    }

                }

                $pattern .= sprintf('(?<%s>%s)', $variableName, $variablePattern);

            } else {

                $pattern .= $part;

            }

        }

        $pattern = '/^' . $pattern . '$/';

        $klass = __CLASS__;
        $route = new $klass($pattern, $handler, $handlerPath);
        $route->uriTemplate = $uriTemplate;
        return $route;

    } // newFromUriTemplate()

} // Route

?>
