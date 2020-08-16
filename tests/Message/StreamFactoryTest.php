<?php

namespace WellRESTed\Message;

use RuntimeException;
use WellRESTed\Test\TestCase;

class StreamFactoryTest extends TestCase
{
    private const CONTENT = 'Stream content';

    /** @var string $tempPath */
    private $tempPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempPath = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($this->tempPath, self::CONTENT);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unlink($this->tempPath);
    }

    // -------------------------------------------------------------------------

    public function testCreatesStreamFromString(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream(self::CONTENT);

        $this->assertEquals(self::CONTENT, (string) $stream);
    }

    public function testCreatesStreamFromFile(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStreamFromFile($this->tempPath);

        $this->assertEquals(self::CONTENT, (string) $stream);
    }

    public function testCreatesStreamFromFileWithModeRByDefault(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStreamFromFile($this->tempPath);

        $mode = $stream->getMetadata('mode');
        $this->assertEquals('r', $mode);
    }

    /**
     * @dataProvider modeProvider
     * @param string $mode
     */
    public function testCreatesStreamFromFileWithPassedMode(string $mode): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStreamFromFile($this->tempPath, $mode);

        $actual = $stream->getMetadata('mode');
        $this->assertEquals($mode, $actual);
    }

    public function modeProvider(): array
    {
        return [
            ['r'],
            ['r+'],
            ['w'],
            ['w+']
        ];
    }

    public function testCreateStreamFromFileThrowsRuntimeExceptionWhenUnableToOpenFile(): void
    {
        $this->expectException(RuntimeException::class);

        $factory = new StreamFactory();
        @$factory->createStreamFromFile('/dev/null/not-a-file', 'w');
    }

    public function testCreatesStreamFromResource(): void
    {
        $f = fopen($this->tempPath, 'r');

        $factory = new StreamFactory();
        $stream = $factory->createStreamFromResource($f);

        $this->assertEquals(self::CONTENT, (string) $stream);
    }
}
