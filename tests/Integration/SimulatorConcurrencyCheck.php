<?php

/**
 * Explicit local MySQL integration check, outside the SQLite PHPUnit suite.
 * Runs real booking actions against a generated, disposable schema only.
 * Usage: php tests/Integration/SimulatorConcurrencyCheck.php
 */
use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Actions\CreateBookingAction;
use App\Modules\Booking\Actions\RecallBookingAction;
use App\Modules\Booking\Models\Booking;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

$worker = ($argv[1] ?? '') === '--worker';
$repository = $worker ? $argv[2] : ($argv[1] ?? dirname(__DIR__, 2));
require $repository.'/vendor/autoload.php';
$app = require $repository.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$originalConnection = config('database.default');
$connectionConfig = config('database.connections.'.$originalConnection);
if (($connectionConfig['driver'] ?? '') !== 'mysql'
    || ! in_array($connectionConfig['host'] ?? '', ['localhost', '127.0.0.1', '::1'], true)
    || ! empty($connectionConfig['url'])) {
    fwrite(STDERR, "Check requires a configured local MySQL connection without a URL override.\n");
    exit(2);
}
$database = $worker ? $argv[3] : 'sim_pbri_phasef_qa_'.bin2hex(random_bytes(6));
if (! preg_match('/\Asim_pbri_phasef_qa_[a-f0-9]{12}\z/', $database)
    || $database === ($connectionConfig['database'] ?? null)) {
    throw new RuntimeException('Unsafe test database identity');
}

