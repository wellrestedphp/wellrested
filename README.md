WellRESTed
==========

[![Build Status](https://travis-ci.org/pjdietz/wellrested.svg?branch=two)](https://travis-ci.org/pjdietz/wellrested)

WellRESTed is a microframework for creating RESTful APIs in PHP. It provides a lightweight yet powerful routing system and classes to make working with HTTP requests and responses clean and easy.

Requirements
------------

- PHP 5.3
- [PHP cURL](http://php.net/manual/en/book.curl.php) for making requests with the `Client` class


Install
-------

Add an entry for "pjdietz/wellrested" to your composer.json file's `require` property. If you are not already using Composer, create a file in your project called "composer.json" with the following content:

```json
{
    "require": {
        "pjdietz/wellrested": "2.*"
    }
}
```

Use Composer to download and install WellRESTed. Run these commands from the directory containing the **composer.json** file.

```bash
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
```

You can now use WellRESTed by including the `vendor/autoload.php` file generated by Composer.


Examples
--------

### Routing

WellRESTed's primary goal is to facilitate mapping of URIs to classes that will provide or accept representations. To do this, create a `Router` instance and load it up with some `Route`s. Each `Route` is simply a mapping of a URI pattern to a class name. The class name represents the `Handler` (any class implementing `HandlerInterface`) which the router will dispatch when it receives a request for the given URI. **The handlers are never instantiated or loaded unless they are needed.**

Here's an example of a Router that will handle two URIs:

```php
// Build the router.
$myRouter = new Router();
$myRouter->addRoutes(array(
    new StaticRoute("/", "\\myapi\\Handlers\\RootHandler")),
    new StaticRoute("/cats/", "\\myapi\\Handlers\\CatCollectionHandler")),
    new TemplateRoute("/cats/{id}/", "\\myapi\\Handlers\\CatItemHandler"))
);
$myRouter->respond();
```

### Building Routes with JSON

WellRESTed also provides a class to construct routes for you based on a JSON description. Here's an example.

```php
$json = <<<'JSON'
{
    "handlerNamespace": "\\myapi\\Handlers",
    "routes": [
        {
            "path": "/",
            "handler": "RootHandler"
        },
        {
            "path": "/cats/",
            "handler": "CatCollectionHandler"
        },
        {
            "tempalte": "/cats/{id}",
            "handler": "CatItemHandler"
        }
    ]
}
JSON;

$builder = new RouteBuilder();
$routes = $builder->buildRoutes($json);

$router = new Router();
$router->addRoutes($routes);
$router->respond();
```

Notice that when you build routes through JSON, you can provide a `handlerNamespace` to be affixed to the front of every `handler`.

### Handlers

Any class that implements `HandlerInterface` may be the handler for a route. This could be a class that builds the actual response, or it could another `Router`.

For most cases, you'll want to use a subclass of the `Handler` class, which provides methods for responding based on HTTP method. When you create your Handler subclass, you will implement a method for each HTTP verb you would like the endpoint to support. For example, if `/cats/` should support `GET`, you would override the `get()` method. For `POST`, `post()`, etc.

If your endpoint should reject particular verbs, no worries. The Handler base class defines the default verb-handling methods to respond with a **405 Method Not Allowed** status.

Here's a simple Handler that matches the first endpoint, `/cats/`.

```php
class CatsCollectionHandler extends \pjdietz\WellRESTed\Handler
{
    protected function get()
    {
        // Read some cats from the database, cache, whatever.
        // ...read these an array as the variable $cats.

        // Set the values for the instance's response member. This is what the
        // Router will eventually output to the client.
        $this->response->setStatusCode(200);
        $this->response->setHeader("Content-Type", "application/json");
        $this->response->setBody(json_encode($cats));
    }

    protected function post()
    {
        // Read from the instance's request member and store a new cat.
        $cat = json_decode($this->request->getBody());
        // ...store $cat to the database...

        // Build a response to send to the client.
        $this->response->setStatusCode(201);
        $this->response->setBody(json_encode($cat));
    }
}
```

This Handler works with the endpoint, `/cats/{id}`. The template for this endpoint has the variable `{id}` in it. The Handler can access path variables through its `args` member, which is an associative array of variables from the URI.

```php
class CatItemHandler extends \pjdietz\WellRESTed\Handler
{
    protected function get()
    {
        // Find a cat ($cat) based on $this->args["id"]
        // ...do lookup here...

        if ($cat) {
            // The cat exists! Let's output a representation.
            $this->response->setStatusCode(200);
            $this->response->setHeader("Content-Type", "application/json");
            $this->response->setBody(json_encode($cat));
        } else {
            // The ID did not match anything.
            $this->response->setStatusCode(404);
            $this->response->setHeader("Content-Type", "text/plain");
            $this->response->setBody("No cat with id " . $this->args["id"]);
        }
    }
}
```

### Responses

You've already seen a `Response` in use in the examples above. You can also a `Response` outside of `Handler`. Let's take a look at creating a new `Response`, setting a header, supplying the body, and outputting.

```php
$resp = new \pjdietz\WellRESTed\Response();
$resp->setStatusCode(200);
$resp->setHeader("Content-type", "text/plain");
$resp->setBody("Hello world!");
$resp->respond();
exit;
```

### Requests

From outside the context of a `Handler`, you can also use the `Request` class to read info for the request sent to the server by using the static method `Request::getRequest()`.

```php
// Call the static method Request::getRequest() to get a reference to the Request
// singleton that represents the request made to the server.
$rqst = \pjdietz\WellRESTed\Request::getRequest();

if ($rqst->getMethod() === 'PUT') {
    $obj = json_decode($rqst->getBody());
    // Do something with the JSON sent as the message body.
    // ...
}
```

### HTTP Client

The `Client` class allows you to make an HTTP request using cURL.

(This feature requires [PHP cURL](http://php.net/manual/en/book.curl.php).)

```php
// Prepare a request.
$rqst = new \pjdietz\WellRESTed\Request();
$rqst->setUri('http://my.api.local/resources/');
$rqst->setMethod('POST');
$rqst->setBody(json_encode($newResource));

// Use a Client to get a Response.
$client = new Client();
$resp = $client->request($rqst);

// Read the response.
if ($resp->getStatusCode() === 201) {
    // The new resource was created.
    $createdResource = json_decode($resp->getBody());
}
```


Copyright and License
---------------------
Copyright © 2014 by PJ Dietz
Licensed under the [MIT license](http://opensource.org/licenses/MIT)
