WellRESTed
==========

[![Build Status](https://travis-ci.org/wellrestedphp/wellrested.svg?branch=master)](https://travis-ci.org/wellrestedphp/wellrested)
[![Documentation Status](https://readthedocs.org/projects/wellrested/badge/?version=latest)](http://wellrested.readthedocs.org/en/latest/)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/b0a2efcb-49f8-4a90-a5bd-0c14e409f59e/mini.png)](https://insight.sensiolabs.com/projects/b0a2efcb-49f8-4a90-a5bd-0c14e409f59e)

WellRESTed is a library for creating RESTful Web services and websites in PHP.

Requirements
------------

- PHP 7.0

Install
-------

Add an entry for "wellrested/wellrested" to your composer.json file's `require` property.

```json
{
    "require": {
        "wellrested/wellrested": "^4"
    }
}
```

Documentation
-------------

See [the documentation](https://wellrested.readthedocs.org/en/latest/) to get started.

Example
-------

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\Stream;
use WellRESTed\Server;

// Create a handler using the PSR-15 RequestHandlerInterface
class HomePageHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Create and return new Response object to return with status code,
        // headers, and body.
        $response = (new Response(200))
            ->withHeader('Content-type', 'text/html')
            ->withBody(new Stream('<h1>Hello, world!</h1>'));
        return $response;
    }
}

// -----------------------------------------------------------------------------

// Create a new server.
$server = new Server();

// Add a router to the server to map methods and endpoints to handlers.
$router = $server->createRouter();
$router->register('GET', '/', new HomePageHandler());
$server->add($router);

// Read the request from the client, dispatch a handler, and output.
$server->respond();
```

Development
-----------

Use Docker to run unit tests, manage Composer dependencies, and render a preview of the documentation site.

To get started, run:

```bash
docker-compose build
docker-compose run --rm php composer install
```

To run PHPUnit tests, use the `php` service:

```bash
docker-compose run --rm php phpunit
```

To generate documentation, use the `docs` service:

```bash
# Generate
docker-compose run --rm docs
# Clean
docker-compose run --rm docs make clean -C docs
```

To run a local playground site, use:

```bash
docker-compose up -d
```

The runs a site you can access at [http://localhost:8080](http://localhost:8080). You can use this site to browser the [documentation](http://localhost:8080/docs/) or [code coverage report](http://localhost:8080/coverage/).

Copyright and License
---------------------
Copyright © 2018 by PJ Dietz
Licensed under the [MIT license](http://opensource.org/licenses/MIT)
