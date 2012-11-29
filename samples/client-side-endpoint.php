<?php

/**
 * This is the file that is requested by client-set-request-and-response.php
 *
 * Feel free to modify this script, then run client-set-request-and-response.php
 * to see the results.
 */

require_once('../Response.inc.php');

// Create a new Response instance.
$resp = new \wellrested\Response();
$resp->statusCode = 200;
$resp->setHeader('Content-Type', 'text/plain');
$resp->setHeader('User-Agent', 'Well RESTed');
$resp->body = 'The test works!';
$resp->respond();
exit;

?>
