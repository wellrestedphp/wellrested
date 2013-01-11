<?php

namespace pjdietz\WellRESTed;

require_once(dirname(__FILE__) . '/Request.inc.php');
require_once(dirname(__FILE__) . '/Response.inc.php');
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
     * @param Request $request
     * @return Response
     */
    public function getResponse($request=null) {

        if (is_null($request)) {
            $request = Request::getRequest();
        }

        $path = $request->path;

        foreach ($this->routes as $route) {

            if (preg_match($route->pattern, $path, $matches)) {

                $klass = $route->handler;

                if (!class_exists($klass)) {
                    require_once($route->handlerPath);
                }

                $handler = new $klass($request, $matches);
                return $handler->response;

            }

        }

        return $this->getNoRouteResponse($request);

    } // getRequestHandler()

    /**
     * @param Request $request
     * @return Response
     */
    protected function getNoRouteResponse(Request $request) {

        $response = new Response(404);
        $response->body = 'No resource at ' . $request->uri;
        return $response;

    }

}

?>
