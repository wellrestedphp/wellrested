<?php

namespace WellRESTed\Routing\Route;

use Prophecy\PhpUnit\ProphecyTrait;
use RuntimeException;
use WellRESTed\Test\TestCase;

class RegexRouteTest extends TestCase
{
    use ProphecyTrait;

    private $methodMap;

    protected function setUp(): void
    {
        $this->methodMap = $this->prophesize(MethodMap::class);
    }

    public function testReturnsPatternType(): void
    {
        $route = new RegexRoute('/', $this->methodMap->reveal());
        $this->assertSame(Route::TYPE_PATTERN, $route->getType());
    }

    /**
     * @dataProvider matchingRouteProvider
     * @param string $pattern
     * @param string $path
     */
    public function testMatchesTarget(string $pattern, string $path): void
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($path));
    }

    /**
     * @dataProvider matchingRouteProvider
     * @param string $pattern
     * @param string $path
     */
    public function testMatchesTargetByRegex(string $pattern, string $path): void
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $this->assertTrue($route->matchesRequestTarget($path));
    }

    /**
     * @dataProvider matchingRouteProvider
     * @param string $pattern
     * @param string $path
     * @param array $expectedCaptures
     */
    public function testExtractsPathVariablesByRegex(string $pattern, string $path, array $expectedCaptures): void
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $route->matchesRequestTarget($path);
        $this->assertEquals($expectedCaptures, $route->getPathVariables());
    }

    public function matchingRouteProvider(): array
    {
        return [
            ['~/cat/[0-9]+~', '/cat/2', [0 => '/cat/2']],
            ['#/dog/.*#', '/dog/his-name-is-bear', [0 => '/dog/his-name-is-bear']],
            ['~/cat/([0-9]+)~', '/cat/2', [
                0 => '/cat/2',
                1 => '2'
            ]],
            ['~/dog/(?<id>[0-9+])~', '/dog/2', [
                0 => '/dog/2',
                1 => '2',
                'id' => '2'
            ]]
        ];
    }

    /**
     * @dataProvider mismatchingRouteProvider
     * @param string $pattern
     * @param string $path
     */
    public function testDoesNotMatchNonMatchingTarget(string $pattern, string $path): void
    {
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $this->assertFalse($route->matchesRequestTarget($path));
    }

    public function mismatchingRouteProvider(): array
    {
        return [
            ['~/cat/[0-9]+~', '/cat/molly'],
            ['~/cat/[0-9]+~', '/dog/bear'],
            ['#/dog/.*#', '/dog']
        ];
    }

    /**
     * @dataProvider invalidRouteProvider
     * @param string $pattern
     */
    public function testThrowsExceptionOnInvalidPattern(string $pattern): void
    {
        $this->expectException(RuntimeException::class);
        $route = new RegexRoute($pattern, $this->methodMap->reveal());
        $level = error_reporting();
        error_reporting($level & ~E_WARNING);
        $route->matchesRequestTarget('/');
        error_reporting($level);
    }

    public function invalidRouteProvider()
    {
        return [
            ['~/unterminated'],
            ['/nope']
        ];
    }
}
