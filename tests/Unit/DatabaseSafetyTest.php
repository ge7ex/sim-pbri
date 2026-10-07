<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\DatabaseSafety;

final class DatabaseSafetyTest extends TestCase
{
    public function test_only_memory_sqlite_is_accepted(): void
    {
        DatabaseSafety::assertIsolated(['default' => 'sqlite', 'connections' => ['sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'url' => null]]]);
        $this->addToAssertionCount(1);
    }

    public function test_runtime_mysql_file_sqlite_and_url_overrides_are_rejected(): void
    {
        foreach ([['driver' => 'mysql', 'database' => 'runtime'], ['driver' => 'sqlite', 'database' => '/tmp/runtime.sqlite'], ['driver' => 'sqlite', 'database' => ':memory:', 'url' => 'mysql://example']] as $connection) {
            try {
                DatabaseSafety::assertIsolated(['default' => 'test', 'connections' => ['test' => $connection]]);
                $this->fail('Unsafe connection was accepted');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('Tests require SQLite :memory:', $exception->getMessage());
            }
        }
    }
}
