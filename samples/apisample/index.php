<?php

require_once('ApiSampleRouter.php');

$router = new \apisample\ApiSampleRouter();
$response = $router->getResponse();
$response->respond();
exit;

?>