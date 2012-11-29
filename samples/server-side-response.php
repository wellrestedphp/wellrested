<?php

/**
 * Create and output a response from the server.
 */

require_once('../Response.inc.php');

// Create a new Response instance.
$resp = new \wellrested\Response();
$resp->statusCode = 200;
$resp->setHeader('Content-Type', 'text/plain');
$resp->body = 'This is a response.';
$resp->respond();
exit;

?>
