<?php

namespace WellRESTed\Test\Unit\Message;

use WellRESTed\Message\ServerRequest;

class ServerRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers WellRESTed\Message\ServerRequest::__construct
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testCreatesInstance()
    {
        $request = new ServerRequest();
        $this->assertNotNull($request);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getServerRequest
     * @uses WellRESTed\Message\ServerRequest::__construct
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     * @preserveGlobalState disabled
     */
    public function testServerRequestProvidesRequest()
    {
        $_SERVER = [
            "HTTP_HOST" => "localhost",
            "QUERY_STRING" => "guinea_pig=Claude&hamster=Fizzgig"
        ];
        $_COOKIE = [
            "cat" => "Molly"
        ];
        $_FILES = [
            "file" => [
                "name" => "MyFile.jpg",
                "type" => "image/jpeg",
                "tmp_name" => "/tmp/php/php6hst32",
                "error" => "UPLOAD_ERR_OK",
                "size" => 98174
            ]
        ];
        $_POST = [
            "dog" => "Bear"
        ];
        $request = ServerRequest::getServerRequest();
        $this->assertNotNull($request);
        return $request;
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getServerParams
     * @preserveGlobalState disabled
     * @depends testServerRequestProvidesRequest
     */
    public function testServerRequestProvidesServerParams($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("localhost", $request->getServerParams()["HTTP_HOST"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getCookieParams
     * @preserveGlobalState disabled
     * @depends testServerRequestProvidesRequest
     */
    public function testServerRequestProvidesCookieParams($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("Molly", $request->getCookieParams()["cat"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getQueryParams
     * @preserveGlobalState disabled
     * @depends testServerRequestProvidesRequest
     */
    public function testServerRequestProvidesQueryParams($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("Claude", $request->getQueryParams()["guinea_pig"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getFileParams
     * @preserveGlobalState disabled
     * @depends testServerRequestProvidesRequest
     */
    public function testServerRequestProvidesFilesParams($request)
    {
        /** @var ServerRequest $request */
        $this->assertEquals("MyFile.jpg", $request->getFileParams()["file"]["name"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withCookieParams
     * @uses WellRESTed\Message\ServerRequest::getCookieParams
     * @uses WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @preserveGlobalState disabled
     * @depends testServerRequestProvidesRequest
     */
    public function testWithCookieParamsCreatesNewInstance($request1)
    {
        /** @var ServerRequest $request1 */
        $request2 = $request1->withCookieParams([
            "cat" => "Oscar"
        ]);
        $this->assertEquals("Molly", $request1->getCookieParams()["cat"]);
        $this->assertEquals("Oscar", $request2->getCookieParams()["cat"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withQueryParams
     * @uses WellRESTed\Message\ServerRequest::getQueryParams
     * @uses WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @preserveGlobalState disabled
     * @depends testServerRequestProvidesRequest
     */
    public function testWithQueryParamsCreatesNewInstance($request1)
    {
        /** @var ServerRequest $request1 */
        $request2 = $request1->withQueryParams([
            "guinea_pig" => "Clyde"
        ]);
        $this->assertEquals("Claude", $request1->getQueryParams()["guinea_pig"]);
        $this->assertEquals("Clyde", $request2->getQueryParams()["guinea_pig"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getParsedBody
     * @uses WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     * @preserveGlobalState disabled
     * @depends testServerRequestProvidesRequest
     */
    public function testGetParsedBodyReturnsFormFieldsForUrlencodedForm($request)
    {
        $_POST = [
            "dog" => "Bear"
        ];

        /** @var ServerRequest $request */
        $request = $request->withHeader("Content-type", "application/x-www-form-urlencoded");
        $this->assertEquals("Bear", $request->getParsedBody()["dog"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getParsedBody
     * @uses WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     * @preserveGlobalState disabled
     * @depends testServerRequestProvidesRequest
     */
    public function testGetParsedBodyReturnsFormFieldsForMultipartForm($request)
    {
        $_POST = [
            "dog" => "Bear"
        ];

        /** @var ServerRequest $request */
        $request = $request->withHeader("Content-type", "multipart/form-data");
        $this->assertEquals("Bear", $request->getParsedBody()["dog"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withParsedBody
     * @uses WellRESTed\Message\ServerRequest::getParsedBody
     * @uses WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     * @preserveGlobalState disabled
     * @depends testServerRequestProvidesRequest
     */
    public function testWithParsedBodyCreatesNewInstance($request1)
    {
        $_POST = [
            "dog" => "Bear"
        ];

        /** @var ServerRequest $request1 */
        $request1 = $request1->withHeader("Content-type", "application/x-www-form-urlencoded");
        $body1 = $request1->getParsedBody();
        $request2 = $request1->withParsedBody([
            "guinea_pig" => "Clyde"
        ]);
        $body2 = $request2->getParsedBody();

        $this->assertEquals("Bear", $body1["dog"]);
        $this->assertEquals("Clyde", $body2["guinea_pig"]);
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\ServerRequest::__construct
     * @uses WellRESTed\Message\ServerRequest::withParsedBody
     * @uses WellRESTed\Message\ServerRequest::getParsedBody
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testCloneMakesDeepCopiesOfParsedBody()
    {
        $body = (object) [
            "cat" => "Dog"
        ];

        $request1 = new ServerRequest();
        $request1 = $request1->withParsedBody($body);
        $request2 = $request1->withHeader("X-extra", "hello world");
        $this->assertEquals($request1->getParsedBody(), $request2->getParsedBody());
        $this->assertNotSame($request1->getParsedBody(), $request2->getParsedBody());
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withAttribute
     * @covers WellRESTed\Message\ServerRequest::getAttribute
     * @uses WellRESTed\Message\ServerRequest::__construct
     * @uses WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\ServerRequest::withParsedBody
     * @uses WellRESTed\Message\ServerRequest::getParsedBody
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithAttributeCreatesNewInstance()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $this->assertEquals("Molly", $request->getAttribute("cat"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withAttribute
     * @uses WellRESTed\Message\ServerRequest::getAttribute
     * @uses WellRESTed\Message\ServerRequest::__construct
     * @uses WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\ServerRequest::withParsedBody
     * @uses WellRESTed\Message\ServerRequest::getParsedBody
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithAttributePreserversOtherAttributes()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $request = $request->withAttribute("dog", "Bear");
        $this->assertEquals("Molly", $request->getAttribute("cat"));
        $this->assertEquals("Bear", $request->getAttribute("dog"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getAttribute
     * @uses WellRESTed\Message\ServerRequest::__construct
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetAttributeReturnsDefaultIfNotSet()
    {
        $request = new ServerRequest();
        $this->assertEquals("Oscar", $request->getAttribute("cat", "Oscar"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withoutAttribute
     * @uses WellRESTed\Message\ServerRequest::withAttribute
     * @uses WellRESTed\Message\ServerRequest::getAttribute
     * @uses WellRESTed\Message\ServerRequest::__construct
     * @uses WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\ServerRequest::withParsedBody
     * @uses WellRESTed\Message\ServerRequest::getParsedBody
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithoutAttributeCreatesNewInstance()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $request = $request->withoutAttribute("cat");
        $this->assertEquals("Oscar", $request->getAttribute("cat", "Oscar"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::withoutAttribute
     * @uses WellRESTed\Message\ServerRequest::withAttribute
     * @uses WellRESTed\Message\ServerRequest::getAttribute
     * @uses WellRESTed\Message\ServerRequest::__construct
     * @uses WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\ServerRequest::withParsedBody
     * @uses WellRESTed\Message\ServerRequest::getParsedBody
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testWithoutAttributePreservesOtherAttributes()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $request = $request->withAttribute("dog", "Bear");
        $request = $request->withoutAttribute("cat");
        $this->assertEquals("Bear", $request->getAttribute("dog"));
        $this->assertEquals("Oscar", $request->getAttribute("cat", "Oscar"));
    }

    /**
     * @covers WellRESTed\Message\ServerRequest::getAttributes
     * @uses WellRESTed\Message\ServerRequest::withAttribute
     * @uses WellRESTed\Message\ServerRequest::__construct
     * @uses WellRESTed\Message\ServerRequest::__clone
     * @uses WellRESTed\Message\ServerRequest::withParsedBody
     * @uses WellRESTed\Message\ServerRequest::getParsedBody
     * @uses WellRESTed\Message\Request
     * @uses WellRESTed\Message\Message
     * @uses WellRESTed\Message\HeaderCollection
     */
    public function testGetAttributesReturnsAllAttributes()
    {
        $request = new ServerRequest();
        $request = $request->withAttribute("cat", "Molly");
        $request = $request->withAttribute("dog", "Bear");
        $attributes = $request->getAttributes();
        $this->assertEquals("Molly", $attributes["cat"]);
        $this->assertEquals("Bear", $attributes["dog"]);
    }


}
