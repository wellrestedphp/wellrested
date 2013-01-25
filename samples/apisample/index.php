<?php

require_once('../../vendor/autoload.php');

$router = new \apisample\ApiSampleRouter();
$response = $router->getResponse();
$response->respond();
exit;
