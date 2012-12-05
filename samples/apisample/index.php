<?php

require_once('ApiSampleRouter.inc.php');

$router = new \apisample\ApiSampleRouter();
$response = $router->getResponse();
$response->respond();
exit;

?>