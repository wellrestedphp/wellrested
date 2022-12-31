<?php

declare(strict_types=1);

namespace WellRESTed\Routing\Route;

use WellRESTed\Server;
use WellRESTed\Test\TestCase;

class RouteFactoryTest extends TestCase
{
    private Server $server;

    protected function setUp(): void
    {
        $this->server = new Server();
    }

    public function testCreatesStaticRoute(): void
    {
        $factory = new RouteFactory($this->server);
        $route = $factory->create('/cats/');
        $this->assertSame(Route::TYPE_STATIC, $route->getType());
    }

    public function testCreatesPrefixRoute(): void
    {
        $factory = new RouteFactory($this->server);
        $route = $factory->create('/cats/*');
        $this->assertSame(Route::TYPE_PREFIX, $route->getType());
    }

    public function testCreatesRegexRoute(): void
    {
        $factory = new RouteFactory($this->server);
        $route = $factory->create('~/cat/[0-9]+~');
        $this->assertSame(Route::TYPE_PATTERN, $route->getType());
    }

    public function testCreatesTemplateRoute(): void
    {
        $factory = new RouteFactory($this->server);
        $route = $factory->create('/cat/{id}');
        $this->assertSame(Route::TYPE_PATTERN, $route->getType());
    }
}
