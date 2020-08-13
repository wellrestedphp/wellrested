<?php

namespace WellRESTed\Message;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use WellRESTed\Test\TestCase;

/** @backupGlobals enabled */
class ServerRequestMarshallerTest extends TestCase
{
    /** @var ServerRequestMarshaller */
    private $marshaller;

    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER = [
            'HTTP_HOST' => 'localhost',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            'QUERY_STRING' => 'cat=molly&kitten=aggie'
        ];

        $_COOKIE = [
            'dog' => 'Bear',
            'hamster' => 'Dusty'
        ];

        $this->marshaller = new ServerRequestMarshaller();
    }

    // -------------------------------------------------------------------------
    // Psr\Http\Message\MessageInterface

    // -------------------------------------------------------------------------
    // Protocol Version

    /**
     * @dataProvider protocolVersionProvider
     * @param $expectedProtocol
     * @param $actualProtocol
     */
    public function testProvidesProtocolVersion(string $expectedProtocol, ?string $actualProtocol): void
    {
        $_SERVER['SERVER_PROTOCOL'] = $actualProtocol;
        $request = $this->marshaller->getServerRequest();
        $this->assertEquals($expectedProtocol, $request->getProtocolVersion());
    }

    public function protocolVersionProvider(): array
    {
        return [
            ['1.1', 'HTTP/1.1'],
            ['1.0', 'HTTP/1.0'],
            ['1.1', null],
            ['1.1', 'INVALID']
        ];
    }

    // -------------------------------------------------------------------------
    // Headers

    public function testProvidesHeadersFromHttpFields(): void
    {
        $_SERVER = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded'
        ];
        $request = $this->marshaller->getServerRequest();
        $this->assertEquals(['application/json'], $request->getHeader('Accept'));
        $this->assertEquals(['application/x-www-form-urlencoded'], $request->getHeader('Content-type'));
    }

    public function testProvidesApacheContentHeaders(): void
    {
        $_SERVER = [
            'CONTENT_LENGTH' => '1024',
            'CONTENT_TYPE' => 'application/json'
        ];
        $request = $this->marshaller->getServerRequest();
        $this->assertEquals('1024', $request->getHeaderLine('Content-length'));
        $this->assertEquals('application/json', $request->getHeaderLine('Content-type'));
    }

    public function testDoesNotProvideEmptyApacheContentHeaders(): void
    {
        $_SERVER = [
            'CONTENT_LENGTH' => '',
            'CONTENT_TYPE' => ' '
        ];
        $request = $this->marshaller->getServerRequest();
        $this->assertFalse($request->hasHeader('Content-length'));
        $this->assertFalse($request->hasHeader('Content-type'));
    }

    // -------------------------------------------------------------------------
    // Body

    public function testProvidesBodyFromInputStream(): void
    {
        $tempFilePath = tempnam(sys_get_temp_dir(), 'test');
        $content = 'Body content';
        file_put_contents($tempFilePath, $content);

        $request = $this->marshaller->getServerRequest(
            null,
            null,
            null,
            null,
            null,
            $tempFilePath
        );
        unlink($tempFilePath);

        $this->assertEquals($content, (string) $request->getBody());
    }

    // -------------------------------------------------------------------------
    // Psr\Http\Message\RequestInterface

    // -------------------------------------------------------------------------
    // Request Target

    /**
     * @dataProvider requestTargetProvider
     * @param $expectedRequestTarget
     * @param $actualRequestUri
     */
    public function testProvidesRequestTarget(string $expectedRequestTarget, ?string $actualRequestUri): void
    {
        $_SERVER['REQUEST_URI'] = $actualRequestUri;
        $request = $this->marshaller->getServerRequest();
        $this->assertEquals($expectedRequestTarget, $request->getRequestTarget());
    }

    public function requestTargetProvider(): array
    {
        return [
            ['/', '/'],
            ['/hello', '/hello'],
            ['/my/path.txt', '/my/path.txt'],
            ['/', null]
        ];
    }

    // -------------------------------------------------------------------------
    // Method

    /**
     * @dataProvider methodProvider
     * @param $expectedMethod
     * @param $serverMethod
     */
    public function testProvidesMethod($expectedMethod, $serverMethod)
    {
        $_SERVER['REQUEST_METHOD'] = $serverMethod;
        $request = $this->marshaller->getServerRequest();
        $this->assertEquals($expectedMethod, $request->getMethod());
    }

    public function methodProvider()
    {
        return [
            ['GET', 'GET'],
            ['POST', 'POST'],
            ['DELETE', 'DELETE'],
            ['PUT', 'PUT'],
            ['OPTIONS', 'OPTIONS'],
            ['GET', null]
        ];
    }

    // -------------------------------------------------------------------------
    // URI

    /**
     * @dataProvider uriProvider
     * @param UriInterface $expected
     * @param array $serverParams
     */
    public function testProvidesUri(UriInterface $expected, array $serverParams): void
    {
        $request = $this->marshaller->getServerRequest($serverParams);
        $this->assertEquals($expected, $request->getUri());
    }

    public function uriProvider()
    {
        return [
            [
                new Uri('http://localhost/path'),
                [
                    'HTTPS' => 'off',
                    'HTTP_HOST' => 'localhost',
                    'REQUEST_URI' => '/path',
                    'QUERY_STRING' => ''
                ]
            ],
            [
                new Uri('https://foo.com/path/to/stuff?cat=molly'),
                [
                    'HTTPS' => '1',
                    'HTTP_HOST' => 'foo.com',
                    'REQUEST_URI' => '/path/to/stuff?cat=molly',
                    'QUERY_STRING' => 'cat=molly'
                ]
            ],
            [
                new Uri('http://foo.com:8080/path/to/stuff?cat=molly'),
                [
                    'HTTP' => '1',
                    'HTTP_HOST' => 'foo.com:8080',
                    'REQUEST_URI' => '/path/to/stuff?cat=molly',
                    'QUERY_STRING' => 'cat=molly'
                ]
            ]
        ];
    }

    // -------------------------------------------------------------------------
    // Psr\Http\Message\ServerRequestInterface

    // -------------------------------------------------------------------------
    // Server Params

    public function testProvidesServerParams(): void
    {
        $request = $this->marshaller->getServerRequest();
        $this->assertEquals($_SERVER, $request->getServerParams());
    }

    // -------------------------------------------------------------------------
    // Cookies

    public function testProvidesCookieParams(): void
    {
        $request = $this->marshaller->getServerRequest();
        $this->assertEquals($_COOKIE, $request->getCookieParams());
    }

    // -------------------------------------------------------------------------
    // Query

    public function testProvidesQueryParams(): void
    {
        $request = $this->marshaller->getServerRequest();
        $query = $request->getQueryParams();
        $this->assertCount(2, $query);
        $this->assertEquals('molly', $query['cat']);
        $this->assertEquals('aggie', $query['kitten']);
    }

    // -------------------------------------------------------------------------
    // Uploaded Files

    /**
     * @dataProvider uploadedFileProvider
     * @param UploadedFileInterface $file
     * @param array $path
     */
    public function testGetServerRequestReadsUploadedFiles(UploadedFileInterface $file, array $path): void
    {
        $_FILES = [
            'single' => [
                'name' => 'single.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/php9hNlHe',
                'error' => UPLOAD_ERR_OK,
                'size' => 524
            ],
            'nested' => [
                'level2' => [
                    'name' => 'nested.json',
                    'type' => 'application/json',
                    'tmp_name' => '/tmp/phpadhjk',
                    'error' => UPLOAD_ERR_OK,
                    'size' => 1024
                ]
            ],
            'nestedList' => [
                'level2' => [
                    'name' => [
                        0 => 'nestedList0.jpg',
                        1 => 'nestedList1.jpg',
                        2 => ''
                    ],
                    'type' => [
                        0 => 'image/jpeg',
                        1 => 'image/jpeg',
                        2 => ''
                    ],
                    'tmp_name' => [
                        0 => '/tmp/phpjpg0',
                        1 => '/tmp/phpjpg1',
                        2 => ''
                    ],
                    'error' => [
                        0 => UPLOAD_ERR_OK,
                        1 => UPLOAD_ERR_OK,
                        2 => UPLOAD_ERR_NO_FILE
                    ],
                    'size' => [
                        0 => 256,
                        1 => 4096,
                        2 => 0
                    ]
                ]
            ],
            'nestedDictionary' => [
                'level2' => [
                    'name' => [
                        'file0' => 'nestedDictionary0.jpg',
                        'file1' => 'nestedDictionary1.jpg'
                    ],
                    'type' => [
                        'file0' => 'image/png',
                        'file1' => 'image/png'
                    ],
                    'tmp_name' => [
                        'file0' => '/tmp/phppng0',
                        'file1' => '/tmp/phppng1'
                    ],
                    'error' => [
                        'file0' => UPLOAD_ERR_OK,
                        'file1' => UPLOAD_ERR_OK
                    ],
                    'size' => [
                        'file0' => 256,
                        'file1' => 4096
                    ]
                ]
            ]
        ];
        $request = $this->marshaller->getServerRequest();
        $current = $request->getUploadedFiles();
        foreach ($path as $item) {
            $current = $current[$item];
        }
        $this->assertEquals($file, $current);
    }

    public function uploadedFileProvider(): array
    {
        return [
            [new UploadedFile('single.txt', 'text/plain', 524, '/tmp/php9hNlHe', UPLOAD_ERR_OK), ['single']],
            [new UploadedFile('nested.json', 'application/json', 1024, '/tmp/phpadhjk', UPLOAD_ERR_OK), ['nested', 'level2']],
            [new UploadedFile('nestedList0.jpg', 'image/jpeg', 256, '/tmp/phpjpg0', UPLOAD_ERR_OK), ['nestedList', 'level2', 0]],
            [new UploadedFile('nestedList1.jpg', 'image/jpeg', 4096, '/tmp/phpjpg1', UPLOAD_ERR_OK), ['nestedList', 'level2', 1]],
            [new UploadedFile('', '', 0, '', UPLOAD_ERR_NO_FILE), ['nestedList', 'level2', 2]],
            [new UploadedFile('nestedDictionary0.jpg', 'image/png', 256, '/tmp/phppng0', UPLOAD_ERR_OK), ['nestedDictionary', 'level2', 'file0']],
            [new UploadedFile('nestedDictionary1.jpg', 'image/png', 4096, '/tmp/phppngg1', UPLOAD_ERR_OK), ['nestedDictionary', 'level2', 'file1']]
        ];
    }

    // -------------------------------------------------------------------------
    // Parsed Body

    /**
     * @dataProvider formContentTypeProvider
     * @param string $contentType
     */
    public function testProvidesParsedBodyForForms(string $contentType): void
    {
        $_SERVER['HTTP_CONTENT_TYPE'] = $contentType;
        $_POST = [
            'dog' => 'Bear'
        ];
        $request = $this->marshaller->getServerRequest();
        $this->assertEquals('Bear', $request->getParsedBody()['dog']);
    }

    public function formContentTypeProvider(): array
    {
        return [
            ['application/x-www-form-urlencoded'],
            ['multipart/form-data']
        ];
    }
}
