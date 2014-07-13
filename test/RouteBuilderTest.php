<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\RouteBuilder;
use pjdietz\WellRESTed\Routes\TemplateRoute;
use stdClass;

class RouteBuilderTest extends \PHPUnit_Framework_TestCase
{
    /*
     * Parse JSON and get the correct number of routes.
     */
    public function testBuildValidJson()
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
    public function testBuildInvalidJson()
    {
        $json = "jadhjaksd";
        $builder = new RouteBuilder();
        $routes = $builder->buildRoutes($json);
    }

    public function testNamesapce()
    {
        $namespace = "\\test\\Namespace";
        $builder = new RouteBuilder();
        $builder->setHandlerNamespace($namespace);
        $this->assertEquals($namespace, $builder->getHandlerNamespace());
    }

    /**
     * @dataProvider varProvider
     */
    public function testDefaultVariablePattern($name, $pattern, $expected)
    {
        $builder = new RouteBuilder();
        $builder->setDefaultVariablePattern($pattern);
        $this->assertEquals($builder->getDefaultVariablePattern(), $expected);
    }

    /**
     * @dataProvider varProvider
     */
    public function testConfigurationDefaultVariablePattern($name, $pattern, $expected)
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
    public function testTemplateVariables($name, $pattern, $expected)
    {
        $builder = new RouteBuilder();
        $builder->setTemplateVars(array($name => $pattern));
        $vars = $builder->getTemplateVars();
        $this->assertEquals($vars[$name], $expected);
    }

    /**
     * @dataProvider varProvider
     */
    public function testConfigurationTemplateVariables($name, $pattern, $expected)
    {
        $builder = new RouteBuilder();
        $conf = new stdClass();
        $conf->vars = array($name => $pattern);
        $builder->readConfiguration($conf);
        $vars = $builder->getTemplateVars();
        $this->assertEquals($vars[$name], $expected);
    }

    public function varProvider()
     {
         return array(
             array("slug", "SLUG", TemplateRoute::RE_SLUG),
             array("name", "ALPHA", TemplateRoute::RE_ALPHA),
             array("name", "ALPHANUM", TemplateRoute::RE_ALPHANUM),
             array("id", "DIGIT", TemplateRoute::RE_NUM),
             array("id", "NUM", TemplateRoute::RE_NUM),
             array("custom", ".*", ".*")
         );
     }

    /**
     * @dataProvider routeDescriptionProvider
     */
    public function testRoutes($key, $value, $expectedClass)
    {
        $mock = $this->getMock('\pjdietz\WellRESTed\Interfaces\HandlerInterface');
        $routes = array(
            (object) array(
                $key => $value,
                "handler" => get_class($mock)
            )
        );
        $builder = new RouteBuilder();
        $routes = $builder->buildRoutes($routes);
        $route = $routes[0];
        $this->assertInstanceOf($expectedClass, $route);
    }

    /**
     * @dataProvider routeDescriptionProvider
     */
    public function testRoutesObject($key, $value, $expectedClass)
    {
        $mock = $this->getMock('\pjdietz\WellRESTed\Interfaces\HandlerInterface');
        $conf = (object) array(
            "routes" => array(
                (object) array(
                    $key => $value,
                    "handler" => get_class($mock)
                )
            )
        );
        $builder = new RouteBuilder();
        $routes = $builder->buildRoutes($conf);
        $route = $routes[0];
        $this->assertInstanceOf($expectedClass, $route);
    }

    public function routeDescriptionProvider()
    {
        return array(
            array("path", "/", '\pjdietz\WellRESTed\Routes\StaticRoute'),
            array("pattern", "/cat/[0-9]+", '\pjdietz\WellRESTed\Routes\RegexRoute'),
            array("template", "/cat/{id}", '\pjdietz\WellRESTed\Routes\TemplateRoute'),
        );
    }

}
