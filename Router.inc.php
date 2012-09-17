<?php

namespace wellrested;

require_once(dirname(__FILE__) . '/Request.inc.php');
require_once(dirname(__FILE__) . '/Route.inc.php');

/*******************************************************************************
 * Router
 *
 * A Router uses a table of Routes to find the appropriate Handler for a
 * request.
 *
 * @package WellRESTed
 *
 ******************************************************************************/

class Router {

    protected $routes;

    /**
     * Create a new Router.
     */
    public function __construct() {
        $this->routes = array();
    }

    /**
     * Append a new Route instance to the Router's route table.
     * @param $route
     */
    public function addRoute(Route $route) {
        $this->routes[] = $route;
    } // addRoute()

    /**
     * @param string $requestPath
     * @return Handler
     */
    public function getRequestHandler($requestPath=null) {

        if (is_null($requestPath)) {
            $request = Request::getRequest();
            $path = $request->path;
        } else {
            $path = $requestPath;
        }

        foreach ($this->routes as $route) {

            if (preg_match($route->pattern, $path, $matches)) {

                $klass = $route->handler;

                if (!class_exists($klass)) {
                    require_once($route->handlerPath);
                }

                // TODO: Need to rethink this plan. May not have a $request yet.
                return $handler = new $klass($request, $matches);

            }

        }

        return false;

    } // getRequestHandler()

} // Router

?>
