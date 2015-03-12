<?php

namespace WellRESTedDev;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;
use pjdietz\WellRESTed\Interfaces\ResponseInterface;
use pjdietz\WellRESTed\Response;

class RootHandler implements HandlerInterface
{
    /**
     * Return the handled response.
     *
     * @param RequestInterface $request The request to respond to.
     * @param array|null $args Optional additional arguments.
     * @return ResponseInterface The handled response.
     */
    public function getResponse(RequestInterface $request, array $args = null)
    {
        $view = <<<HTML
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>WellRESTed</title>
    </head>
    <body>
        <h1>Welcome to the WellRESTed Test Site</h1>
        <ul>
            <li>View <a href="/docs/">Documentatation</a></li>
            <li>View <a href="/coverage/">Code Coverage Report</a></li>
        </ul>
        <p>Run <code>vagrant ssh</code>, then:</p>
        <dl>
            <dt>To run unit tests</dt>
            <dd><code>vendor/bin/phpunit</code></dd>
            <dt>To generate documentation</dt>
            <dd><code>make html -C docs</code></dd>
        </dl>
        <p>Use this site as a sandbox. Modify the router <code>/htdocs/index.php</code> however you like.</p>
        <p>Any classes you create inside <code>/autoload</code> will be autoloaded with a PSR-4 autoloader.</p>
    </body>
</html>
HTML;
        $response = new Response(200);
        $response->setBody($view);
        return $response;
    }
}
