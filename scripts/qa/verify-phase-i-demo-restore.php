<?php

declare(strict_types=1);

$tempRoot = realpath(sys_get_temp_dir());
$demoRoot = realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'sim-pbri-phase-i-demo');

if ($tempRoot === false || $demoRoot === false || ! str_starts_with(strtolower($demoRoot.DIRECTORY_SEPARATOR), strtolower($tempRoot.DIRECTORY_SEPARATOR))) {
    fwrite(STDERR, "Refusing a demo path outside the current user's temporary directory.\n");
    exit(1);
}

$sourcePath = $demoRoot.DIRECTORY_SEPARATOR.'sim-pbri-phase-i-demo.sqlite';
$backupPath = $demoRoot.DIRECTORY_SEPARATOR.'restore-check-backup.sqlite';
$restoredPath = $demoRoot.DIRECTORY_SEPARATOR.'restore-check-restored.sqlite';

if (! is_file($sourcePath) || filesize($sourcePath) === 0) {
    fwrite(STDERR, "The isolated demo SQLite database is missing or empty. Run phase-i-demo.ps1 Setup first.\n");
    exit(1);
}

if (file_exists($backupPath) || file_exists($restoredPath)) {
    fwrite(STDERR, "Restore-check output already exists; refusing to overwrite it.\n");
    exit(1);
}

$sqlite = static function (string $path): PDO {
    $pdo = new PDO('sqlite:'.$path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;
};

$tableCounts = static function (PDO $pdo): array {
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $counts = [];

    foreach ($tables as $table) {
        $quotedTable = '"'.str_replace('"', '""', $table).'"';
        $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM {$quotedTable}")->fetchColumn();
    }

    return $counts;
};

$source = $sqlite($sourcePath);
$backup = null;
$restored = null;
$exitCode = 0;

try {
    if ($source->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
        throw new RuntimeException('Source database integrity check failed.');
    }

    $sourceCounts = $tableCounts($source);
    $source->exec('VACUUM INTO '.$source->quote($backupPath));
    if (! copy($backupPath, $restoredPath)) {
        throw new RuntimeException('Could not create the isolated restore copy.');
    }

    $backup = $sqlite($backupPath);
    $restored = $sqlite($restoredPath);

    foreach (['Backup' => $backup, 'Restored copy' => $restored] as $label => $database) {
        if ($database->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            throw new RuntimeException("{$label} database integrity check failed.");
        }
        if ($tableCounts($database) !== $sourceCounts) {
            throw new RuntimeException("{$label} table row counts do not match the source database.");
        }
    }

    fwrite(STDOUT, 'PASS: isolated SQLite backup and restore integrity verified; '.count($sourceCounts)." table row counts match. Source data was not modified.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'FAIL: '.$exception->getMessage()."\n");
    $exitCode = 1;
} finally {
    $database = null;
    $restored = null;
    $backup = null;
    $source = null;
    if (is_file($backupPath)) {
        unlink($backupPath);
    }
    if (is_file($restoredPath)) {
        unlink($restoredPath);
    }
}

exit($exitCode);
