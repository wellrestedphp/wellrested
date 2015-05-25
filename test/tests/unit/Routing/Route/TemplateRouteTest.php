<?php

namespace WellRESTed\Test\Unit\Routing\Route;

use Prophecy\Argument;
use WellRESTed\Routing\Route\RouteInterface;
use WellRESTed\Routing\Route\TemplateRoute;

/**
 * @coversDefaultClass WellRESTed\Routing\Route\TemplateRoute
 * @uses WellRESTed\Routing\Route\TemplateRoute
 * @uses WellRESTed\Routing\Route\RegexRoute
 * @uses WellRESTed\Routing\Route\Route
 * @group route
 * @group routing
 */
class TemplateRouteTest extends \PHPUnit_Framework_TestCase
{
    private $methodMap;

    public function setUp()
    {
        $this->methodMap = $this->prophesize('WellRESTed\Routing\MethodMapInterface');
    }

    private function getExpectedValues($keys)
    {
        $expectedValues = [
            "var" => "value",
            "hello" => "Hello World!",
            "x" => "1024",
            "y" => "768",
            "path" => "/foo/bar",
            "who" => "fred",
            "half" => "50%",
            "empty" => "",
            "count" => ["one", "two", "three"],
            "list" => ["red", "green", "blue"]
        ];
        return array_intersect_key($expectedValues, array_flip($keys));
    }

    private function assertArrayHasSameContents($expected, $actual)
    {
        ksort($expected);
        ksort($actual);
        $this->assertEquals($expected, $actual);
    }

    // ------------------------------------------------------------------------

    /**
     * @covers ::getType
     */
    public function testReturnsPatternType()
    {
        $route = new TemplateRoute("/", $this->methodMap->reveal());
        $this->assertSame(RouteInterface::TYPE_PATTERN, $route->getType());
    }

    // ------------------------------------------------------------------------
    // Matching

    /**
     * @covers ::matchesRequestTarget
     * @covers ::matchesStartOfRequestTarget
     * @covers ::getMatchingPattern
     * @dataProvider nonMatchingTargetProvider
     * @param string $template
     * @param string $target
     */
    public function testFailsToMatchNonMatchingTarget($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $this->assertFalse($route->matchesRequestTarget($target));
    }

    public function nonMatchingTargetProvider()
    {
        return [
            ["/foo/{var}", "/bar/12", "Mismatch before first template expression"],
            ["/foo/{foo}/bar/{bar}", "/foo/12/13", "Mismatch after first template expression"],
            ["/hello/{hello}", "/hello/Hello%20World!", "Requires + operator to match reserver characters"],
            ["{/var}", "/bar/12", "Path contains more segements than template"],
        ];
    }

    // ------------------------------------------------------------------------
    // Matching :: Simple Strings

    /**
     * @covers ::matchesRequestTarget
     * @covers ::getMatchingPattern
     * @covers ::uriVariableReplacementCallback
     * @dataProvider simpleStringProvider
     * @param string $template
     * @param string $target
     */
    public function testMatchesSimpleStrings($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /**
     * @covers ::getPathVariables
     * @covers ::processMatches
     * @covers ::uriVariableReplacementCallback
     * @dataProvider simpleStringProvider
     * @param string $template
     * @param string $target
     * @param string[] List of variables that should be extracted
     */
    public function testCapturesFromSimpleStrings($template, $target, $variables)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function simpleStringProvider()
    {
        return [
            ["/foo", "/foo", []],
            ["/{var}", "/value", ["var"]],
            ["/{hello}", "/Hello%20World%21", ["hello"]],
            ["/{x,hello,y}", "/1024,Hello%20World%21,768", ["x", "hello", "y"]],
            ["/{x,hello,y}", "/1024,Hello%20World%21,768", ["x", "hello", "y"]],
        ];
    }

    // ------------------------------------------------------------------------
    // Matching :: Reservered

    /**
     * @covers ::matchesRequestTarget
     * @covers ::getMatchingPattern
     * @covers ::uriVariableReplacementCallback
     * @dataProvider reservedStringProvider
     * @param string $template
     * @param string $target
     */
    public function testMatchesReserveredStrings($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /**
     * @covers ::getPathVariables
     * @covers ::processMatches
     * @covers ::uriVariableReplacementCallback
     * @dataProvider reservedStringProvider
     * @param string $template
     * @param string $target
     * @param string[] List of variables that should be extracted
     */
    public function testCapturesFromReservedStrings($template, $target, $variables)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $route->matchesRequestTarget($target);
        $this->assertSame($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function reservedStringProvider()
    {
        return [
            ["/{+var}", "/value", ["var"]],
            ["/{+hello}", "/Hello%20World!", ["hello"]],
            ["{+path}/here", "/foo/bar/here", ["path"]],
        ];
    }

    // ------------------------------------------------------------------------
    // Matching :: Label Expansion

    /**
     * @covers ::matchesRequestTarget
     * @covers ::getMatchingPattern
     * @covers ::uriVariableReplacementCallback
     * @dataProvider labelWithDotPrefixProvider
     * @param string $template
     * @param string $target
     */
    public function testMatchesLabelWithDotPrefix($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /**
     * @covers ::getPathVariables
     * @covers ::processMatches
     * @covers ::uriVariableReplacementCallback
     * @dataProvider labelWithDotPrefixProvider
     * @param string $template
     * @param string $target
     * @param string[] List of variables that should be extracted
     */
    public function testCapturesFromLabelWithDotPrefix($template, $target, $variables)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function labelWithDotPrefixProvider()
    {
        return [
            ["/{.who}", "/.fred", ["who"]],
            ["/{.half,who}", "/.50%25.fred", ["half", "who"]],
            ["/X{.empty}", "/X.", ["empty"]]
        ];
    }

    // ------------------------------------------------------------------------
    // Matching :: Path Segments

    /**
     * @covers ::matchesRequestTarget
     * @covers ::getMatchingPattern
     * @covers ::uriVariableReplacementCallback
     * @dataProvider pathSegmentProvider
     * @param string $template
     * @param string $target
     */
    public function testMatchesPathSegments($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /**
     * @covers ::getPathVariables
     * @covers ::processMatches
     * @covers ::uriVariableReplacementCallback
     * @dataProvider pathSegmentProvider
     * @param string $template
     * @param string $target
     * @param string[] List of variables that should be extracted
     */
    public function testCapturesFromPathSegments($template, $target, $variables)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function pathSegmentProvider()
    {
        return [
            ["{/who}", "/fred", ["who"]],
            ["{/half,who}", "/50%25/fred", ["half", "who"]],
            ["{/var,empty}", "/value/", ["var", "empty"]]
        ];
    }

    // ------------------------------------------------------------------------
    // Matching :: Explosion

    /**
     * @covers ::matchesRequestTarget
     * @covers ::getMatchingPattern
     * @covers ::uriVariableReplacementCallback
     * @dataProvider pathExplosionProvider
     * @param string $template
     * @param string $target
     */
    public function testMatchesExplosion($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /**
     * @covers ::getPathVariables
     * @covers ::processMatches
     * @covers ::uriVariableReplacementCallback
     * @dataProvider pathExplosionProvider
     * @param string $template
     * @param string $target
     * @param string[] List of variables that should be extracted
     */
    public function testCapturesFromExplosion($template, $target, $variables)
    {
        $route = new TemplateRoute($template, $this->methodMap);
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function pathExplosionProvider()
    {
        return [
            ["/{count*}", "/one,two,three", ["count"]],
            ["{/count*}", "/one/two/three", ["count"]],
            ["X{.list*}", "X.red.green.blue", ["list"]]
        ];
    }
}
