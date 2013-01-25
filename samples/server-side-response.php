<?php

/**
 * Create and output a response from the server.
 */

// Include the autoload script.
require_once('../vendor/autoload.php');

use \pjdietz\WellRESTed\Response;

// Create a new Response instance.
$resp = new Response();
$resp->statusCode = 200;
$resp->setHeader('Content-Type', 'text/plain');
$resp->body = 'This is a response.';
$resp->respond();
exit;
