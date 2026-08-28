# ONE-TIME: allow phone access to local KCJ on TCP 8080
# Run as Administrator:
#   powershell -ExecutionPolicy Bypass -File C:\Scripts\wp-dev\sites\kcjdrama\scripts\allow-lan-8080.ps1

#Requires -RunAsAdministrator
$ErrorActionPreference = "Stop"
$name = 'KCJ local Apache 8080'
netsh advfirewall firewall delete rule name=$name 2>$null | Out-Null
netsh advfirewall firewall add rule name=$name dir=in action=allow protocol=TCP localport=8080 profile=private,domain
Write-Host ""
Write-Host "OK - inbound TCP 8080 allowed on Private/Domain."
Write-Host "On phone (same Wi-Fi): http://10.0.0.101:8080/"
Write-Host ""
netsh advfirewall firewall show rule name=$name
pause
