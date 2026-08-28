# Stable local kcjdrama: Laragon MySQL + Apache on :8080
# Canonical tree: C:\Scripts\wp-dev\sites\kcjdrama
# Docroot: wordpress\ (also junction C:\laragon\www\kcjdrama)
# Apache conf: C:\laragon\etc\apache2\sites-enabled\kcjdrama-8080.conf
# No Docker. No php -S (flaky). No ngrok.
#
# Phone: http://10.0.0.101:8080/ (same Wi-Fi). One-time Admin firewall:
#   netsh advfirewall firewall add rule name="KCJ local PHP 8080" dir=in action=allow protocol=TCP localport=8080 profile=private,domain

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
$EnsureMysql = "C:\Scripts\wp-dev\scripts\ensure-mysql.ps1"
$Httpd = "C:\laragon\bin\apache\httpd-2.4.68-260617-Win64-VS18\bin\httpd.exe"
$HttpdRoot = "C:\laragon\bin\apache\httpd-2.4.68-260617-Win64-VS18"
$Junction = "C:\laragon\www\kcjdrama"
$WpDoc = Join-Path $Root "wordpress"
$Port = 8080

function Get-LanIPv4 {
    try {
        $ip = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
            Where-Object {
                $_.IPAddress -like '10.*' -or
                $_.IPAddress -like '192.168.*' -or
                ($_.IPAddress -match '^172\.(1[6-9]|2[0-9]|3[0-1])\.')
            } |
            Where-Object { $_.InterfaceAlias -notmatch 'vEthernet|WSL|Loopback|Docker' } |
            Select-Object -First 1 -ExpandProperty IPAddress
        return $ip
    } catch {
        return $null
    }
}

if (-not (Test-Path $Httpd)) {
    Write-Error "Apache httpd not found: $Httpd"
    exit 1
}

& $EnsureMysql
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

# Ensure www junction points at this site's wordpress/
New-Item -ItemType Directory -Force -Path (Split-Path $Junction) | Out-Null
if (Test-Path $Junction) {
    $item = Get-Item $Junction -Force
    $target = $null
    if ($item.LinkType -eq 'Junction' -or $item.Attributes -match 'ReparsePoint') {
        $target = $item.Target
        if ($target -is [array]) { $target = $target[0] }
    }
    $want = (Resolve-Path $WpDoc).Path
    if (-not $target -or ((Resolve-Path -LiteralPath $target -ErrorAction SilentlyContinue).Path -ne $want)) {
        Write-Host "Refreshing junction $Junction -> $want"
        cmd /c "rmdir `"$Junction`"" | Out-Null
        cmd /c "mklink /J `"$Junction`" `"$want`"" | Out-Null
    }
} else {
    Write-Host "Creating junction $Junction -> $WpDoc"
    cmd /c "mklink /J `"$Junction`" `"$WpDoc`"" | Out-Null
}

# Stop flaky PHP built-in server if it still owns 8080
Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue | ForEach-Object {
    $proc = Get-Process -Id $_.OwningProcess -ErrorAction SilentlyContinue
    if ($proc -and $proc.ProcessName -eq 'php') {
        Write-Host "Stopping php -S on $Port (PID $($proc.Id))"
        Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 1
    }
}

# Is Apache already listening on 8080?
$apacheListen = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue | Where-Object {
    $p = Get-Process -Id $_.OwningProcess -ErrorAction SilentlyContinue
    $p -and ($p.ProcessName -match 'httpd')
}

if (-not $apacheListen) {
    Get-Process -Name httpd -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
    Start-Sleep -Milliseconds 500
    Write-Host "Starting Apache on 0.0.0.0:$Port …"
    Start-Process -FilePath $Httpd -ArgumentList "-d `"$HttpdRoot`"" -WorkingDirectory $HttpdRoot -WindowStyle Hidden
    Start-Sleep -Seconds 2
}

$listening = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if (-not $listening) {
    Write-Host "Apache failed to bind :$Port. Check C:\laragon\tmp\kcjdrama-apache-error.log"
    if (Test-Path "C:\laragon\tmp\kcjdrama-apache-error.log") {
        Get-Content "C:\laragon\tmp\kcjdrama-apache-error.log" -Tail 30
    }
    # Config test
    & $Httpd -t 2>&1
    exit 1
}

$lan = Get-LanIPv4
Write-Host ""
Write-Host "kcjdrama local (Laragon Apache + MySQL) is up."
Write-Host "  PC:    http://127.0.0.1:$Port/"
if ($lan) {
    Write-Host "  Phone: http://${lan}:$Port/   (same Wi-Fi; firewall must allow TCP $Port)"
}
Write-Host "  Desk:  http://127.0.0.1:$Port/stories/#kcj-desk"
Write-Host ""
Write-Host "Apache stays running after this script exits. Stop with: Get-Process httpd | Stop-Process"
