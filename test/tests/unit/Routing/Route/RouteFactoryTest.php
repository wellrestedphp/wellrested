<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RouteFactory;

/**
 * @covers WellRESTed\Routing\Route\RouteFactory
 * @uses WellRESTed\Routing\Route\TemplateRoute
 * @uses WellRESTed\Routing\Route\RegexRoute
 * @uses WellRESTed\Routing\Route\PrefixRoute
 * @uses WellRESTed\Routing\Route\StaticRoute
 * @uses WellRESTed\Routing\Route\Route
 */
class RouteFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $routeTable;

    public function setUp()
    {
        parent::setUp();
        $this->routeTable = $this->prophesize("\\WellRESTed\\Routing\\RouteTableInterface");
        $this->routeTable->addStaticRoute(Argument::cetera())->willReturn();
        $this->routeTable->addPrefixRoute(Argument::cetera())->willReturn();
        $this->routeTable->addRoute(Argument::cetera())->willReturn();
    }

    public function testRegistersStaticRoute()
    {
        $factory = new RouteFactory();
        $factory->registerRoute($this->routeTable->reveal(), "/cats/", null);
        $this->routeTable->addStaticRoute(Argument::any())->shouldHaveBeenCalled();
    }

    public function testRegistersPrefixRoute()
    {
        $factory = new RouteFactory();
        $factory->registerRoute($this->routeTable->reveal(), "/cats/*", null);
        $this->routeTable->addPrefixRoute(Argument::any())->shouldHaveBeenCalled();
    }

    public function testRegistersTemplateRoute()
    {
        $factory = new RouteFactory();
        $factory->registerRoute($this->routeTable->reveal(), "/cats/{catId}", null);
        $this->routeTable->addRoute(Argument::type("\\WellRESTed\\Routing\\Route\\TemplateRoute"))->shouldHaveBeenCalled();
    }

    public function testRegistersRegexRoute()
    {
        $factory = new RouteFactory();
        $factory->registerRoute($this->routeTable->reveal(), "~/cat/[0-9]+~", null);
        $this->routeTable->addRoute(Argument::type("\\WellRESTed\\Routing\\Route\\RegexRoute"))->shouldHaveBeenCalled();
        $this->routeTable->addRoute(Argument::type("\\WellRESTed\\Routing\\Route\\TemplateRoute"))->shouldNotHaveBeenCalled();
    }
}
