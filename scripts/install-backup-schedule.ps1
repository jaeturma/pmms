<#
    One-time setup: registers a daily Windows scheduled task that runs
    backup-database.ps1 unattended. This is NOT run automatically by
    anything in this repo or during deployment - the server administrator
    runs it once, deliberately, when setting up (or reviewing) the backup
    schedule. See docs/backup-restore.md.

    Usage:
        powershell -File scripts\install-backup-schedule.ps1
        powershell -File scripts\install-backup-schedule.ps1 -Time '02:00' -RetentionCount 30
#>
[CmdletBinding()]
param(
    [string]$Time = '02:00',
    [string]$TaskName = 'PMMS Database Backup',
    [int]$RetentionCount = 14
)

$ErrorActionPreference = 'Stop'

$scriptDir = $PSScriptRoot
if (-not $scriptDir) {
    $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}
$backupScript = Join-Path $scriptDir 'backup-database.ps1'

if (-not (Test-Path $backupScript)) {
    throw "backup-database.ps1 not found next to this script: $backupScript"
}

$action = New-ScheduledTaskAction -Execute 'powershell.exe' `
    -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$backupScript`" -RetentionCount $RetentionCount"
$trigger = New-ScheduledTaskTrigger -Daily -At $Time
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd

Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger $trigger -Settings $settings `
    -Description 'Nightly PMMS database backup (mysqldump via scripts\backup-database.ps1).' `
    -Force | Out-Null

Write-Host "Scheduled task '$TaskName' registered - runs daily at $Time, keeping the last $RetentionCount backups."
Write-Host "Verify with: Get-ScheduledTask -TaskName '$TaskName' | Get-ScheduledTaskInfo"
