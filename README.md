WellRESTed
==========


[![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/wellrested/wellrested/php?style=flat-square)](https://packagist.org/packages/wellrested/wellrested)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/wellrestedphp/wellrested/ci.yml?style=flat-square)](https://github.com/wellrestedphp/wellrested/actions/workflows/ci.yml)
[![Read the Docs](https://img.shields.io/readthedocs/wellrested?style=flat-square)](https://wellrested.readthedocs.io/en/latest/)

WellRESTed is a library for creating RESTful APIs and websites in PHP that provides abstraction for HTTP messages, a powerful handler and middleware system, and a flexible router.

### Features

- Router allows you to match paths with variables such as `/foo/{bar}/{baz}`.
- Middleware system provides a way to compose your application from discrete, modular components.
- Lazy-loaded handlers and middleware don't instantiate unless they're needed.

### Supports PSR Standards

- [PSR-7](https://www.php-fig.org/psr/psr-7/) interfaces for requests, responses, and streams
- [PSR-15](https://www.php-fig.org/psr/psr-15/) interfaces for handlers and middleware
- [PSR-11](https://www.php-fig.org/psr/psr-11/) interfaces dependency injection

Install
-------

Add an entry for "wellrested/wellrested" to your composer.json file's `require` property.

```json
{
    "require": {
        "wellrested/wellrested": "^6"
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

// Create a new Server instance.
$server = new Server();
// Add a router to the server to map methods and endpoints to handlers.
$router = $server->createRouter();
// Register the route GET / with an anonymous function that provides a handler.
$router->register("GET", "/", function () { return new HomePageHandler(); });
// Add the router to the server.
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

To run Psalm for static analysis:

```bash
docker-compose run --rm php psalm
```

To run PHP Coding Standards Fixer:

```bash
docker-compose run --rm php php-cs-fixer fix
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
Copyright © 2024 by PJ Dietz
Licensed under the [MIT license](http://opensource.org/licenses/MIT)
