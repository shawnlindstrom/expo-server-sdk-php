<?php

namespace ExpoSDK\Tests;

use ExpoSDK\Exceptions\UnableToReadFileException;
use ExpoSDK\Exceptions\UnableToWriteFileException;
use ExpoSDK\File;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

class FileTest extends TestCase
{
    private $path = TEST_DIR . '/storage/expo.json';
    private $txtPath = TEST_DIR . '/storage/expo.txt';
    private $testPath = TEST_DIR . '/storage/test.json';
    private $file;

    protected function setUp(): void
    {
        // so file can be populated with empty object
        file_put_contents($this->path, "");

        $this->file = new File($this->path);

        $this->file->empty();
    }

    protected function tearDown(): void
    {
        $this->file->empty();

        @unlink($this->testPath);

        @unlink($this->txtPath);
    }

    #[Test]
    public function read_returns_empty_object_when_file_is_empty_or_whitespace()
    {
        file_put_contents($this->testPath, "   \n\t  ");

        $file = new File($this->testPath);

        $data = $file->read();

        $this->assertInstanceOf(stdClass::class, $data);
        $this->assertSame([], get_object_vars($data));
    }

    #[Test]
    public function validate_contents_replaces_non_object_json_with_object()
    {
        file_put_contents($this->testPath, "[]");

        $file = new File($this->testPath);

        $data = $file->read();

        $this->assertInstanceOf(stdClass::class, $data);
        $this->assertSame([], get_object_vars($data));
    }

    #[Test]
    public function throws_exception_if_json_is_invalid()
    {
        file_put_contents($this->testPath, "{");

        $this->expectException(UnableToReadFileException::class);

        new File($this->testPath);
    }

    #[Test]
    public function can_write_and_then_read_data_successfully()
    {
        file_put_contents($this->testPath, "{}");

        $file = new File($this->testPath);

        $obj = new stdClass();
        $obj->foo = 'bar';

        $this->assertTrue($file->write($obj));

        $data = $file->read();

        $this->assertInstanceOf(stdClass::class, $data);
        $this->assertSame('bar', $data->foo);
    }

    #[Test]
    public function write_throws_json_exception_when_encoding_fails()
    {
        file_put_contents($this->testPath, "{}");

        $file = new File($this->testPath);

        $obj = new stdClass();
        $obj->bad = "\xB1\x31";

        $this->expectException(JsonException::class);

        $file->write($obj);
    }

    #[Test]
    public function file_class_instantiates()
    {
        $file = new File($this->path);

        $this->assertInstanceOf(File::class, $file);
    }

    #[Test]
    public function throws_exception_for_non_json_file()
    {
        $file = fopen($this->txtPath, "w");
        fclose($file);

        $this->expectExceptionMessage(
            'The storage file must have a .json extension.'
        );

        new File($this->txtPath);
    }

    #[Test]
    public function throws_exception_if_unable_to_read_file()
    {
        $file = fopen($this->testPath, "w");
        fclose($file);
        $file = new File($this->testPath);
        @unlink($this->testPath);

        $this->expectException(UnableToReadFileException::class);

        $file->read();
    }

    #[Test]
    public function throws_exception_if_unable_to_write_file()
    {
        $file = fopen($this->testPath, "w");
        fclose($file);
        $file = new File($this->testPath);
        @unlink($this->testPath);

        $this->expectException(UnableToWriteFileException::class);

        $file->write(new stdClass());
    }

    #[Test]
    public function throws_exception_for_non_existent_file_path()
    {
        $this->expectException(\ExpoSDK\Exceptions\FileDoesntExistException::class);
        $this->expectExceptionMessage('does not exist');

        new File('/path/that/does/not/exist.json');
    }

    #[Test]
    public function throws_exception_when_file_contents_cannot_be_read_during_validation()
    {
        // Create a directory with the same name to simulate file_get_contents failure
        @mkdir($this->testPath);

        try {
            $this->expectException(UnableToReadFileException::class);
            new File($this->testPath);
        } finally {
            @rmdir($this->testPath);
        }
    }

    #[Test]
    public function throws_exception_when_file_contents_cannot_be_read()
    {
        file_put_contents($this->testPath, "{}");
        $file = new File($this->testPath);
        @unlink($this->testPath);
        @mkdir($this->testPath);

        try {
            $this->expectException(UnableToReadFileException::class);
            $file->read();
        } finally {
            @rmdir($this->testPath);
        }
    }

    #[Test]
    public function throws_exception_when_read_encounters_invalid_json()
    {
        file_put_contents($this->testPath, "{}");
        $file = new File($this->testPath);

        file_put_contents($this->testPath, "{invalid");

        $this->expectException(UnableToReadFileException::class);

        $file->read();
    }
}