$created = false;
$exitCode = 1;
$bootstrapConnection = DB::connection($originalConnection);
try {
    if (! $worker) {
        $bootstrapConnection->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $created = true;
    }
    $connectionConfig['database'] = $database;
    $connectionConfig['url'] = null;
    config(['database.connections.phase_f_qa' => $connectionConfig, 'database.default' => 'phase_f_qa']);
    DB::purge('phase_f_qa');
    $connection = DB::connection('phase_f_qa');
    $connection->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $connection->statement('SET SESSION innodb_lock_wait_timeout = 15');

    if ($worker) {
        [$flow, $userId, $assetId, $roomId, $bookingId, $signal] = array_slice($argv, 4);
        $signaled = false;
        $connection->beforeExecuting(function (string $sql) use ($signal, &$signaled): void {
            $sql = strtolower($sql);
            if (! $signaled && str_contains($sql, 'for update')
                && (str_contains($sql, 'simulator_assets') || str_contains($sql, 'simulator_types'))) {
                file_put_contents($signal, 'shared-inventory-lock');
                $signaled = true;
            }
        });
        $actor = User::findOrFail((int) $userId);
        try {
            if ($flow === 'create') {
                $app->make(CreateBookingAction::class)->execute([
                    'resources' => [['id' => (int) $roomId, 'quantity' => 1]],
                    'simulator_asset_id' => (int) $assetId,
                    'starts_at' => '2030-01-10 09:00:00', 'ends_at' => '2030-01-10 11:00:00',
                    'participant_count' => 1, 'requester_phone' => '0812345678',
                ], $actor);
            } elseif ($flow === 'recall') {
                $app->make(RecallBookingAction::class)->execute(Booking::findOrFail((int) $bookingId), $actor, 'Concurrency regression');
            } else {
                throw new RuntimeException('Unknown check flow');
            }
            echo json_encode(['accepted' => true], JSON_THROW_ON_ERROR);
        } catch (ValidationException $error) {
            echo json_encode(['accepted' => false, 'validation_fields' => array_keys($error->errors())], JSON_THROW_ON_ERROR);
        }
        exit(0);
    }

    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Test schema migration failed');
    }
    $results = [];
    foreach (['create', 'recall'] as $flow) {
        $college = College::factory()->create();
        $lecturer = User::factory()->create(['college_id' => $college->id, 'role' => UserRole::Lecturer]);
        $staff = User::factory()->create(['college_id' => $college->id, 'role' => UserRole::Staff]);
        $type = SimulatorType::create(['college_id' => $college->id, 'name' => 'Concurrency type']);
        $asset = SimulatorAsset::create(['college_id' => $college->id, 'simulator_type_id' => $type->id, 'asset_name' => 'Concurrency asset']);
        $rooms = [];
        foreach (['A', 'B'] as $name) {
            $rooms[] = SimResource::create(['college_id' => $college->id, 'name' => $name, 'kind' => 'room', 'status' => 'ready', 'quantity_total' => 1, 'is_exclusive' => true, 'capacity' => 1]);
        }
        $base = ['college_id' => $college->id, 'requested_by_user_id' => $lecturer->id, 'requester_name' => $lecturer->name,
            'simulator_asset_id' => $asset->id, 'starts_at' => '2030-01-10 09:00:00', 'ends_at' => '2030-01-10 11:00:00', 'participant_count' => 1, 'requester_phone' => '0812345678'];
        $recalled = null;
        if ($flow === 'recall') {
            $recalled = Booking::create([...$base, 'status' => 'rejected']);
            $recalled->resources()->attach($rooms[0]->id, ['quantity' => 1]);
        }
        $signal = tempnam(sys_get_temp_dir(), 'sim-pbri-phasef-sync-');
        $process = null;
        $pipes = [];
        try {
            $connection->beginTransaction();
            // A competing reservation holds shared inventory before the tested writer starts.
            SimulatorType::whereKey($type->id)->lockForUpdate()->firstOrFail();
            SimulatorAsset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $process = proc_open([PHP_BINARY, __FILE__, '--worker', $repository, $database, $flow,
                (string) ($flow === 'create' ? $lecturer->id : $staff->id), (string) $asset->id,
                (string) $rooms[0]->id, (string) ($recalled?->id ?? 0), $signal],
                [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $repository, null, ['bypass_shell' => true]);
            if (! is_resource($process)) {
                throw new RuntimeException('Worker failed to start');
            }
            fclose($pipes[0]);
            $deadline = microtime(true) + 10;
            do {
                clearstatcache(true, $signal);
                if (filesize($signal) > 0) {
                    break;
                }
                if (! proc_get_status($process)['running'] || microtime(true) >= $deadline) {
                    throw new RuntimeException('Worker did not reach the inventory lock');
                }
                usleep(25000);
            } while (true);
            $competing = Booking::create([...$base, 'status' => 'pending']);
            $competing->resources()->attach($rooms[1]->id, ['quantity' => 1]);
            $connection->commit();
            $output = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $workerExit = proc_close($process);
            $process = null;
            if ($workerExit !== 0 || $stderr !== '') {
                throw new RuntimeException('Worker execution failed');
            }
            $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            $pending = Booking::where('simulator_asset_id', $asset->id)->whereIn('status', ['pending', 'approved'])->count();
            $passed = $result['accepted'] === false && $pending === 1
                && ($recalled === null || $recalled->fresh()->status->value === 'rejected');
            $results[] = ['flow' => $flow, 'competing_booking_committed' => true, 'tested_writer' => $result, 'blocking_bookings' => $pending, 'passed' => $passed];
        } finally {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            if (is_resource($process)) {
                proc_terminate($process);
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
                proc_close($process);
            }
            unlink($signal);
        }
    }
    echo json_encode(['driver' => 'mysql', 'isolation' => 'REPEATABLE READ', 'results' => $results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
    $exitCode = collect($results)->every(fn ($result) => $result['passed']) ? 0 : 1;
} catch (Throwable $error) {
    fwrite(STDERR, 'Check failed: '.$error::class.PHP_EOL);
} finally {
    if (! $worker && $created) {
        DB::purge('phase_f_qa');
        $bootstrapConnection->statement('DROP DATABASE `'.$database.'`');
        echo "Disposable integration database removed.\n";
    }
}
exit($exitCode);
