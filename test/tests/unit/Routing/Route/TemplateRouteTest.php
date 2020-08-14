<?php

namespace WellRESTed\Routing\Route;

use Prophecy\PhpUnit\ProphecyTrait;
use WellRESTed\Test\TestCase;

class TemplateRouteTest extends TestCase
{
    use ProphecyTrait;

    private $methodMap;

    protected function setUp(): void
    {
        $this->methodMap = $this->prophesize(MethodMap::class);
    }

    private function getExpectedValues($keys)
    {
        $expectedValues = [
            'var' => 'value',
            'hello' => 'Hello World!',
            'x' => '1024',
            'y' => '768',
            'path' => '/foo/bar',
            'who' => 'fred',
            'half' => '50%',
            'empty' => '',
            'count' => ['one', 'two', 'three'],
            'list' => ['red', 'green', 'blue']
        ];
        return array_intersect_key($expectedValues, array_flip($keys));
    }

    private function assertArrayHasSameContents($expected, $actual)
    {
        ksort($expected);
        ksort($actual);
        $this->assertEquals($expected, $actual);
    }

    // -------------------------------------------------------------------------

    public function testReturnsPatternType()
    {
        $route = new TemplateRoute('/', $this->methodMap->reveal());
        $this->assertSame(Route::TYPE_PATTERN, $route->getType());
    }

    // -------------------------------------------------------------------------
    // Matching

    /** @dataProvider nonMatchingTargetProvider */
    public function testFailsToMatchNonMatchingTarget($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget($target));
    }

    public function nonMatchingTargetProvider()
    {
        return [
            ['/foo/{var}', '/bar/12', 'Mismatch before first template expression'],
            ['/foo/{foo}/bar/{bar}', '/foo/12/13', 'Mismatch after first template expression'],
            ['/hello/{hello}', '/hello/Hello%20World!', 'Requires + operator to match reserved characters'],
            ['{/var}', '/bar/12', 'Path contains more segments than template'],
        ];
    }

    // -------------------------------------------------------------------------
    // Matching :: Simple Strings

    /** @dataProvider simpleStringProvider */
    public function testMatchesSimpleStrings($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /** @dataProvider simpleStringProvider */
    public function testCapturesFromSimpleStrings($template, $target, $variables)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function simpleStringProvider()
    {
        return [
            ['/foo', '/foo', []],
            ['/{var}', '/value', ['var']],
            ['/{hello}', '/Hello%20World%21', ['hello']],
            ['/{x,hello,y}', '/1024,Hello%20World%21,768', ['x', 'hello', 'y']],
            ['/{x,hello,y}', '/1024,Hello%20World%21,768', ['x', 'hello', 'y']],
        ];
    }

    // -------------------------------------------------------------------------
    // Matching :: Reserved

    /** @dataProvider reservedStringProvider */
    public function testMatchesReservedStrings($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /** @dataProvider reservedStringProvider */
    public function testCapturesFromReservedStrings($template, $target, $variables)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertSame($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function reservedStringProvider()
    {
        return [
            ['/{+var}', '/value', ['var']],
            ['/{+hello}', '/Hello%20World!', ['hello']],
            ['{+path}/here', '/foo/bar/here', ['path']],
        ];
    }

    // -------------------------------------------------------------------------
    // Matching :: Label Expansion

    /** @dataProvider labelWithDotPrefixProvider */
    public function testMatchesLabelWithDotPrefix($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /** @dataProvider labelWithDotPrefixProvider */
    public function testCapturesFromLabelWithDotPrefix($template, $target, $variables)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function labelWithDotPrefixProvider()
    {
        return [
            ['/{.who}', '/.fred', ['who']],
            ['/{.half,who}', '/.50%25.fred', ['half', 'who']],
            ['/X{.empty}', '/X.', ['empty']]
        ];
    }

    // -------------------------------------------------------------------------
    // Matching :: Path Segments

    /** @dataProvider pathSegmentProvider */
    public function testMatchesPathSegments($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /** @dataProvider pathSegmentProvider */
    public function testCapturesFromPathSegments($template, $target, $variables)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function pathSegmentProvider()
    {
        return [
            ['{/who}', '/fred', ['who']],
            ['{/half,who}', '/50%25/fred', ['half', 'who']],
            ['{/var,empty}', '/value/', ['var', 'empty']]
        ];
    }

    // -------------------------------------------------------------------------
    // Matching :: Explosion

    /** @dataProvider pathExplosionProvider */
    public function testMatchesExplosion($template, $target)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /** @dataProvider pathExplosionProvider */
    public function testCapturesFromExplosion($template, $target, $variables)
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function pathExplosionProvider()
    {
        return [
            ['/{count*}', '/one,two,three', ['count']],
            ['{/count*}', '/one/two/three', ['count']],
            ['X{.list*}', 'X.red.green.blue', ['list']]
        ];
    }
}
