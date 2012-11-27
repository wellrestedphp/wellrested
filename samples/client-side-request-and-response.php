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

// Create a new empty request.
$rqst = new \wellrested\Request();

// Set some of the information for it.
$rqst->path = 'https://www.google.com/search';
$rqst->query = array('q' => 'rest api');

print $rqst->uri;


?>