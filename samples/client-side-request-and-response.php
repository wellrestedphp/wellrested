<?php

/*
 * Client-side Request and Response
 *
 * This script will build a request to an external server, issue the request,
 * then read the reponse returned by the server.
 *
 * Please modify samples/client-side-endpoint.php to see results.
 */

// Include the autoload script.
require_once('../vendor/autoload.php');

use \pjdietz\WellRESTed\Request;
use \pjdietz\WellRESTed\Response;
use \pjdietz\WellRESTed\Exceptions\CurlException;

// Make a custom request to talk to the server.
$rqst = new Request();

// Use the client-site-endpoint.php script
$rqst->hostname = $_SERVER['HTTP_HOST'];
$rqst->path = '/wellrested/samples/server-side-response.php';

// Issue the request, and read the response returned by the server.
try {
    $resp = $rqst->request();
} catch (CurlException $e) {

    // Explain the cURL error and provide an error status code.
    $myResponse = new Response();
    $myResponse->statusCode = 500;
    $myResponse->setHeader('Content-Type', 'text/plain');
    $myResponse->body = 'Message: ' .$e->getMessage() ."\n";
    $myResponse->body .= 'Code: ' . $e->getCode() . "\n";
    $myResponse->respond();
    exit;

}

// Create new response to send to output to the browser.
$myResponse = new Response();
$myResponse->statusCode = 200;
$myResponse->setHeader('Content-Type', 'application/json');

$json = array(
    'Status Code' => $resp->statusCode,
    'Body' => $resp->body,
    'Headers' => $resp->headers
);
$myResponse->body = json_encode($json);

$myResponse->respond();
exit;
