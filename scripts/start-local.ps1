# Start container-free kcjdrama WP on http://127.0.0.1:8080
# Canonical tree: C:\Scripts\wp-dev\sites\kcjdrama
# MySQL: shared Laragon mysqld (port 3306) — started detached so it survives this shell.
# PHP: Laragon built-in server. No Docker.

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
$Php = "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe"
$EnsureMysql = "C:\Scripts\wp-dev\scripts\ensure-mysql.ps1"

if (-not (Test-Path $Php)) {
    Write-Error "PHP not found: $Php"
    exit 1
}

& $EnsureMysql
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

$listening = Get-NetTCPConnection -LocalPort 8080 -State Listen -ErrorAction SilentlyContinue
if ($listening) {
    Write-Host "Already listening on 8080. Open http://127.0.0.1:8080/"
    exit 0
}

Set-Location $Root
Write-Host "kcjdrama local (no Docker): http://127.0.0.1:8080/"
& $Php -S 127.0.0.1:8080 -t wordpress (Join-Path $PSScriptRoot "php-router.php")
