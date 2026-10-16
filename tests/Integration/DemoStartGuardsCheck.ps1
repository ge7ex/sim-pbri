$ErrorActionPreference = 'Stop'
$repo = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$script = Join-Path $repo 'scripts\qa\phase-i-demo.ps1'
$demo = Join-Path $env:TEMP 'sim-pbri-phase-i-demo'
$database = Join-Path $demo 'sim-pbri-phase-i-demo.sqlite'
$credentials = Join-Path $demo 'credentials.txt'
$listener = Get-NetTCPConnection -State Listen -LocalPort 8766 -ErrorAction SilentlyContinue | Select-Object -First 1
if ($null -eq $listener -or -not (Test-Path -LiteralPath $database) -or -not (Test-Path -LiteralPath $credentials)) {
    throw 'This non-destructive check requires an already running isolated demo.'
}
$beforeDatabase = (Get-FileHash -LiteralPath $database -Algorithm SHA256).Hash
$beforeCredentials = (Get-FileHash -LiteralPath $credentials -Algorithm SHA256).Hash
$checks = @()
foreach ($case in @(@{action='Setup';expected='Demo directory already exists'}, @{action='Start';expected='Port 8766 is already in use'})) {
    $refused = $false
    try { & $script -Action $case.action } catch { $refused = $_.Exception.Message.Contains($case.expected) }
    if (-not $refused) { throw "Expected refusal absent for $($case.action)." }
    $checks += "$($case.action): refused safely"
}
$after = Get-NetTCPConnection -State Listen -LocalPort 8766 -ErrorAction SilentlyContinue | Select-Object -First 1
if ($null -eq $after -or $after.OwningProcess -ne $listener.OwningProcess -or
    $beforeDatabase -ne (Get-FileHash -LiteralPath $database -Algorithm SHA256).Hash -or
    $beforeCredentials -ne (Get-FileHash -LiteralPath $credentials -Algorithm SHA256).Hash) {
    throw 'Existing demo data, credentials or listener changed.'
}
$errors = $null
$tokens = $null
[void][Management.Automation.Language.Parser]::ParseFile($script, [ref]$tokens, [ref]$errors)
if ($errors.Count) { throw 'Demo script syntax check failed.' }
[pscustomobject]@{checks=$checks;database_preserved=$true;credentials_preserved=$true;listener_preserved=$true;syntax='pass'} | ConvertTo-Json
