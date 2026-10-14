<?php

/** Local MySQL regression. Only the generated disposable schema may be mutated. */
use App\Core\Enums\UserRole;
use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Actions\CreateBookingAction;
use App\Modules\Booking\Http\Controllers\BookingController;
use App\Modules\Booking\Http\Requests\StoreBookingRequest;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingDraft;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

$worker = ($argv[1] ?? '') === '--worker';
$repository = dirname(__DIR__, 2);
if (is_file($repository.'/bootstrap/cache/config.php')) {
    throw new RuntimeException('Cached config is not permitted for this isolated check.');
}
require $repository.'/vendor/autoload.php';
$app = require $repository.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$config = config('database.connections.'.config('database.default'));
if (($config['driver'] ?? '') !== 'mysql' || ! in_array($config['host'] ?? '', ['localhost', '127.0.0.1', '::1'], true) || ! empty($config['url'])) {
    throw new RuntimeException('Only local MySQL without URL override is permitted.');
}
$database = $worker ? $argv[2] : 'sim_pbri_i4_qa_'.bin2hex(random_bytes(6));
if (! preg_match('/\Asim_pbri_i4_qa_[a-f0-9]{12}\z/', $database) || $database === $config['database']) {
    throw new RuntimeException('Unsafe schema identity.');
}
$bootstrap = DB::connection();
$created = false;
$exit = 1;
try {
    if (! $worker) {
        $bootstrap->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $created = true;
    }
    $config['database'] = $database;
    $config['url'] = null;
    config(['database.connections.i4_qa' => $config, 'database.default' => 'i4_qa', 'cache.default' => 'array', 'session.driver' => 'array', 'queue.default' => 'sync', 'mail.default' => 'array']);
    $connection = DB::connection('i4_qa');
    if ($connection->selectOne('SELECT DATABASE() AS name')->name !== $database) {
        throw new RuntimeException('Effective connection identity mismatch.');
    }
    $connection->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $connection->statement('SET SESSION innodb_lock_wait_timeout = 12');
    $isolation = $connection->selectOne('SELECT @@tx_isolation AS isolation_level')->isolation_level;
    if ($isolation !== 'REPEATABLE-READ') {
        throw new RuntimeException('Unexpected effective isolation.');
    }
    if ($worker) {
        [$mode, $actorId, $roomId, $equipmentId, $assetId, $scenarioId, $draftId, $signal] = array_slice($argv, 3);
        $actor = User::findOrFail((int) $actorId);
        $data = ['resources' => [['id' => (int) $roomId, 'quantity' => 1]], 'starts_at' => '2030-01-10 09:00:00', 'ends_at' => '2030-01-10 11:00:00', 'participant_count' => 1, 'requester_phone' => '0812345678', 'draft_id' => (int) $draftId];
        if ($mode !== 'scenario_null') {
            $data['scenario_id'] = (int) $scenarioId;
        }
        if ($mode === 'equipment') {
            $data['resources'][] = ['id' => (int) $equipmentId, 'quantity' => 2];
        }
        if ($mode === 'simulator') {
            $data['simulator_asset_id'] = (int) $assetId;
        }
        $request = StoreBookingRequest::create('/app/bookings', 'POST', $data);
        $request->setContainer($app)->setRedirector($app['redirect']);
        $request->setUserResolver(fn () => $actor);
        $request->validateResolved();
        $signaled = false;
        $plainScenarioBeforeLock = false;
        $connection->beforeExecuting(function (string $sql) use ($connection, $signal, $mode, &$signaled, &$plainScenarioBeforeLock): void {
            $sql = strtolower($sql);
            if (! $signaled && $connection->transactionLevel() > 0 && str_contains($sql, 'from `scenarios`') && ! str_contains($sql, 'for update')) {
                $plainScenarioBeforeLock = true;
            }
            $target = $mode === 'simulator' ? 'simulator_assets' : 'sim_resources';
            if (! $signaled && str_contains($sql, 'for update') && str_contains($sql, $target)) {
                file_put_contents($signal, json_encode(['barrier' => 'inventory-lock-statement', 'transaction_level' => $connection->transactionLevel(), 'plain_scenario_before_lock' => $plainScenarioBeforeLock], JSON_THROW_ON_ERROR));
                $signaled = true;
            }
        });
        try {
            $app->make(BookingController::class)->store($request, $app->make(CreateBookingAction::class));
            $result = ['accepted' => true];
        } catch (ValidationException $error) {
            $result = ['accepted' => false, 'validation_fields' => array_keys($error->errors())];
        }
        echo json_encode([...$result, 'plain_scenario_before_lock' => $plainScenarioBeforeLock, 'outer_controller' => true], JSON_THROW_ON_ERROR);
        exit(0);
    }
    if (Artisan::call('migrate', ['--database' => 'i4_qa', '--force' => true]) !== 0) {
        throw new RuntimeException('Disposable migration failed.');
    }
    $results = [];
    foreach (['room', 'equipment', 'simulator', 'scenario_null', 'touching', 'success'] as $mode) {
        $college = College::factory()->create();
        $actor = User::factory()->create(['college_id' => $college->id, 'role' => UserRole::Lecturer]);
        $competitor = User::factory()->create(['college_id' => $college->id, 'role' => UserRole::Lecturer]);
        $course = Course::create(['college_id' => $college->id, 'name' => 'Fictional concurrency course']);
        $scenario = Scenario::create(['course_id' => $course->id, 'name' => 'Fictional concurrency scenario']);
        $room = SimResource::create(['college_id' => $college->id, 'name' => 'Fictional room A', 'kind' => 'room', 'status' => 'ready', 'quantity_total' => 1, 'is_exclusive' => true, 'capacity' => 4]);
        $roomB = SimResource::create(['college_id' => $college->id, 'name' => 'Fictional room B', 'kind' => 'room', 'status' => 'ready', 'quantity_total' => 1, 'is_exclusive' => true, 'capacity' => 4]);
        $equipment = SimResource::create(['college_id' => $college->id, 'name' => 'Fictional equipment', 'kind' => 'equipment', 'status' => 'ready', 'quantity_total' => 3, 'is_exclusive' => false]);
        $scenario->recommendedResources()->attach($equipment->id, ['quantity' => 2]);
        $type = SimulatorType::create(['college_id' => $college->id, 'name' => 'Fictional type']);
        $asset = SimulatorAsset::create(['college_id' => $college->id, 'simulator_type_id' => $type->id, 'asset_name' => 'Fictional simulator']);
        $draft = BookingDraft::create(['college_id' => $college->id, 'user_id' => $actor->id, 'payload' => ['note' => 'Preserve on failed submission']]);
        $signal = tempnam(sys_get_temp_dir(), 'sim-pbri-i4-sync-');
        $process = null;
        $pipes = [];
        try {
            $connection->beginTransaction();
            if ($mode === 'simulator') {
                SimulatorAsset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            } else {
                SimResource::whereKey($mode === 'equipment' ? $equipment->id : $room->id)->lockForUpdate()->firstOrFail();
            }
            $process = proc_open([PHP_BINARY, __FILE__, '--worker', $database, $mode, (string) $actor->id, (string) $room->id, (string) $equipment->id, (string) $asset->id, (string) $scenario->id, (string) $draft->id, $signal], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $repository, null, ['bypass_shell' => true]);
            if (! is_resource($process)) {
                throw new RuntimeException('Worker start failed.');
            }
            fclose($pipes[0]);
            $deadline = microtime(true) + 10;
            do {
                clearstatcache(true, $signal);
                if (filesize($signal) > 0) {
                    break;
                }
                if (! proc_get_status($process)['running'] || microtime(true) >= $deadline) {
                    throw new RuntimeException('Worker failed to reach lock barrier.');
                }
                usleep(25000);
            } while (true);
            $barrier = json_decode(file_get_contents($signal), true, 512, JSON_THROW_ON_ERROR);
            if ($barrier['transaction_level'] < 1) {
                throw new RuntimeException('Controller transaction absent.');
            }
            if ($mode !== 'success') {
                $competing = Booking::create(['college_id' => $college->id, 'requested_by_user_id' => $competitor->id, 'requester_name' => 'Fictional competitor', 'scenario_id' => $scenario->id, 'simulator_asset_id' => $mode === 'simulator' ? $asset->id : null, 'starts_at' => $mode === 'touching' ? '2030-01-10 07:00:00' : '2030-01-10 09:00:00', 'ends_at' => $mode === 'touching' ? '2030-01-10 09:00:00' : '2030-01-10 11:00:00', 'participant_count' => 1, 'requester_phone' => '0812345678', 'status' => 'pending']);
                $competing->resources()->attach(in_array($mode, ['equipment', 'simulator'], true) ? $roomB->id : $room->id, ['quantity' => 1]);
                if ($mode === 'equipment') {
                    $competing->resources()->attach($equipment->id, ['quantity' => 2]);
                }
            }
            $connection->commit();
            stream_set_timeout($pipes[1], 15);
            $output = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $workerExit = proc_close($process);
            $process = null;
            if ($workerExit !== 0 || $stderr !== '') {
                throw new RuntimeException('Worker failed: '.$stderr);
            }
            $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            $own = Booking::where('requested_by_user_id', $actor->id)->count();
            $draftExists = BookingDraft::whereKey($draft->id)->exists();
            $audit = DB::table('booking_status_transitions')->where('actor_user_id', $actor->id)->count();
            $expected = in_array($mode, ['touching', 'success'], true);
            $passed = $result['accepted'] === $expected && $own === ($expected ? 1 : 0) && $draftExists === ! $expected && $audit === ($expected ? 1 : 0);
            $results[] = ['mode' => $mode, 'barrier' => $barrier, 'worker' => $result, 'own_bookings' => $own, 'draft_preserved' => $draftExists, 'own_audit_rows' => $audit, 'passed' => $passed];
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
                } proc_close($process);
            }
            unlink($signal);
        }
    }
    echo json_encode(['effective_isolation' => $isolation, 'disposable_schema' => $database, 'controller_path' => 'BookingController::store', 'results' => $results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
    $exit = collect($results)->every(fn ($result) => $result['passed']) ? 0 : 1;
} catch (Throwable $error) {
    fwrite(STDERR, 'Check failed: '.$error::class.' '.$error->getMessage().PHP_EOL);
} finally {
    if (! $worker && $created) {
        DB::purge('i4_qa');
        $bootstrap->statement('DROP DATABASE `'.$database.'`');
        echo "Disposable schema removed.\n";
    }
}
exit($exit);
