<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\RouteBuilder;
use pjdietz\WellRESTed\Routes\TemplateRoute;
use stdClass;

class RouteBuilderTest extends \PHPUnit_Framework_TestCase
{
    /*
     * Parse JSON and get the correct number of routes.
     */
    public function testBuildRoutesFromJson()
    {
        $json = <<<'JSON'
{
    "handlerNamespace": "\\myapi\\Handlers",
    "routes": [
        {
            "path": "/",
            "handler": "RootHandler"
        },
        {
            "path": "/cats/",
            "handler": "CatCollectionHandler"
        },
        {
            "tempalte": "/cats/{id}",
            "handler": "CatItemHandler"
        }
    ]
}
JSON;

        $builder = new RouteBuilder();
        $routes = $builder->buildRoutes($json);
        $this->assertEquals(3, count($routes));
    }

    /**
     * Fail properly on malformed JSON
     *
     * @expectedException        \pjdietz\WellRESTed\Exceptions\ParseException
     * @expectedExceptionMessage Unable to parse as JSON.
     */
    public function testFailBuildingRoutesFromInvalidJson()
    {
        $json = "jadhjaksd";
        $builder = new RouteBuilder();
        $builder->buildRoutes($json);
    }

    public function testSetNamesapce()
    {
        $namespace = "\\test\\Namespace";
        $builder = new RouteBuilder();
        $builder->setHandlerNamespace($namespace);
        $this->assertEquals($namespace, $builder->getHandlerNamespace());
    }

    /**
     * @dataProvider varProvider
     */
    public function testSetDefaultVariablePatternThroughAccessor($name, $pattern, $expected)
    {
        $builder = new RouteBuilder();
        $builder->setDefaultVariablePattern($pattern);
        $this->assertEquals($builder->getDefaultVariablePattern(), $expected);
    }

    /**
     * @dataProvider varProvider
     */
    public function testSetDefaultVariablePatternThroughConfiguration($name, $pattern, $expected)
    {
        $builder = new RouteBuilder();
        $conf = new stdClass();
        $conf->variablePattern = $pattern;
        $builder->readConfiguration($conf);
        $this->assertEquals($builder->getDefaultVariablePattern(), $expected);
    }

    /**
     * @dataProvider varProvider
     */
    public function testSetTemplateVariablesThroughAccessor($name, $pattern, $expected)
    {
        $builder = new RouteBuilder();
        $builder->setTemplateVars(array($name => $pattern));
        $vars = $builder->getTemplateVars();
        $this->assertEquals($vars[$name], $expected);
    }

    /**
     * @dataProvider varProvider
     */
    public function testSetTemplateVariablesThroughConfiguration($name, $pattern, $expected)
    {
        $builder = new RouteBuilder();
        $conf = new stdClass();
        $conf->vars = [$name => $pattern];
        $builder->readConfiguration($conf);
        $vars = $builder->getTemplateVars();
        $this->assertEquals($vars[$name], $expected);
    }

    public function varProvider()
    {
         return [
             ["slug", "SLUG", TemplateRoute::RE_SLUG],
             ["name", "ALPHA", TemplateRoute::RE_ALPHA],
             ["name", "ALPHANUM", TemplateRoute::RE_ALPHANUM],
             ["id", "DIGIT", TemplateRoute::RE_NUM],
             ["id", "NUM", TemplateRoute::RE_NUM],
             ["custom", ".*", ".*"]
         ];
    }

    /**
     * @dataProvider routeDescriptionProvider
     */
    public function testBuildRoutesFromRoutesArray($key, $value, $expectedClass)
    {
        $mockHander = $this->getMock('\pjdietz\WellRESTed\Interfaces\HandlerInterface');
        $routes = [
            (object) [
                $key => $value,
                "handler" => get_class($mockHander)
            ]
        ];
        $builder = new RouteBuilder();
        $routes = $builder->buildRoutes($routes);
        $route = $routes[0];
        $this->assertInstanceOf($expectedClass, $route);
    }

    /**
     * @dataProvider routeDescriptionProvider
     */
    public function testBuildRoutesFromConfigurationObject($key, $value, $expectedClass)
    {
        $mockHander = $this->getMock('\pjdietz\WellRESTed\Interfaces\HandlerInterface');
        $conf = (object) [
            "routes" => [
                (object) [
                    $key => $value,
                    "handler" => get_class($mockHander)
                ]
            ]
        ];
        $builder = new RouteBuilder();
        $routes = $builder->buildRoutes($conf);
        $route = $routes[0];
        $this->assertInstanceOf($expectedClass, $route);
    }

    public function routeDescriptionProvider()
    {
        return [
            ["path", "/", '\pjdietz\WellRESTed\Routes\StaticRoute'],
            ["pattern", "/cat/[0-9]+", '\pjdietz\WellRESTed\Routes\RegexRoute'],
            ["template", "/cat/{id}", '\pjdietz\WellRESTed\Routes\TemplateRoute'],
        ];
    }

    public function testBuildRoutesWithTemplateVariables()
    {
        $mock = $this->getMock('\pjdietz\WellRESTed\Interfaces\HandlerInterface');
        $routes = [
            (object) [
                "template" => "/cats/{catId}",
                "handler" => get_class($mock),
                "vars" => [
                    "catId" => "SLUG"
                ]
            ]
        ];
        $builder = new RouteBuilder();
        $builder->setTemplateVars(["dogId" => "NUM"]);
        $routes = $builder->buildRoutes($routes);
        $route = $routes[0];
        $this->assertInstanceOf('\pjdietz\WellRESTed\Routes\TemplateRoute', $route);
    }

    /**
     * @expectedException        \pjdietz\WellRESTed\Exceptions\ParseException
     * @expectedExceptionMessage Unable to parse. Missing array of routes.
     */
    public function testFailOnConfigurationObjectMissingRoutesArray()
    {
        $conf = new stdClass();
        $builder = new RouteBuilder();
        $builder->buildRoutes($conf);
    }

    /**
     * @expectedException        \pjdietz\WellRESTed\Exceptions\ParseException
     * @expectedExceptionMessage Unable to parse. Route is missing a handler.
     */
    public function testFailOnRouteMissingHandler()
    {
        $routes = [
            (object) [
                "path" => "/"
            ]
        ];
        $builder = new RouteBuilder();
        $builder->buildRoutes($routes);
    }
}
