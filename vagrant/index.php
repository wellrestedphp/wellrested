<?php

use pjdietz\WellRESTed\Router;

$loader = require_once __DIR__. "/../vendor/autoload.php";
$loader->addPsr4("", __DIR__ . "/../vagrant/src");
$loader->addPsr4("", __DIR__ . "/../autoload");

$router = new Router();
$router->add("/", "\\WellRESTedDev\\RootHandler");
$router->respond();
