<?php

require_once('ApiSampleRouter.inc.php');

$router = new ApiSampleRouter();
$response = $router->getResponse();
$response->respond();
exit;

?>