<?php

/*
 * Client-side Request and Response
 *
 * This script will build a request to an external server, issue the request,
 * then read the reponse returned by the server.
 */

// Include the Well RESTed Request and Response class files.
require_once('../Request.inc.php');
require_once('../Response.inc.php');

$thisRequest = \wellrested\Request::getRequest();

// Create a new empty request.
$rqst = new \wellrested\Request();

// Set some of the information for it.
$rqst->hostname = $thisRequest->hostname;
$rqst->path = '/wellrested/samples/server-side-request-and-response.php';
$rqst->method = 'PUT';
$rqst->body = 'This is the body';

$resp = $rqst->request();

print 'Response code: ' . $resp->statusCode . "\n";
print 'Response body: ' . $resp->body . "\n";

?>