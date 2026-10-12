param(
    [ValidateSet('Setup', 'Cleanup')]
    [string] $Action = 'Setup'
)

$ErrorActionPreference = 'Stop'
$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$tempRoot = [IO.Path]::GetFullPath($env:TEMP).TrimEnd('\') + '\'
$demoRoot = [IO.Path]::GetFullPath((Join-Path $env:TEMP 'sim-pbri-phase-i-demo'))
if (-not $demoRoot.StartsWith($tempRoot, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'Demo path is outside the current user temporary directory.'
}

function Get-DemoProcessRecord($process) {
    return [pscustomobject]@{
        pid = $process.ProcessId
        name = $process.Name
        creationTicks = $process.CreationDate.ToUniversalTime().Ticks.ToString()
        commandLine = $process.CommandLine
    }
}

function Test-DemoProcessIdentity($record, $process) {
    return $null -ne $process -and
        $record.name -eq $process.Name -and
        [long]$record.creationTicks -eq $process.CreationDate.ToUniversalTime().Ticks -and
        $record.commandLine -eq $process.CommandLine
}

if ($Action -eq 'Cleanup') {
    if (-not (Test-Path -LiteralPath $demoRoot)) {
        Write-Output 'Demo environment is already absent.'
        exit 0
    }

    $processFile = Join-Path $demoRoot 'processes.json'
    if (Test-Path -LiteralPath $processFile) {
        $processes = Get-Content -Raw -LiteralPath $processFile | ConvertFrom-Json
        foreach ($entry in $processes) {
            $process = Get-CimInstance Win32_Process -Filter "ProcessId=$($entry.pid)" -ErrorAction SilentlyContinue
            if ($null -eq $process) { continue }
            if (-not (Test-DemoProcessIdentity $entry $process)) { continue }
            $isListener = $process.Name -eq 'php.exe' -and $process.CommandLine -like "*$repoRoot*" -and $process.CommandLine -like '*-S 127.0.0.1:8766*'
            $isServerParent = $process.Name -eq 'php.exe' -and $process.CommandLine -like '*artisan serve --host=127.0.0.1 --port=8766 --no-reload*'
            if ($isListener -or $isServerParent) { Stop-Process -Id $entry.pid -Force }
        }
    }

    Start-Sleep -Milliseconds 300
    if (Get-NetTCPConnection -State Listen -LocalPort 8766 -ErrorAction SilentlyContinue) {
        throw 'Port 8766 is still listening; refusing to remove demo data.'
    }

    Remove-Item -LiteralPath $demoRoot -Recurse -Force
    Write-Output 'Removed only the isolated Phase I demo directory after confirming port 8766 is closed.'
    exit 0
}

if (Test-Path -LiteralPath $demoRoot) {
    throw "Demo directory already exists. Run Cleanup first: $demoRoot"
}
if (Test-Path -LiteralPath (Join-Path $repoRoot 'bootstrap\cache\config.php')) {
    throw 'Laravel config is cached; refusing to start until the project config cache is cleared through the normal local workflow.'
}
if (Get-NetTCPConnection -State Listen -LocalPort 8766 -ErrorAction SilentlyContinue) {
    throw 'Port 8766 is already in use. Stop the existing isolated QA server before setup.'
}

New-Item -ItemType Directory -Path $demoRoot | Out-Null
$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$acl = Get-Acl -LiteralPath $demoRoot
$acl.SetAccessRuleProtection($true, $false)
$rule = [Security.AccessControl.FileSystemAccessRule]::new(
    $identity.User,
    [Security.AccessControl.FileSystemRights]::FullControl,
    [Security.AccessControl.InheritanceFlags]'ContainerInherit, ObjectInherit',
    [Security.AccessControl.PropagationFlags]::None,
    [Security.AccessControl.AccessControlType]::Allow
)
$acl.SetAccessRule($rule)
Set-Acl -LiteralPath $demoRoot -AclObject $acl

$databasePath = Join-Path $demoRoot 'sim-pbri-phase-i-demo.sqlite'
New-Item -ItemType File -Path $databasePath | Out-Null

$isolatedEnvironmentNames = @(
    'APP_ENV', 'APP_DEBUG', 'APP_URL', 'APP_KEY', 'DB_CONNECTION', 'DB_DATABASE',
    'DB_URL', 'DATABASE_URL', 'SESSION_DRIVER', 'CACHE_STORE', 'QUEUE_CONNECTION',
    'MAIL_MAILER', 'LARAVEL_STORAGE_PATH'
)
$originalEnvironment = @{}
foreach ($name in $isolatedEnvironmentNames) {
    $originalEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}

Push-Location $repoRoot
try {
    $env:APP_ENV = 'local'
    $env:APP_DEBUG = 'false'
    $env:APP_URL = 'http://127.0.0.1:8766'
    $env:APP_KEY = (& php -r "echo 'base64:' . base64_encode(random_bytes(32));").Trim()
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = $databasePath
    $env:DB_URL = ''
    $env:DATABASE_URL = ''
    $env:SESSION_DRIVER = 'file'
    $env:CACHE_STORE = 'array'
    $env:QUEUE_CONNECTION = 'sync'
    $env:MAIL_MAILER = 'array'
    $env:LARAVEL_STORAGE_PATH = Join-Path $demoRoot 'storage'

    & php artisan migrate --force --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'Isolated demo migrations failed.' }
    $seedOutput = & php (Join-Path $repoRoot 'scripts\qa\seed-phase-i-demo.php') 2>&1
    $seedExitCode = $LASTEXITCODE
    $seedOutput | Write-Output
    if ($seedExitCode -ne 0 -or ($seedOutput -join "`n") -notmatch 'Seeded fictional') { throw 'Synthetic demo fixture seeding failed.' }

    $server = Start-Process -FilePath 'C:\xampp\php\php.exe' `
        -ArgumentList @('artisan', 'serve', '--host=127.0.0.1', '--port=8766', '--no-reload') `
        -WorkingDirectory $repoRoot -WindowStyle Hidden -PassThru `
        -RedirectStandardOutput (Join-Path $demoRoot 'server.log') `
        -RedirectStandardError (Join-Path $demoRoot 'server.err')
    $serverProcess = Get-CimInstance Win32_Process -Filter "ProcessId=$($server.Id)" -ErrorAction SilentlyContinue
    if ($null -eq $serverProcess) {
        if (-not $server.HasExited) { $server.Kill() }
        throw 'Could not record the demo server process identity.'
    }
    $serverRecord = Get-DemoProcessRecord $serverProcess
    @($serverRecord) | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $demoRoot 'processes.json')
    $listener = $null
    for ($attempt = 0; $attempt -lt 30 -and $null -eq $listener; $attempt++) {
        Start-Sleep -Milliseconds 500
        $listener = Get-NetTCPConnection -State Listen -LocalPort 8766 -ErrorAction SilentlyContinue | Select-Object -First 1
    }
    if ($null -eq $listener) { throw 'Demo server did not begin listening on port 8766.' }
    $listenerProcess = Get-CimInstance Win32_Process -Filter "ProcessId=$($listener.OwningProcess)"
    if ($listenerProcess.Name -ne 'php.exe' -or $listenerProcess.CommandLine -notlike '*8766*') {
        throw 'The port listener did not match the expected demo server.'
    }
    $listenerRecord = Get-DemoProcessRecord $listenerProcess
    @($listenerRecord, $serverRecord) | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $demoRoot 'processes.json')

    $ready = $false
    for ($attempt = 0; $attempt -lt 30 -and -not $ready; $attempt++) {
        try {
            $response = Invoke-WebRequest -Uri 'http://127.0.0.1:8766/login' -UseBasicParsing -TimeoutSec 2
            $ready = $response.StatusCode -eq 200
        } catch { Start-Sleep -Milliseconds 500 }
    }
    if (-not $ready) { throw 'Demo server is listening but login did not return HTTP 200.' }
} catch {
    $processFile = Join-Path $demoRoot 'processes.json'
    if (Test-Path -LiteralPath $processFile) {
        foreach ($entry in (Get-Content -Raw -LiteralPath $processFile | ConvertFrom-Json)) {
            $process = Get-CimInstance Win32_Process -Filter "ProcessId=$($entry.pid)" -ErrorAction SilentlyContinue
            if ($null -eq $process) { continue }
            if (-not (Test-DemoProcessIdentity $entry $process)) { continue }
            $isListener = $process.Name -eq 'php.exe' -and $process.CommandLine -like "*$repoRoot*" -and $process.CommandLine -like '*-S 127.0.0.1:8766*'
            $isServerParent = $process.Name -eq 'php.exe' -and $process.CommandLine -like '*artisan serve --host=127.0.0.1 --port=8766 --no-reload*'
            if ($isListener -or $isServerParent) { Stop-Process -Id $entry.pid -Force }
        }
    }
    Start-Sleep -Milliseconds 300
    if (Get-NetTCPConnection -State Listen -LocalPort 8766 -ErrorAction SilentlyContinue) {
        throw "Setup failed while port 8766 still listens; demo data was preserved at $demoRoot for safe cleanup."
    }
    if (Test-Path -LiteralPath $demoRoot) { Remove-Item -LiteralPath $demoRoot -Recurse -Force }
    throw
} finally {
    try {
        Pop-Location
    } finally {
        foreach ($name in $isolatedEnvironmentNames) {
            [Environment]::SetEnvironmentVariable($name, $originalEnvironment[$name], 'Process')
        }
    }
}

Write-Output 'Synthetic demo is ready at http://127.0.0.1:8766'
Write-Output "Random demo sign-in credentials are in: $(Join-Path $demoRoot 'credentials.txt')"
Write-Output 'Use Cleanup after the walkthrough. No real MySQL data is used.'
