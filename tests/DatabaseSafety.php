<?php

namespace Tests;

use RuntimeException;

final class DatabaseSafety
{
    public static function assertIsolated(array $database): void
    {
        $connection = $database['connections'][$database['default'] ?? ''] ?? [];
        if (($connection['driver'] ?? null) !== 'sqlite'
            || ($connection['database'] ?? null) !== ':memory:'
            || ! empty($connection['url'])) {
            throw new RuntimeException('Tests require SQLite :memory: before database refresh. Check PHPUnit configuration and cached configuration.');
        }
    }
}
