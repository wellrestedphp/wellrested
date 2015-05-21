WellRESTed
==========

[![Build Status](https://travis-ci.org/wellrestedphp/wellrested.svg?branch=psr7)](https://travis-ci.org/wellrestedphp/wellrested)
[![Documentation Status](https://readthedocs.org/projects/wellrested/badge/?version=latest)](https://readthedocs.org/projects/wellrested/?badge=latest)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/b0a2efcb-49f8-4a90-a5bd-0c14e409f59e/mini.png)](https://insight.sensiolabs.com/projects/b0a2efcb-49f8-4a90-a5bd-0c14e409f59e)

WellRESTed is a library for creating RESTful Web services in PHP.

Requirements
------------

- PHP 5.4

Install
-------

Add an entry for "wellrested/wellrested" to your composer.json file's `require` property.

```json
{
    "require": {
        "wellrested/wellrested": "~3.0"
    }
}
```

Documentation
-------------

See [the documentation](http://wellrested.readthedocs.org/en/latest/) to get started.

Example
-------

```php
<?php

use WellRESTed\Message\Stream;
use WellRESTed\Server;

require_once "vendor/autoload.php";

// Build some middleware. We'll register these with a server below.
// We're using callables to fit this all in one example, but these
// could also be classes implementing WellRESTed\MiddlewareInterface.

// Set the status code and provide the greeting as the response body.
$hello = function ($request, $response, $next) {

    // Check for a "name" attribute which may have been provided as a
    // path variable. Use "world" as a default.
    $name = $request->getAttribute("name", "world");

    // Set the response body to the greeting and the status code to 200 OK.
    $response = $response->withStatus(200)
        ->withHeader("Content-type", "text/plain")
        ->withBody(new Stream("Hello, $name!"));

    // Propagate to the next middleware, if any, and return the response.
    return $next($request, $response);

};

// Add a header to the response.
$headerAdder = function ($request, $response, $next) {
    // Add the header.
    $response = $response->withHeader("X-example", "hello world");
    // Propagate to the next middleware, if any, and return the response.
    return $next($request, $response);
};

// Create a server
$server = new Server();

// Start each request-response cycle by dispatching the header adder.
$server->add($headerAdder);

// The header adder will propagate to this router, which will dispatch the
// $hello middleware, possibly with a {name} variable.
$server->add($server->createRouter()
    ->register("GET", "/hello", $hello)
    ->register("GET", "/hello/{name}", $hello)
);

// Read the request from the client, dispatch middleware, and output.
$server->respond();

```


Copyright and License
---------------------
Copyright © 2015 by PJ Dietz
Licensed under the [MIT license](http://opensource.org/licenses/MIT)
