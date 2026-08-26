# First-run (and re-run safe) local WordPress setup.
# Starts Compose, waits for the site, and installs WordPress if needed.

# Continue: docker compose writes status to stderr, and Windows PowerShell
# would otherwise treat that as a terminating NativeCommandError.
$ErrorActionPreference = "Continue"
$PSNativeCommandUseErrorActionPreference = $false

$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

function Read-DotEnv {
    param([string]$Path)
    $map = @{}
    if (-not (Test-Path $Path)) {
        return $map
    }
    Get-Content -Path $Path | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq "" -or $line.StartsWith("#")) {
            return
        }
        $parts = $line -split "=", 2
        if ($parts.Count -eq 2) {
            $map[$parts[0].Trim()] = $parts[1].Trim()
        }
    }
    return $map
}

function Invoke-WpCli {
    param([string[]]$WpArgs)
    & docker compose --profile cli run --rm --no-TTY wpcli wp @WpArgs
    return $LASTEXITCODE
}

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    throw "Docker is not installed or not on PATH. Install Docker Desktop and try again."
}

$previousEap = $ErrorActionPreference
$ErrorActionPreference = "SilentlyContinue"
cmd /c "docker info >NUL 2>&1"
$dockerReady = ($LASTEXITCODE -eq 0)
$ErrorActionPreference = $previousEap
if (-not $dockerReady) {
    throw "Docker Desktop does not appear to be running. Start it and try again."
}

$envExample = Join-Path $Root ".env.example"
$envFile = Join-Path $Root ".env"
if (-not (Test-Path $envFile)) {
    if (-not (Test-Path $envExample)) {
        throw "Missing .env.example; cannot create .env."
    }
    Copy-Item -Path $envExample -Destination $envFile
    Write-Host "Created .env from .env.example"
}

$wordpressDir = Join-Path $Root "wordpress"
if (-not (Test-Path $wordpressDir)) {
    New-Item -ItemType Directory -Path $wordpressDir | Out-Null
}

$config = Read-DotEnv $envFile
$wpUrl = if ($config["WP_URL"]) { $config["WP_URL"] } else { "http://localhost:8080" }
$wpTitle = if ($config["WP_TITLE"]) { $config["WP_TITLE"] } else { "Local WordPress" }
$wpAdminUser = if ($config["WP_ADMIN_USER"]) { $config["WP_ADMIN_USER"] } else { "admin" }
$wpAdminPassword = if ($config["WP_ADMIN_PASSWORD"]) { $config["WP_ADMIN_PASSWORD"] } else { "admin" }
$wpAdminEmail = if ($config["WP_ADMIN_EMAIL"]) { $config["WP_ADMIN_EMAIL"] } else { "admin@example.com" }

Write-Host "Starting WordPress stack..."
& docker compose up -d
if ($LASTEXITCODE -ne 0) {
    throw "docker compose up failed."
}

Write-Host "Waiting for WordPress at $wpUrl ..."
$deadline = (Get-Date).AddMinutes(5)
$ready = $false
while ((Get-Date) -lt $deadline) {
    try {
        $response = Invoke-WebRequest -Uri $wpUrl -UseBasicParsing -TimeoutSec 5 -MaximumRedirection 5
        if ($response.StatusCode -ge 200 -and $response.StatusCode -lt 500) {
            $ready = $true
            break
        }
    } catch {
        # Container still starting or files still copying.
    }
    Start-Sleep -Seconds 3
}

if (-not $ready) {
    throw "Timed out waiting for WordPress at $wpUrl. Check 'docker compose logs wordpress'."
}

Write-Host "Waiting for WP-CLI to see a WordPress install..."
$cliReady = $false
$installed = $false
while ((Get-Date) -lt $deadline) {
    cmd /c "docker compose --profile cli run --rm --no-TTY wpcli wp core is-installed >nul 2>&1"
    $code = $LASTEXITCODE
    if ($code -eq 0) {
        $cliReady = $true
        $installed = $true
        break
    }
    # Exit 1 from `wp core is-installed` means files/config exist but WP is not installed.
    if ($code -eq 1) {
        $cliReady = $true
        $installed = $false
        break
    }
    Start-Sleep -Seconds 3
}

if (-not $cliReady) {
    throw "Timed out waiting for WP-CLI. Check that ./wordpress was populated and MariaDB is healthy."
}

if ($installed) {
    Write-Host "WordPress is already installed."
} else {
    Write-Host "Installing WordPress..."
    $installCode = Invoke-WpCli @(
        "core", "install",
        "--url=$wpUrl",
        "--title=$wpTitle",
        "--admin_user=$wpAdminUser",
        "--admin_password=$wpAdminPassword",
        "--admin_email=$wpAdminEmail",
        "--skip-email"
    )
    if ($installCode -ne 0) {
        throw "wp core install failed (exit $installCode)."
    }
    Write-Host "WordPress installed."
}

Write-Host ""
Write-Host "Site:     $wpUrl"
Write-Host "Admin:    $wpUrl/wp-admin"
Write-Host "phpMyAdmin: http://localhost:$($config['PHPMYADMIN_PORT'])"
Write-Host "User:     $wpAdminUser"
Write-Host "Password: $wpAdminPassword"
Write-Host ""
Write-Host "WP-CLI:   .\scripts\wp.ps1 plugin list"
