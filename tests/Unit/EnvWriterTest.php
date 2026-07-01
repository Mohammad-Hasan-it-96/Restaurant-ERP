<?php

namespace Tests\Unit;

use App\Support\EnvWriter;
use PHPUnit\Framework\TestCase;

class EnvWriterTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($this->tmp, "APP_NAME=Old\nDB_HOST=127.0.0.1\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->tmp);
        parent::tearDown();
    }

    public function test_updates_existing_and_adds_new_keys(): void
    {
        (new EnvWriter($this->tmp))->write([
            'APP_NAME' => 'NewName',
            'APP_URL' => 'https://example.com',
        ]);

        $contents = file_get_contents($this->tmp);
        $this->assertStringContainsString('APP_NAME=NewName', $contents);
        $this->assertStringContainsString('DB_HOST=127.0.0.1', $contents); // preserved
        $this->assertStringContainsString('APP_URL=https://example.com', $contents); // appended
    }

    public function test_quotes_values_with_spaces(): void
    {
        (new EnvWriter($this->tmp))->write(['APP_NAME' => 'My Restaurant']);

        $this->assertStringContainsString('APP_NAME="My Restaurant"', file_get_contents($this->tmp));
    }

    public function test_does_not_duplicate_keys_on_repeat_writes(): void
    {
        $writer = new EnvWriter($this->tmp);
        $writer->write(['APP_NAME' => 'A']);
        $writer->write(['APP_NAME' => 'B']);

        $contents = file_get_contents($this->tmp);
        $this->assertSame(1, substr_count($contents, 'APP_NAME='));
        $this->assertStringContainsString('APP_NAME=B', $contents);
    }
}
