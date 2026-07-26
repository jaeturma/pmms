<#
    Backs up the PMMS MySQL database via mysqldump into
    storage/app/private/backups/database/, applying a simple retention
    policy. Credentials are read from the project's .env at runtime and are
    never hardcoded, logged, or passed on the command line - they go into a
    short-lived, ACL-restricted mysql "defaults extra file" that is deleted
    immediately after the dump completes.

    Usage:
        powershell -File scripts\backup-database.ps1
        powershell -File scripts\backup-database.ps1 -RetentionCount 30
        powershell -File scripts\backup-database.ps1 -NoCompress
#>
[CmdletBinding()]
param(
    [string]$EnvFile = '',
    [string]$OutputDir = '',
    [int]$RetentionCount = 14,
    [switch]$NoCompress
)

$ErrorActionPreference = 'Stop'

# $PSScriptRoot is not reliably populated inside param() default-value
# expressions under Windows PowerShell 5.1, so paths are resolved here
# instead, once the script body is actually running.
$scriptDir = $PSScriptRoot
if (-not $scriptDir) {
    $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
}
if (-not $EnvFile) {
    $EnvFile = Join-Path $scriptDir '..\.env'
}
if (-not $OutputDir) {
    $OutputDir = Join-Path $scriptDir '..\storage\app\private\backups\database'
}

function Read-EnvValue {
    param([string]$Path, [string]$Key)

    if (-not (Test-Path $Path)) {
        throw "Env file not found: $Path"
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

$connection = Read-EnvValue -Path $EnvFile -Key 'DB_CONNECTION'
if ($connection -ne 'mysql') {
    throw "DB_CONNECTION in $EnvFile is '$connection', not 'mysql' - this script backs up the MySQL production database only."
}

$dbHost = Read-EnvValue -Path $EnvFile -Key 'DB_HOST'
if (-not $dbHost) { $dbHost = '127.0.0.1' }
$dbPort = Read-EnvValue -Path $EnvFile -Key 'DB_PORT'
if (-not $dbPort) { $dbPort = '3306' }
$dbName = Read-EnvValue -Path $EnvFile -Key 'DB_DATABASE'
$dbUser = Read-EnvValue -Path $EnvFile -Key 'DB_USERNAME'
$dbPass = Read-EnvValue -Path $EnvFile -Key 'DB_PASSWORD'

if (-not $dbName -or -not $dbUser) {
    throw "DB_DATABASE / DB_USERNAME missing from $EnvFile - cannot back up."
}

if (-not (Get-Command mysqldump -ErrorAction SilentlyContinue)) {
    throw 'mysqldump was not found on PATH. Add the MySQL client tools to PATH (Laragon ships them under bin\mysql\<version>\bin) and retry.'
}

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$defaultsFile = Join-Path $env:TEMP ("pmms-backup-{0}.cnf" -f [Guid]::NewGuid())
"[client]`nhost=$dbHost`nport=$dbPort`nuser=$dbUser`npassword=$dbPass" |
    Out-File -FilePath $defaultsFile -Encoding ascii -NoNewline
icacls $defaultsFile /inheritance:r /grant:r "$($env:USERNAME):(R,W)" | Out-Null

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$sqlFile = Join-Path $OutputDir "$dbName-$timestamp.sql"

try {
    & mysqldump --defaults-extra-file=$defaultsFile --single-transaction --routines --triggers --result-file=$sqlFile $dbName
    if ($LASTEXITCODE -ne 0) {
        throw "mysqldump exited with code $LASTEXITCODE"
    }
} finally {
    Remove-Item -Path $defaultsFile -Force -ErrorAction SilentlyContinue
}

$finalFile = $sqlFile
if (-not $NoCompress) {
    $gzFile = "$sqlFile.gz"
    $inStream = [System.IO.File]::OpenRead($sqlFile)
    $outStream = [System.IO.File]::Create($gzFile)
    $gzStream = New-Object System.IO.Compression.GZipStream($outStream, [System.IO.Compression.CompressionMode]::Compress)
    try {
        $inStream.CopyTo($gzStream)
    } finally {
        $gzStream.Dispose()
        $outStream.Dispose()
        $inStream.Dispose()
    }
    Remove-Item -Path $sqlFile -Force
    $finalFile = $gzFile
}

$size = (Get-Item $finalFile).Length
Write-Host "Backup written: $finalFile ($([Math]::Round($size / 1MB, 2)) MB)"

$existing = Get-ChildItem -Path $OutputDir -Filter "$dbName-*.sql*" | Sort-Object LastWriteTime -Descending
if ($existing.Count -gt $RetentionCount) {
    $existing | Select-Object -Skip $RetentionCount | ForEach-Object {
        Write-Host "Retention: removing old backup $($_.Name)"
        Remove-Item $_.FullName -Force
    }
}
