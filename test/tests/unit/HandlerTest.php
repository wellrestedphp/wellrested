<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Exceptions\HttpExceptions\NotFoundException;
use pjdietz\WellRESTed\Handler;

/**
 * @covers pjdietz\WellRESTed\Handler
 */
class HandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsResponse()
    {
        $request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $handler = $this->getMockForAbstractClass("\\pjdietz\\WellRESTed\\Handler");
        $response = $handler->getResponse($request->reveal());
        $this->assertNotNull($response);
    }

    /**
     * @dataProvider verbProvider
     */
    public function testCallsMethodForHttpVerb($method)
    {
        $request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $request->getMethod()->willReturn($method);
        $handler = $this->getMockForAbstractClass("\\pjdietz\\WellRESTed\\Handler");
        $response = $handler->getResponse($request->reveal());
        $this->assertNotNull($response);
    }

    public function verbProvider()
    {
        return [
            ["GET"],
            ["POST"],
            ["PUT"],
            ["DELETE"],
            ["HEAD"],
            ["PATCH"],
            ["OPTIONS"],
            ["NOTALLOWED"]
        ];
    }

    public function testTranslatesHttpExceptionToResponse()
    {
        $request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $request->getMethod()->willReturn("GET");

        $handler = new ExceptionHandler();
        $response = $handler->getResponse($request->reveal());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testProvidesAllowHeader()
    {
        $request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $request->getMethod()->willReturn("OPTIONS");

        $handler = new OptionsHandler();
        $response = $handler->getResponse($request->reveal());
        $this->assertEquals("GET, POST", $response->getHeader("Allow"));
    }

}

class OptionsHandler extends Handler
{
    protected function getAllowedMethods()
    {
        return ["GET","POST"];
    }
}

class ExceptionHandler extends Handler
{
    protected function get()
    {
        throw new NotFoundException();
    }
}
