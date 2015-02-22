<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\WellRESTed\HandlerUnpacker;
use pjdietz\WellRESTed\Interfaces\HandlerInterface;
use pjdietz\WellRESTed\Interfaces\RequestInterface;

/**
 * @covers pjdietz\WellRESTed\HandlerUnpacker
 */
class HandlerUnpackerTest extends \PHPUnit_Framework_TestCase
{
    public function testUnpacksFromCallable()
    {
        $handlerContainer = function () {
            return new HandlerUnpackerTest_Handler();
        };
        $handlerUnpacker = new HandlerUnpacker();
        $handler = $handlerUnpacker->unpack($handlerContainer);
        $this->assertInstanceOf("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface", $handler);
    }

    public function testPropagatesArgumentsToCallable()
    {
        $request = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $args = [
            "cat" => "Molly"
        ];

        $callableRequest = null;
        $callableArguments = null;

        $handlerCallable = function ($rqst, $args) use (&$callableRequest, &$callableArguments) {
            $callableRequest = $rqst;
            $callableArguments = $args;
            return null;
        };

        $handlerUnpacker = new HandlerUnpacker();
        $handlerUnpacker->unpack($handlerCallable, $request->reveal(), $args);

        $this->assertSame($callableRequest, $request->reveal());
        $this->assertSame($callableArguments, $args);
    }

    public function testUnpacksFromString()
    {
        $handlerContainer = __NAMESPACE__ . "\\HandlerUnpackerTest_Handler";
        $handlerUnpacker = new HandlerUnpacker();
        $handler = $handlerUnpacker->unpack($handlerContainer);
        $this->assertInstanceOf("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface", $handler);
    }

    public function testUnpacksInstance()
    {
        $handler = new HandlerUnpackerTest_Handler();
        $handlerUnpacker = new HandlerUnpacker();
        $handler = $handlerUnpacker->unpack($handler);
        $this->assertInstanceOf("\\pjdietz\\WellRESTed\\Interfaces\\HandlerInterface", $handler);
    }
}

class HandlerUnpackerTest_Handler implements HandlerInterface
{
    public function getResponse(RequestInterface $request, array $args = null)
    {
        return null;
    }
}
