<#
    One-shot manual health check (WP-09-02) - runs the three checks from
    docs/monitoring.md in one pass and prints a pass/fail summary. This is
    NOT a background process or scheduled task - a person runs it
    themselves, on whatever cadence they decide. See docs/monitoring.md.

    Usage:
        powershell -File scripts\health-check.ps1
        powershell -File scripts\health-check.ps1 -LogLines 500
#>
[CmdletBinding()]
param(
    [string]$EnvFile = '',
    [string]$LogFile = '',
    [int]$LogLines = 200
)

$ErrorActionPreference = 'Stop'

$scriptDir = $PSScriptRoot
if (-not $scriptDir) {
    $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}
if (-not $EnvFile) {
    $EnvFile = Join-Path $scriptDir '..\.env'
}
if (-not $LogFile) {
    $LogFile = Join-Path $scriptDir '..\storage\logs\laravel.log'
}

function Read-EnvValue {
    param([string]$Path, [string]$Key)

    if (-not (Test-Path $Path)) {
        return $null
    }

    $line = Get-Content $Path | Where-Object { $_ -match "^\s*$Key\s*=" } | Select-Object -First 1
    if (-not $line) {
        return $null
    }

    $value = ($line -replace "^\s*$Key\s*=", '').Trim()
    if ($value.Length -ge 2 -and $value.StartsWith('"') -and $value.EndsWith('"')) {
        $value = $value.Substring(1, $value.Length - 2)
    }

    return $value
}

$failures = 0

Write-Host "=== PMMS health check ===" -ForegroundColor Cyan

# 1. /up responds
$appUrl = Read-EnvValue -Path $EnvFile -Key 'APP_URL'
if (-not $appUrl) { $appUrl = 'http://localhost' }
$healthUrl = $appUrl.TrimEnd('/') + '/up'

Write-Host "`n[1/3] $healthUrl"
try {
    $response = Invoke-WebRequest -Uri $healthUrl -UseBasicParsing -TimeoutSec 10
    if ($response.StatusCode -eq 200) {
        Write-Host "  PASS - HTTP 200" -ForegroundColor Green
    } else {
        Write-Host "  FAIL - HTTP $($response.StatusCode)" -ForegroundColor Red
        $failures++
    }
} catch {
    Write-Host "  FAIL - $($_.Exception.Message)" -ForegroundColor Red
    $failures++
}

# 2. Log skim - real (non-"testing.") ERROR/CRITICAL/EMERGENCY lines in the
# most recent $LogLines lines. "testing." entries are Pest test-run noise
# (this app logs to the same file regardless of APP_ENV), not real
# production/local errors, and are excluded the same way a human skimming
# the file would skip past them.
Write-Host "`n[2/3] storage/logs/laravel.log (last $LogLines lines)"
if (-not (Test-Path $LogFile)) {
    Write-Host "  FAIL - log file not found: $LogFile" -ForegroundColor Red
    $failures++
} else {
    $recent = Get-Content $LogFile -Tail $LogLines
    $alarming = $recent | Where-Object {
        $_ -match '\.(ERROR|CRITICAL|EMERGENCY):' -and $_ -notmatch '^\[[\d\-: ]+\] testing\.'
    }
    if ($alarming.Count -eq 0) {
        Write-Host "  PASS - nothing alarming" -ForegroundColor Green
    } else {
        Write-Host "  FAIL - $($alarming.Count) alarming line(s), most recent:" -ForegroundColor Red
        $alarming | Select-Object -Last 3 | ForEach-Object { Write-Host "    $_" }
        $failures++
    }
}

# 3. Scheduled Tasks from Phase 6
Write-Host "`n[3/3] Scheduled Tasks"
foreach ($taskName in @('PMMS Database Backup', 'PMMS Queue Worker')) {
    $task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    if (-not $task) {
        Write-Host "  FAIL - '$taskName' is not registered" -ForegroundColor Red
        $failures++
        continue
    }

    $info = $task | Get-ScheduledTaskInfo
    if ($task.State -eq 'Disabled') {
        Write-Host "  FAIL - '$taskName' is registered but disabled" -ForegroundColor Red
        $failures++
    } elseif ($info.LastTaskResult -eq 0 -or $null -eq $info.LastRunTime) {
        Write-Host "  PASS - '$taskName' ($($task.State), last run: $($info.LastRunTime))" -ForegroundColor Green
    } else {
        Write-Host "  FAIL - '$taskName' last run result code $($info.LastTaskResult) (0 = success)" -ForegroundColor Red
        $failures++
    }
}

Write-Host "`n==========================="
if ($failures -eq 0) {
    Write-Host "All checks passed." -ForegroundColor Green
} else {
    Write-Host "$failures check(s) failed - see above." -ForegroundColor Red
}
exit $failures
