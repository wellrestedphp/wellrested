<?php

/*
 * Server-side Request and Response
 *
 * This script will read some data from the request sent to server and respond
 * with JSON descrition of the original request.
 */

// Include the autoload script.
require_once('../vendor/autoload.php');

use \pjdietz\WellRESTed\Request;
use \pjdietz\WellRESTed\Response;

// Read the request sent to the server as the singleton instance.
$rqst = Request::getRequest();

// Alternatively, you can create a new Request and call readHttpRequest().
// $rqst = new Request();
// $rqst->readHttpRequest();

// Read some info from the request and store it to an associative array.
$rtn = array(
    'Path' => $rqst->path,
    'URI' => $rqst->uri,
    'Body' => $rqst->body,
    'Method' => $rqst->method,
    'Headers' => $rqst->headers
);

// Create a new Response instance.
$resp = new Response();

// Set the status code to 200 OK.
$resp->statusCode = 200;

// Set the content type for JSON.
$resp->setHeader('Content-Type', 'application/json');

// Add the associative array, encoded as JSON, as the body.
// (Note, setting the body automatically adds a Content-Length header.)
$resp->body = json_encode($rtn);

// Output the response.
$resp->respond();
