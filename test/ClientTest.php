<?php

namespace pjdietz\WellRESTed\Test;

use pjdietz\ShamServer\ShamServer;
use pjdietz\WellRESTed\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider httpMethodProvider
     */
    public function testCheckHttpMethod($method)
    {
        $host = "localhost";
        $port = 8080;
        $script = realpath(__DIR__ . "/sham-routers/method.php");

        $server = new ShamServer($host, $port, $script);

        $rqst = $this->getMockBuilder('pjdietz\WellRESTed\Request')->getMock();
        $rqst->expects($this->any())
            ->method("getUri")
            ->will($this->returnValue("http://$host:$port"));
        $rqst->expects($this->any())
            ->method("getMethod")
            ->will($this->returnValue($method));
        $rqst->expects($this->any())
            ->method("getPort")
            ->will($this->returnValue($port));
        $rqst->expects($this->any())
            ->method("getHeaders")
            ->will($this->returnValue(array()));

        $client = new Client();
        $resp = $client->request($rqst);
        $body = trim($resp->getBody());
        $this->assertEquals($method, $body);

        $server->stop();
    }

    public function httpMethodProvider()
    {
        return [
            ["GET"],
            ["POST"],
            ["PUT"],
            ["DELETE"],
            ["PATCH"],
            ["OPTIONS"]
        ];
    }
}
