# Native WP-CLI (Laragon PHP). No Docker.
# Example: .\scripts\wp.ps1 plugin list

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
$Php = "C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe"
$Phar = Join-Path $PSScriptRoot "wp-cli.phar"
$WpPath = Join-Path $Root "wordpress"

if (-not (Test-Path $Php)) {
    Write-Host "Laragon PHP not found at $Php"
    exit 1
}
if ($args.Count -eq 0) {
    Write-Host "Usage: .\scripts\wp.ps1 <wp-cli args>"
    Write-Host "Example: .\scripts\wp.ps1 plugin list"
    exit 1
}
if (-not (Test-Path $Phar)) {
    Invoke-WebRequest -Uri "https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar" -OutFile $Phar -UseBasicParsing
}

& $Php $Phar --path=$WpPath @args
exit $LASTEXITCODE
