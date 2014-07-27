# HandlerInterface

Much of WellRESTed 2 centers around the [`HandlerInterface`](../src/pjdietz/WellRESTed/Interfaces/HandlerInterface.php) which has only one method that you need to implement:

```php
/**
 * @param RequestInterface $request The request to respond to.
 * @param array|null $args Optional additional arguments.
 * @return ResponseInterface The handled response.
 */
public function getResponse(RequestInterface $request, array $args = null);
```

## Hello, World!

Here's a really simplistic example of a "hello world" handler.

```php
class HelloWorldHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = new Response(200, "Hello, world!");
        return $response;
    }
}
```

You can plug this into a [`Router`](../src/pjdietz/WellRESTed/Router.php), and the router will always respond with "Hello, world!".

```php
$router = new Router();
$router->addRoute(new HelloWorldHandler());
$router->respond();
```

### But there's no route?

Here's the cool thing about how routing works in WellRESTed 2: The route classes implement [`HandlerInterface`](../src/pjdietz/WellRESTed/Interfaces/HandlerInterface.php). When the `Router` iterates through its list of routes, it calls `getResponse()` on each one until it gets a non-`null` return value. At this point, it returns that response (or outputs it in the case of `Router::respond()`).

Each time the router calls `getResponse()` on a route that doesn't match request, the route returns `null` to indicate that something else will need to handle this.

Let's add another class to demonstrate.

```php
class DoNothingHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        // THE GOGGLES DO NOTHING!
        return null;
    }
}
```

```php
$router = new Router();
$router->addRoute(new DoNothingHandler());
$router->addRoute(new HelloWorldHandler());
$router->respond();
```

This router will still always respond with "Hello, world!" even though the router will try `DoNothingHandler` first because `DoNothingHandler` returns `null`.

### 404'ed!

If none of the routes in a router return a non-`null` value, what happens?

If you're calling `Router::respond()`, you will **always** get a response. `Router::respond()` is a shorthand method that will output the response made in `Router::getNoRouteResponse()` if it gets through its entire route table and finds no matches.

If you want to customize the default 404 response, you can either subclass `Router` and redefine `Router::getNoRouteResponse()`, or you can create a [`HandlerInterface`](../src/pjdietz/WellRESTed/Interfaces/HandlerInterface.php) like our `HelloWorldHandler` that always returns a response with a `404 Not Found` status code and add it to the router **last**. (Remember: a router evaluates its routes in the order you add them.)

```php
class NotFoundHandler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $response = new Response(400);
        $response->setBody("No resource at " $request->getPath());
        return $response;
    }
}
```

```php
$router = new Router();
$router->addRoute(/*...Real route... */);
$router->addRoute(/*...Real route... */);
$router->addRoute(/*...Real route... */);
$router->addRoute(new NotFoundHandler());
$router->respond();
```

## Nested Routers

`Router::respond()` is a shorthand method that wraps `Router::getResponse()`, which [`Router`](../src/pjdietz/WellRESTed/Router.php) must have because it too implements [`HandlerInterface`](../src/pjdietz/WellRESTed/Interfaces/HandlerInterface.php). This means that you can break your router into subrouters.


```php
$router = new Router();
$router->addRoutes(array(
    new TemplateRoute("/cats/*", "CatRouter"),
    new TemplateRoute("/dogs/*", "DogRouter"),
    new NotFoundHandler()
));
$router->repond();
```
