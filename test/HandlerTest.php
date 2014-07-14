<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\Exceptions\HttpExceptions\HttpException;
use pjdietz\WellRESTed\Exceptions\HttpExceptions\NotFoundException;
use pjdietz\WellRESTed\Handler;

class HandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testResponse()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockHandler = $this->getMockForAbstractClass('\pjdietz\WellRESTed\Handler');
        $this->assertNotNull($mockHandler->getResponse($mockRequest));
    }

    /**
     * @dataProvider verbProvider
     */
    public function testVerb($verb)
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue($verb));

        $mockHandler = $this->getMockForAbstractClass('\pjdietz\WellRESTed\Handler');
        $this->assertNotNull($mockHandler->getResponse($mockRequest));
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

    public function testHttException()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue("GET"));

        $handler = new ExceptionHandler();
        $resp = $handler->getResponse($mockRequest);
        $this->assertEquals(404, $resp->getStatusCode());
    }

    public function testAllowedMethods()
    {
        $mockRequest = $this->getMock('\pjdietz\WellRESTed\Interfaces\RequestInterface');
        $mockRequest->expects($this->any())
            ->method('getMethod')
            ->will($this->returnValue("OPTIONS"));

        $mockHandler = $this->getMockForAbstractClass('\pjdietz\WellRESTed\Handler');
        $mockHandler->expects($this->any())
            ->method('getAllowedMethods')
            ->will($this->returnValue(["GET","POST"]));

        $handler = new OptionsHandler();

        $resp = $handler->getResponse($mockRequest);
        $this->assertEquals("GET, POST", $resp->getHeader("Allow"));
    }

}

class ExceptionHandler extends Handler
{
    protected function get()
    {
        throw new NotFoundException();
    }
}

class OptionsHandler extends Handler
{
    protected function getAllowedMethods()
    {
        return ["GET","POST"];
    }
}
