# Run a WP-CLI command against the local stack.
# Example: .\scripts\wp.ps1 plugin list

$ErrorActionPreference = "Continue"
$PSNativeCommandUseErrorActionPreference = $false

$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

if ($args.Count -eq 0) {
    Write-Host "Usage: .\scripts\wp.ps1 <wp-cli args>"
    Write-Host "Example: .\scripts\wp.ps1 plugin list"
    exit 1
}

& docker compose --profile cli run --rm --no-TTY wpcli wp @args
exit $LASTEXITCODE
