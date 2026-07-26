<#
    One-time setup: registers a Windows scheduled task that keeps a PMMS
    queue worker (`php artisan queue:work`) running continuously and
    restarts it if it ever stops. This is NOT run automatically by
    anything in this repo or during deployment - the server administrator
    runs it once, deliberately. See docs/deployment.md "Queue worker."

    Why this exists: `QUEUE_CONNECTION=database` means nothing processes
    queued jobs unless a worker is actually running. Today the only queued
    job in the app is Phase 7's `App\Events\ScoreUpdated` broadcast (see
    docs/live-scoring.md) - without a worker, every live-scoring score
    change silently queues a job that never runs, and the `jobs` table
    grows forever. Live scoring's own polling fallback still works either
    way (this was never a functional break for end users), but the queue
    should still be drained.

    This task runs at every machine startup (not on a schedule) because
    `queue:work` is meant to run indefinitely, not as a one-shot job like
    backup-database.ps1. Task Scheduler's restart settings bring it back
    if it ever exits/crashes.

    Usage:
        powershell -File scripts\install-queue-worker-schedule.ps1

    For genuinely unattended 24/7 operation (no one ever logged into this
    machine), edit the registered task afterward in Task Scheduler
    (taskschd.msc) to run "whether user is logged on or not" under a
    service account of your choosing - this script deliberately does not
    prompt for or store credentials itself, the same principle
    backup-database.ps1 follows for the database password.
#>
[CmdletBinding()]
param(
    [string]$TaskName = 'PMMS Queue Worker',
    [int]$RestartIntervalMinutes = 1,
    [int]$RestartCount = 999
)

$ErrorActionPreference = 'Stop'

$scriptDir = $PSScriptRoot
if (-not $scriptDir) {
    $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}
$repoRoot = Resolve-Path (Join-Path $scriptDir '..')

$phpCommand = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpCommand) {
    throw 'php was not found on PATH. Add PHP (Laragon ships it under bin\php\<version>) to PATH and retry.'
}
$phpPath = $phpCommand.Source

$artisanPath = Join-Path $repoRoot 'artisan'
if (-not (Test-Path $artisanPath)) {
    throw "artisan not found at $artisanPath - is this script still next to the repo's scripts\ directory?"
}

$action = New-ScheduledTaskAction -Execute $phpPath `
    -Argument 'artisan queue:work --tries=3 --backoff=5' `
    -WorkingDirectory $repoRoot
$trigger = New-ScheduledTaskTrigger -AtStartup
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd `
    -RestartCount $RestartCount -RestartInterval (New-TimeSpan -Minutes $RestartIntervalMinutes) `
    -ExecutionTimeLimit ([TimeSpan]::Zero)

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings `
    -Description 'Keeps a PMMS queue worker (php artisan queue:work) running continuously, restarting it if it stops.' `
    -Force | Out-Null

Write-Host "Scheduled task '$TaskName' registered - starts at machine startup, restarts up to $RestartCount times ($RestartIntervalMinutes min apart) if it stops."
Write-Host "Start it now without rebooting: Start-ScheduledTask -TaskName '$TaskName'"
Write-Host "Verify with: Get-ScheduledTask -TaskName '$TaskName' | Get-ScheduledTaskInfo"
