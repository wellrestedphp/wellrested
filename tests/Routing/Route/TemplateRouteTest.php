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

    private function getExpectedValues(array $keys): array
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

    private function assertArrayHasSameContents($expected, $actual): void
    {
        ksort($expected);
        ksort($actual);
        $this->assertEquals($expected, $actual);
    }

    // -------------------------------------------------------------------------

    public function testReturnsPatternType(): void
    {
        $route = new TemplateRoute('/', $this->methodMap->reveal());
        $this->assertSame(Route::TYPE_PATTERN, $route->getType());
    }

    // -------------------------------------------------------------------------
    // Matching

    /**
     * @dataProvider nonMatchingTargetProvider
     * @param string $template
     * @param string $target
     */
    public function testFailsToMatchNonMatchingTarget(string $template, string $target): void
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

    /**
     * @dataProvider simpleStringProvider
     * @param string $template
     * @param string $target
     */
    public function testMatchesSimpleStrings(string $template, string $target): void
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /**
     * @dataProvider simpleStringProvider
     * @param string $template
     * @param string $target
     * @param string[] $variables
     */
    public function testCapturesFromSimpleStrings(string $template, string $target, array $variables): void
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function simpleStringProvider(): array
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

    /**
     * @dataProvider reservedStringProvider
     * @param string $template
     * @param string $target
     */
    public function testMatchesReservedStrings(string $template, string $target): void
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /**
     * @dataProvider reservedStringProvider
     * @param string $template
     * @param string $target
     * @param array $variables
     */
    public function testCapturesFromReservedStrings(string $template, string $target, array $variables): void
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertSame($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function reservedStringProvider(): array
    {
        return [
            ['/{+var}', '/value', ['var']],
            ['/{+hello}', '/Hello%20World!', ['hello']],
            ['{+path}/here', '/foo/bar/here', ['path']],
        ];
    }

    // -------------------------------------------------------------------------
    // Matching :: Label Expansion

    /**
     * @dataProvider labelWithDotPrefixProvider
     * @param string $template
     * @param string $target
     */
    public function testMatchesLabelWithDotPrefix(string $template, string $target): void
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /**
     * @dataProvider labelWithDotPrefixProvider
     * @param string $template
     * @param string $target
     * @param array $variables
     */
    public function testCapturesFromLabelWithDotPrefix(string $template, string $target, array $variables): void
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function labelWithDotPrefixProvider(): array
    {
        return [
            ['/{.who}', '/.fred', ['who']],
            ['/{.half,who}', '/.50%25.fred', ['half', 'who']],
            ['/X{.empty}', '/X.', ['empty']]
        ];
    }

    // -------------------------------------------------------------------------
    // Matching :: Path Segments

    /**
     * @dataProvider pathSegmentProvider
     * @param string $template
     * @param string $target
     */
    public function testMatchesPathSegments(string $template, string $target): void
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /**
     * @dataProvider pathSegmentProvider
     * @param string $template
     * @param string $target
     * @param array $variables
     */
    public function testCapturesFromPathSegments(string $template, string $target, array $variables): void
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function pathSegmentProvider(): array
    {
        return [
            ['{/who}', '/fred', ['who']],
            ['{/half,who}', '/50%25/fred', ['half', 'who']],
            ['{/var,empty}', '/value/', ['var', 'empty']]
        ];
    }

    // -------------------------------------------------------------------------
    // Matching :: Explosion

    /**
     * @dataProvider pathExplosionProvider
     * @param string $template
     * @param string $target
     */
    public function testMatchesExplosion(string $template, string $target): void
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($target));
    }

    /**
     * @dataProvider pathExplosionProvider
     * @param string $template
     * @param string $target
     * @param array $variables
     */
    public function testCapturesFromExplosion(string $template, string $target, array $variables): void
    {
        $route = new TemplateRoute($template, $this->methodMap->reveal());
        $route->matchesRequestTarget($target);
        $this->assertArrayHasSameContents($this->getExpectedValues($variables), $route->getPathVariables());
    }

    public function pathExplosionProvider(): array
    {
        return [
            ['/{count*}', '/one,two,three', ['count']],
            ['{/count*}', '/one/two/three', ['count']],
            ['X{.list*}', 'X.red.green.blue', ['list']]
        ];
    }
}
