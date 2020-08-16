<?php

namespace WellRESTed\Message;

use InvalidArgumentException;
use WellRESTed\Test\TestCase;

class ServerRequestTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Server Params

    public function testGetServerParamsReturnsEmptyArrayByDefault(): void
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getServerParams());
    }

    // -------------------------------------------------------------------------
    // Cookies

    public function testGetCookieParamsReturnsEmptyArrayByDefault(): void
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getCookieParams());
    }

    public function testWithCookieParamsCreatesNewInstanceWithCookies(): void
    {
        $cookies = [
            'cat' => 'Oscar'
        ];

        $request1 = new ServerRequest();
        $request2 = $request1->withCookieParams($cookies);

        $this->assertEquals($cookies, $request2->getCookieParams());
        $this->assertNotSame($request2, $request1);
    }

    // -------------------------------------------------------------------------
    // Query

    public function testGetQueryParamsReturnsEmptyArrayByDefault(): void
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getQueryParams());
    }

    public function testWithQueryParamsCreatesNewInstance(): void
    {
        $query = [
            'cat' => 'Aggie'
        ];

        $request1 = new ServerRequest();
        $request2 = $request1->withQueryParams($query);

        $this->assertEquals($query, $request2->getQueryParams());
        $this->assertNotSame($request2, $request1);
    }

    // -------------------------------------------------------------------------
    // Uploaded Files

    public function testGetUploadedFilesReturnsEmptyArrayByDefault(): void
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getUploadedFiles());
    }

    public function testWithUploadedFilesCreatesNewInstance(): void
    {
        $uploadedFiles = [
            'file' => new UploadedFile('index.html', 'text/html', 524, '/tmp/php9hNlHe', 0)
        ];
        $request = new ServerRequest();
        $request1 = $request->withUploadedFiles([]);
        $request2 = $request1->withUploadedFiles($uploadedFiles);
        $this->assertNotSame($request2, $request1);
    }

    /**
     * @dataProvider validUploadedFilesProvider
     * @param array $uploadedFiles
     */
    public function testWithUploadedFilesStoresPassedUploadedFiles(array $uploadedFiles): void
    {
        $request = new ServerRequest();
        $request = $request->withUploadedFiles($uploadedFiles);
        $this->assertSame($uploadedFiles, $request->getUploadedFiles());
    }

    public function validUploadedFilesProvider(): array
    {
        return [
            [[]],
            [['files' => new UploadedFile('index.html', 'text/html', 524, '/tmp/php9hNlHe', 0)]],
            [['nested' => [
                'level2' => new UploadedFile('index.html', 'text/html', 524, '/tmp/php9hNlHe', 0)
            ]]],
            [['nestedList' => [
                'level2' => [
                    new UploadedFile('file1.html', 'text/html', 524, '/tmp/php9hNlHe', 0),
                    new UploadedFile('file2.html', 'text/html', 524, '/tmp/php9hNshj', 0)
                ]
            ]]],
            [['nestedDictionary' => [
                'level2' => [
                    'file1' => new UploadedFile('file1.html', 'text/html', 524, '/tmp/php9hNlHe', 0),
                    'file2' => new UploadedFile('file2.html', 'text/html', 524, '/tmp/php9hNshj', 0)
                ]
            ]]]
        ];
    }

    /**
     * @dataProvider invalidUploadedFilesProvider
     * @param array $uploadedFiles
     */
    public function testWithUploadedFilesThrowsExceptionWithInvalidTree(array $uploadedFiles): void
    {
        $this->expectException(InvalidArgumentException::class);
        $request = new ServerRequest();
        $request->withUploadedFiles($uploadedFiles);
    }

    public function invalidUploadedFilesProvider()
    {
        return [
            // All keys must be strings
            [[new UploadedFile('index.html', 'text/html', 524, '/tmp/php9hNlHe', 0)]],
            [
                [new UploadedFile('index1.html', 'text/html', 524, '/tmp/php9hNlHe', 0)],
                [new UploadedFile('index2.html', 'text/html', 524, '/tmp/php9hNlHe', 0)]
            ],
            [
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
                ]
            ],
            [
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
                ]
            ]
        ];
    }

    // -------------------------------------------------------------------------
    // Parsed Body

    public function testGetParsedBodyReturnsNullByDefault(): void
    {
        $request = new ServerRequest();
        $this->assertNull($request->getParsedBody());
    }

    public function testWithParsedBodyCreatesNewInstance(): void
    {
        $body = [
            'guinea_pig' => 'Clyde'
        ];

        $request1 = new ServerRequest();
        $request2 = $request1->withParsedBody($body);

        $this->assertEquals($body, $request2->getParsedBody());
        $this->assertNotSame($request2, $request1);
    }

    /**
     * @dataProvider invalidParsedBodyProvider
     * @param mixed $body
     */
    public function testWithParsedBodyThrowsExceptionWithInvalidType($body): void
    {
        $this->expectException(InvalidArgumentException::class);
        $request = new ServerRequest();
        $request->withParsedBody($body);
    }

    public function invalidParsedBodyProvider()
    {
        return [
            [false],
            [1]
        ];
    }

    public function testCloneMakesDeepCopiesOfParsedBody(): void
    {
        $body = (object) [
            'cat' => 'Dog'
        ];

        $request1 = new ServerRequest();
        $request1 = $request1->withParsedBody($body);
        $request2 = $request1->withHeader('X-extra', 'hello world');

        $this->assertTrue(
            $request1->getParsedBody() == $request2->getParsedBody()
            && $request1->getParsedBody() !== $request2->getParsedBody()
        );
    }

    // -------------------------------------------------------------------------
    // Attributes

    public function testGetAttributesReturnsEmptyArrayByDefault(): void
    {
        $request = new ServerRequest();
        $this->assertEquals([], $request->getAttributes());
    }

    public function testGetAttributesReturnsAllAttributes(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('cat', 'Molly');
        $request = $request->withAttribute('dog', 'Bear');
        $expected = [
            'cat' => 'Molly',
            'dog' => 'Bear'
        ];
        $this->assertEquals($expected, $request->getAttributes());
    }

    public function testGetAttributeReturnsDefaultIfNotSet(): void
    {
        $request = new ServerRequest();
        $this->assertEquals('Oscar', $request->getAttribute('cat', 'Oscar'));
    }

    public function testWithAttributeCreatesNewInstance(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('cat', 'Molly');
        $this->assertEquals('Molly', $request->getAttribute('cat'));
    }

    public function testWithAttributePreserversOtherAttributes(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('cat', 'Molly');
        $request = $request->withAttribute('dog', 'Bear');
        $expected = [
            'cat' => 'Molly',
            'dog' => 'Bear'
        ];
        $this->assertEquals($expected, $request->getAttributes());
    }

    public function testWithoutAttributeCreatesNewInstance(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('cat', 'Molly');
        $this->assertNotEquals($request, $request->withoutAttribute('cat'));
    }

    public function testWithoutAttributeRemovesAttribute(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('cat', 'Molly');
        $request = $request->withoutAttribute('cat');
        $this->assertEquals('Oscar', $request->getAttribute('cat', 'Oscar'));
    }

    public function testWithoutAttributePreservesOtherAttributes(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('cat', 'Molly');
        $request = $request->withAttribute('dog', 'Bear');
        $request = $request->withoutAttribute('cat');
        $this->assertEquals('Bear', $request->getAttribute('dog'));
    }
}
