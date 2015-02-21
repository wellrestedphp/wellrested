<?php

namespace pjdietz\WellRESTed\Test;

use Faker\Factory;
use pjdietz\ShamServer\ShamServer;
use pjdietz\WellRESTed\Client;
use pjdietz\WellRESTed\Request;

/**
 * @covers pjdietz\WellRESTed\Client
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider httpMethodProvider
     */
    public function testSendsHttpMethod($method)
    {
        $host = "localhost";
        $port = $this->getRandomNumberInRange(getenv("PORT"));
        $script = realpath(__DIR__ . "/sham-routers/method.php");

        $server = new ShamServer($host, $port, $script);

        $rqst = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $rqst->getUri()->willReturn("http://$host:$port");
        $rqst->getMethod()->willReturn($method);
        $rqst->getPort()->willReturn($port);
        $rqst->getHeaders()->willReturn([]);
        $rqst->getBody()->willReturn(null);

        $client = new Client();
        $resp = $client->request($rqst->reveal());
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
    public function testSendsHttpHeaders($headerKey, $headerValue)
    {
        $host = "localhost";
        $port = $this->getRandomNumberInRange(getenv("PORT"));
        $script = realpath(__DIR__ . "/sham-routers/headers.php");

        $server = new ShamServer($host, $port, $script);

        $rqst = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $rqst->getUri()->willReturn("http://$host:$port");
        $rqst->getMethod()->willReturn("GET");
        $rqst->getPort()->willReturn($port);
        $rqst->getHeaders()->willReturn([$headerKey => $headerValue]);
        $rqst->getBody()->willReturn(null);

        $client = new Client();
        $resp = $client->request($rqst->reveal());
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
    public function testSendsBody($body)
    {
        $host = "localhost";
        $port = $this->getRandomNumberInRange(getenv("PORT"));
        $script = realpath(__DIR__ . "/sham-routers/body.php");
        $server = new ShamServer($host, $port, $script);

        $rqst = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $rqst->getUri()->willReturn("http://$host:$port");
        $rqst->getMethod()->willReturn("POST");
        $rqst->getPort()->willReturn($port);
        $rqst->getHeaders()->willReturn([]);
        $rqst->getBody()->willReturn($body);

        $client = new Client();
        $resp = $client->request($rqst->reveal());
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
    public function testSendsForm($form)
    {
        $host = "localhost";
        $port = $this->getRandomNumberInRange(getenv("PORT"));
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

    public function testSetsCustomCurlOptionsOnInstantiation()
    {
        $host = "localhost";
        $port = $this->getRandomNumberInRange(getenv("PORT"));
        $script = realpath(__DIR__ . "/sham-routers/headers.php");
        $server = new ShamServer($host, $port, $script);

        $rqst = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $rqst->getUri()->willReturn("http://$host:$port");
        $rqst->getMethod()->willReturn("GET");
        $rqst->getPort()->willReturn($port);
        $rqst->getHeaders()->willReturn([]);
        $rqst->getBody()->willReturn(null);

        $cookieValue = "key=value";
        $client = new Client([CURLOPT_COOKIE => $cookieValue]);
        $resp = $client->request($rqst->reveal());
        $headers = json_decode($resp->getBody());
        $this->assertEquals($cookieValue, $headers->Cookie);

        $server->stop();
    }

    public function testSetsCustomCurlOptionsOnRequest()
    {
        $host = "localhost";
        $port = $this->getRandomNumberInRange(getenv("PORT"));
        $script = realpath(__DIR__ . "/sham-routers/headers.php");
        $server = new ShamServer($host, $port, $script);

        $rqst = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $rqst->getUri()->willReturn("http://$host:$port");
        $rqst->getMethod()->willReturn("GET");
        $rqst->getPort()->willReturn($port);
        $rqst->getHeaders()->willReturn([]);
        $rqst->getBody()->willReturn(null);

        $cookieValue = "key=value";
        $client = new Client();
        $resp = $client->request($rqst->reveal(), [CURLOPT_COOKIE => $cookieValue]);
        $headers = json_decode($resp->getBody());
        $this->assertEquals($cookieValue, $headers->Cookie);

        $server->stop();
    }

    /**
     * @dataProvider curlErrorProvider
     * @expectedException \pjdietz\WellRESTed\Exceptions\CurlException
     */
    public function testThrowsCurlException($uri, $opts)
    {
        $rqst = $this->prophesize("\\pjdietz\\WellRESTed\\Interfaces\\RequestInterface");
        $rqst->getUri()->willReturn($uri);
        $rqst->getMethod()->willReturn("GET");
        $rqst->getPort()->willReturn(parse_url($uri, PHP_URL_PORT));
        $rqst->getHeaders()->willReturn([]);
        $rqst->getBody()->willReturn(null);

        $client = new Client();
        $client->request($rqst->reveal(), $opts);
    }

    public function curlErrorProvider()
    {
        $port = $this->getRandomNumberInRange(getenv("FAIL_PORT"));
        return [
            ["http://localhost:{$port}", [
                CURLOPT_FAILONERROR, true,
                CURLOPT_TIMEOUT_MS, 10
            ]],
        ];
    }

    private function getRandomNumberInRange($range)
    {
        static $pattern = '/(\d+)\-(\d+)/';
        if (preg_match($pattern, $range, $matches)) {
            $lower = $matches[1];
            $upper = $matches[2];
            return rand($lower, $upper);
        } else {
            return $range;
        }
    }
}
