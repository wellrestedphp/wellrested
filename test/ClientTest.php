<?php

use pjdietz\WellRESTed\Client;
use pjdietz\WellRESTed\Request;
use pjdietz\WellRESTed\Test;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testFake()
    {
        $this->assertTrue(true);
    }

    /**
     * @dataProvider curlProvider
     */
    public function testCurl($method, $uri, $opts, $code)
    {
        $rqst = $this->getMockBuilder('pjdietz\WellRESTed\Request')->getMock();
        $rqst->expects($this->any())
            ->method("getUri")
            ->will($this->returnValue($uri));
        $rqst->expects($this->any())
            ->method("getMethod")
            ->will($this->returnValue($method));
        $rqst->expects($this->any())
            ->method("getPort")
            ->will($this->returnValue(80));
        $rqst->expects($this->any())
            ->method("getHeaders")
            ->will($this->returnValue(array(
                        "Cache-control" => "max-age=0"
                    )));

        $client = new Client(array(CURLOPT_HTTPHEADER => array("Cache-control" => "max-age=0")));
        $resp = $client->request($rqst, $opts);
        $this->assertEquals($code, $resp->getStatusCode());
    }

    public function curlProvider()
    {
        return [
            ["GET", "http://icanhasip.com", [
                [CURLOPT_MAXREDIRS => 2]
            ], 200],
            ["POST", "http://icanhasip.com", [], 200],
            ["PUT", "http://icanhasip.com", [], 405],
            ["DELETE", "http://icanhasip.com", [], 405]
        ];
    }

    /**
     * @dataProvider curlErrorProvider
     * @expectedException \pjdietz\WellRESTed\Exceptions\CurlException
     */
    public function testErrorCurl($uri, $opts)
    {
        $rqst = $this->getMockBuilder('pjdietz\WellRESTed\Request')->getMock();
        $rqst->expects($this->any())
            ->method("getUri")
            ->will($this->returnValue($uri));
        $rqst->expects($this->any())
            ->method("getHeaders")
            ->will($this->returnValue(array(
                        "Cache-control" => "max-age=0"
                    )));

        $rqst = new Request($uri);
        $client = new Client();
        $client->request($rqst, $opts);
    }

    public function curlErrorProvider()
    {
        return [
            ["http://localhost:9991", [
                CURLOPT_FAILONERROR, true,
                CURLOPT_TIMEOUT_MS, 10
            ]],
        ];
    }
}
