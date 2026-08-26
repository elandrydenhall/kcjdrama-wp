# Start container-free kcjdrama WP on http://127.0.0.1:8080
# Canonical tree: C:\Scripts\wp-dev\sites\kcjdrama
# MySQL: Laragon mysqld. PHP: Laragon built-in server. No Docker.

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
$Php = "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe"
$Mysqld = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqld.exe"
$MyIni = "C:\laragon\data\mysql-kcj.ini"

if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
    Start-Process -FilePath $Mysqld -ArgumentList "--defaults-file=$MyIni" -WindowStyle Hidden
    Start-Sleep -Seconds 3
}

$listening = Get-NetTCPConnection -LocalPort 8080 -State Listen -ErrorAction SilentlyContinue
if ($listening) {
    Write-Host "Already listening on 8080. Open http://127.0.0.1:8080/"
    exit 0
}

Set-Location $Root
Write-Host "kcjdrama local (no Docker): http://127.0.0.1:8080/"
& $Php -S 127.0.0.1:8080 -t wordpress (Join-Path $PSScriptRoot "php-router.php")
