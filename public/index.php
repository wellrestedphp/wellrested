<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WellRESTed\Message\Response;
use WellRESTed\Message\Stream;
use WellRESTed\Server;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

// Create a handler using the PSR-15 RequestHandlerInterface
class HomePageHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $view = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>WellRESTed Development Site</title>
</head>
<body>
    <h1>WellRESTed Development Site</h1>

    <p>To run unit tests, run:</p>
    <code>docker-compose run --rm php phpunit</code>
    <p>View the <a href="/coverage/">code coverage report</a>.</p>

    <p>To generate documentation, run:</p>
    <code>docker-compose run --rm docs</code>
    <p>View <a href="/docs/"> documentation</a>.</p>
  </body>
</html>
HTML;

        return (new Response(200))
            ->withHeader('Content-type', 'text/html')
            ->withBody(new Stream($view));
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
