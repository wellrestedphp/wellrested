<?php

/*
 * This script will make a request to google and output the response.
 */

// Include the Well RESTed Request and Response class files.
require_once('../Request.php');

// Make a requst to Google in one line:
$rqst = new \pjdietz\WellRESTed\Request();
$rqst->uri = 'https://www.google.com/search?q=my+search+terms';

// You could also set the members individually, like this:
//$rqst->protocol = 'https';
//$rqst->hostname = 'www.google.com';
//$rqst->path = '/search';
//$rqst->query = array('q' => 'my search terms');

// Make the request and obtain an Response instance.
$resp = $rqst->request();

// Output the response body and exit.
print $resp->body;
exit;

?>
