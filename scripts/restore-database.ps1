<#
    Restores a PMMS mysqldump backup (plain .sql or gzip-compressed .sql.gz,
    as produced by backup-database.ps1) into a MySQL database. Connection
    details are read from the project's .env at runtime, same as the backup
    script - never hardcoded, logged, or passed on the command line.

    Refuses to target the production database name from .env unless -Force
    is passed, so a routine restore-and-verify drill can't accidentally
    overwrite live data.

    Usage:
        powershell -File scripts\restore-database.ps1 -BackupFile .\storage\app\private\backups\database\pmmsdb-20260726-120000.sql.gz -TargetDatabase pmmsdb_restore_test
        powershell -File scripts\restore-database.ps1 -BackupFile <path> -TargetDatabase pmmsdb -Force   # real disaster recovery only
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string]$BackupFile,

    [Parameter(Mandatory = $true)]
    [string]$TargetDatabase,

    [string]$EnvFile = '',

    [switch]$Force
)

$ErrorActionPreference = 'Stop'

# $PSScriptRoot is not reliably populated inside param() default-value
# expressions under Windows PowerShell 5.1, so it's resolved here instead,
# once the script body is actually running.
$scriptDir = $PSScriptRoot
if (-not $scriptDir) {
    $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}
if (-not $EnvFile) {
    $EnvFile = Join-Path $scriptDir '..\.env'
}

function Read-EnvValue {
    param([string]$Path, [string]$Key)

    $line = Get-Content $Path | Where-Object { $_ -match "^\s*$Key\s*=" } | Select-Object -First 1
    if (-not $line) { return $null }

    $value = ($line -replace "^\s*$Key\s*=", '').Trim()
    if ($value.Length -ge 2 -and $value.StartsWith('"') -and $value.EndsWith('"')) {
        $value = $value.Substring(1, $value.Length - 2)
    }

    return $value
}

if (-not (Test-Path $BackupFile)) {
    throw "Backup file not found: $BackupFile"
}

$dbHost = Read-EnvValue -Path $EnvFile -Key 'DB_HOST'
if (-not $dbHost) { $dbHost = '127.0.0.1' }
$dbPort = Read-EnvValue -Path $EnvFile -Key 'DB_PORT'
if (-not $dbPort) { $dbPort = '3306' }
$dbUser = Read-EnvValue -Path $EnvFile -Key 'DB_USERNAME'
$dbPass = Read-EnvValue -Path $EnvFile -Key 'DB_PASSWORD'
$prodDb = Read-EnvValue -Path $EnvFile -Key 'DB_DATABASE'

if ($TargetDatabase -eq $prodDb -and -not $Force) {
    throw "Target database '$TargetDatabase' matches DB_DATABASE in $EnvFile - restoring here would overwrite live data. Re-run with -Force if that's genuinely what you want (real disaster recovery)."
}

if (-not (Get-Command mysql -ErrorAction SilentlyContinue)) {
    throw 'mysql client was not found on PATH.'
}

$defaultsFile = Join-Path $env:TEMP ("pmms-restore-{0}.cnf" -f [Guid]::NewGuid())
"[client]`nhost=$dbHost`nport=$dbPort`nuser=$dbUser`npassword=$dbPass" |
    Out-File -FilePath $defaultsFile -Encoding ascii -NoNewline
icacls $defaultsFile /inheritance:r /grant:r "$($env:USERNAME):(R,W)" | Out-Null

$tempExtracted = $null

try {
    & mysql --defaults-extra-file=$defaultsFile -e "CREATE DATABASE IF NOT EXISTS ``$TargetDatabase``"
    if ($LASTEXITCODE -ne 0) {
        throw "Failed to create target database (exit $LASTEXITCODE)"
    }

    $sqlFile = $BackupFile
    if ($BackupFile.ToLower().EndsWith('.gz')) {
        $tempExtracted = Join-Path $env:TEMP ("pmms-restore-{0}.sql" -f [Guid]::NewGuid())
        $inStream = [System.IO.File]::OpenRead($BackupFile)
        $gzStream = New-Object System.IO.Compression.GZipStream($inStream, [System.IO.Compression.CompressionMode]::Decompress)
        $outStream = [System.IO.File]::Create($tempExtracted)
        try {
            $gzStream.CopyTo($outStream)
        } finally {
            $outStream.Dispose()
            $gzStream.Dispose()
            $inStream.Dispose()
        }
        $sqlFile = $tempExtracted
    }

    # cmd's `<` redirection streams the file straight into mysql's stdin -
    # more reliable for large SQL dumps than reading the whole file into a
    # PowerShell string first.
    $cmdLine = 'mysql --defaults-extra-file="' + $defaultsFile + '" "' + $TargetDatabase + '" < "' + $sqlFile + '"'
    cmd /c $cmdLine
    if ($LASTEXITCODE -ne 0) {
        throw "mysql restore exited with code $LASTEXITCODE"
    }
} finally {
    Remove-Item -Path $defaultsFile -Force -ErrorAction SilentlyContinue
    if ($tempExtracted) {
        Remove-Item -Path $tempExtracted -Force -ErrorAction SilentlyContinue
    }
}

Write-Host "Restored '$BackupFile' into database '$TargetDatabase'."
