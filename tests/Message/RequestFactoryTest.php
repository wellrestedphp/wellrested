<?php

namespace WellRESTed\Message;

use WellRESTed\Test\TestCase;

class RequestFactoryTest extends TestCase
{
    public function testCreatesRequestFromString(): void
    {
        $method = 'GET';
        $uri = 'http://localhost:8080';

        $factory = new RequestFactory();
        $request = $factory->createRequest($method, $uri);

        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, $request->getUri());
    }

    public function testCreatesRequestFromUri(): void
    {
        $method = 'POST';
        $uri = new Uri('http://localhost:8080');

        $factory = new RequestFactory();
        $request = $factory->createRequest($method, $uri);

        $this->assertEquals($method, $request->getMethod());
        $this->assertEquals($uri, $request->getUri());
    }
}