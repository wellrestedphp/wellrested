<?php

namespace wellrested;

require_once(dirname(__FILE__) . '/Request.inc.php');
require_once(dirname(__FILE__) . '/Route.inc.php');

/*******************************************************************************
 * Router
 *
 * @package WellRESTed
 *
 ******************************************************************************/

class Router {

    protected $routes;

    public $handlerPathPattern = '%s.inc.php';

    public function __construct() {
        $this->routes = array();
    }

    protected function getHandlerPath($handler) {
        return sprintf($this->handlerPathPattern, $handler);
    }

    public function addRoute($pattern, $handler, $handlerPath=null) {

        if (is_null($handlerPath)) {
            $handlerPath = $this->getHandlerPath($handler);
        }

        $this->routes[] = new Route($pattern, $handler, $handlerPath);

    } // addRoute()

    public function addUriTemplate($uriTemplate, $handler, $handlerPath=null, $variables=null) {

        if (is_null($handlerPath)) {
            $handlerPath = $this->getHandlerPath($handler);
        }

        $this->routes[] = Route::newFromUriTemplate($uriTemplate, $handler, $handlerPath, $variables);

    } // addUriTemplate()

    public function getRequestHandler($request=null) {

        if (is_null($request)) {
            $request = Request::getRequest();
        }

        $path = $request->path;

        foreach ($this->routes as $route) {

            if (preg_match($route->pattern, $path, $matches)) {

                if (!class_exists($route->handler)) {
                    require_once($route->handlerPath);
                }

                return $handler = new $route->handler($request, $matches);

            }

        }

        return false;

    } // getRequestHandler()

} // Router

?>
