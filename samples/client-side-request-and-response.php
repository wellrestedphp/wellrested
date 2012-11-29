<?php

/*
 * Client-side Request and Response
 *
 * This script will build a request to an external server, issue the request,
 * then read the reponse returned by the server.
 *
 * Please modify samples/client-side-endpoint.php to see results.
 */

// Include the Well RESTed Request and Response class files.
require_once('../Request.inc.php');
require_once('../Response.inc.php');

// Get a Request instance describing the request made to this script.
$thisRequest = \wellrested\Request::getRequest();

// Create a new empty request.
$rqst = new \wellrested\Request();
$rqst->hostname = $thisRequest->hostname;
$rqst->path = '/wellrested/samples/client-side-endpoint.php';

// Uncomment this to get a cURL exception.
//$rqst->uri = 'http://not-a-real.domain';

// Issue the request, and read the response returned by the server.
try {
    $resp = $rqst->request();
} catch (\wellrested\exceptions\CurlException $e) {

    // Create new response to send to output to the browser.
    $myResponse = new \wellrested\Response();
    $myResponse->statusCode = 500;
    $myResponse->setHeader('Content-Type', 'text/plain');
    $myResponse->body = 'Message: ' .$e->getMessage() ."\n";
    $myResponse->body .= 'Code: ' . $e->getCode() . "\n";
    $myResponse->respond();
    exit;

}

// Create new response to send to output to the browser.
$myResponse = new \wellrested\Response();
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

?>