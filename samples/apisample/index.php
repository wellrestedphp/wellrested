<?php

require_once('../../vendor/autoload.php');

// Have to add these manually since this isn't part of the normal package.
// Your project won't have to do this.
require_once('ApiSampleRouter.php');
require_once('ArticlesController.php');
require_once('Handlers/ArticleCollectionHandler.php');
require_once('Handlers/ArticleItemHandler.php');

$router = new \ApiSample\ApiSampleRouter();
$response = $router->getResponse();
$response->respond();
exit;
