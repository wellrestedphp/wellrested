<?php

namespace pjdietz\WellRESTed\Test;

use Faker\Factory;
use pjdietz\ShamServer\ShamServer;
use pjdietz\WellRESTed\Client;
use pjdietz\WellRESTed\Request;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider httpMethodProvider
     */
    public function testSendHttpMethod($method)
    {
        $host = "localhost";
        $port = getenv("PORT");
        $script = realpath(__DIR__ . "/sham-routers/method.php");

        $server = new ShamServer($host, $port, $script);

        $rqst = $this->getMockBuilder('pjdietz\WellRESTed\Interfaces\RequestInterface')->getMock();
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

    /**
     * @dataProvider httpHeaderProvider
     */
    public function testSendHttpHeaders($headerKey, $headerValue)
    {
        $host = "localhost";
        $port = getenv("PORT");
        $script = realpath(__DIR__ . "/sham-routers/headers.php");

        $server = new ShamServer($host, $port, $script);

        $rqst = $this->getMockBuilder('pjdietz\WellRESTed\Interfaces\RequestInterface')->getMock();
        $rqst->expects($this->any())
            ->method("getUri")
            ->will($this->returnValue("http://$host:$port"));
        $rqst->expects($this->any())
            ->method("getMethod")
            ->will($this->returnValue("GET"));
        $rqst->expects($this->any())
            ->method("getPort")
            ->will($this->returnValue($port));
        $rqst->expects($this->any())
            ->method("getHeaders")
            ->will($this->returnValue(array($headerKey => $headerValue)));

        $client = new Client();
        $resp = $client->request($rqst);
        $headers = json_decode($resp->getBody());
        $this->assertEquals($headerValue, $headers->{$headerKey});

        $server->stop();
    }

    public function httpHeaderProvider()
    {
        return [
            ["Cache-Control", "max-age=0"],
            ["X-Custom-Header", "custom value"],
            ["Accept-Charset", "utf-8"]
        ];
    }

    /**
     * @dataProvider bodyProvider
     */
    public function testSendBody($body)
    {
        $host = "localhost";
        $port = getenv("PORT");
        $script = realpath(__DIR__ . "/sham-routers/body.php");
        $server = new ShamServer($host, $port, $script);

        $rqst = $this->getMockBuilder('pjdietz\WellRESTed\Interfaces\RequestInterface')->getMock();
        $rqst->expects($this->any())
            ->method("getUri")
            ->will($this->returnValue("http://$host:$port"));
        $rqst->expects($this->any())
            ->method("getMethod")
            ->will($this->returnValue("POST"));
        $rqst->expects($this->any())
            ->method("getPort")
            ->will($this->returnValue($port));
        $rqst->expects($this->any())
            ->method("getHeaders")
            ->will($this->returnValue(array()));
        $rqst->expects($this->any())
            ->method("getBody")
            ->will($this->returnValue($body));

        $client = new Client();
        $resp = $client->request($rqst);
        $this->assertEquals($body, $resp->getBody());
        $server->stop();
    }

    public function bodyProvider()
    {
        $faker = Factory::create();
        return [
            [$faker->text()],
            [$faker->text()],
            [$faker->text()]
        ];
    }

    /**
     * @dataProvider formProvider
     */
    public function testSendForm($form)
    {
        $host = "localhost";
        $port = getenv("PORT");
        $script = realpath(__DIR__ . "/sham-routers/formFields.php");
        $server = new ShamServer($host, $port, $script);

        $rqst = new Request("http://$host:$port");
        $rqst->setMethod("POST");
        $rqst->setFormFields($form);
        $client = new Client();
        $resp = $client->request($rqst);

        $body = json_decode($resp->getBody(), true);
        $this->assertEquals($form, $body);

        $server->stop();
    }

    public function formProvider()
    {
        $faker = Factory::create();
        return [
            [
                [
                    "firstName" => $faker->firstName,
                    "lastName" => $faker->lastName,
                    "email" => $faker->email
                ]
            ],
        ];
    }

    public function testSetCustomCurlOptionsOnInstantiation()
    {
        $host = "localhost";
        $port = getenv("PORT");
        $script = realpath(__DIR__ . "/sham-routers/headers.php");
        $server = new ShamServer($host, $port, $script);

        $rqst = $this->getMockBuilder('pjdietz\WellRESTed\Interfaces\RequestInterface')->getMock();
        $rqst->expects($this->any())
            ->method("getUri")
            ->will($this->returnValue("http://$host:$port"));
        $rqst->expects($this->any())
            ->method("getMethod")
            ->will($this->returnValue("GET"));
        $rqst->expects($this->any())
            ->method("getPort")
            ->will($this->returnValue($port));
        $rqst->expects($this->any())
            ->method("getHeaders")
            ->will($this->returnValue(array()));

        $cookieValue = "key=value";
        $client = new Client([CURLOPT_COOKIE => $cookieValue]);
        $resp = $client->request($rqst);
        $headers = json_decode($resp->getBody());
        $this->assertEquals($cookieValue, $headers->Cookie);

        $server->stop();
    }

    public function testSetCustomCurlOptionsOnRequest()
    {
        $host = "localhost";
        $port = getenv("PORT");
        $script = realpath(__DIR__ . "/sham-routers/headers.php");
        $server = new ShamServer($host, $port, $script);

        $rqst = $this->getMockBuilder('pjdietz\WellRESTed\Interfaces\RequestInterface')->getMock();
        $rqst->expects($this->any())
            ->method("getUri")
            ->will($this->returnValue("http://$host:$port"));
        $rqst->expects($this->any())
            ->method("getMethod")
            ->will($this->returnValue("GET"));
        $rqst->expects($this->any())
            ->method("getPort")
            ->will($this->returnValue($port));
        $rqst->expects($this->any())
            ->method("getHeaders")
            ->will($this->returnValue(array()));

        $cookieValue = "key=value";
        $client = new Client();
        $resp = $client->request($rqst, [CURLOPT_COOKIE => $cookieValue]);
        $headers = json_decode($resp->getBody());
        $this->assertEquals($cookieValue, $headers->Cookie);

        $server->stop();
    }

    /**
     * @dataProvider curlErrorProvider
     * @expectedException \pjdietz\WellRESTed\Exceptions\CurlException
     */
    public function testFailOnCurlError($uri, $opts)
    {
        $rqst = $this->getMockBuilder('pjdietz\WellRESTed\Interfaces\RequestInterface')->getMock();
        $rqst->expects($this->any())
            ->method("getUri")
            ->will($this->returnValue($uri));
        $rqst->expects($this->any())
            ->method("getMethod")
            ->will($this->returnValue("GET"));
        $rqst->expects($this->any())
            ->method("getPort")
            ->will($this->returnValue(parse_url($uri, PHP_URL_PORT)));
        $rqst->expects($this->any())
            ->method("getHeaders")
            ->will($this->returnValue(array()));

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
