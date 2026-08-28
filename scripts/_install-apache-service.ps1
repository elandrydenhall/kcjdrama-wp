$Httpd = "C:\laragon\bin\apache\httpd-2.4.68-260617-Win64-VS18\bin\httpd.exe"
$HttpdRoot = "C:\laragon\bin\apache\httpd-2.4.68-260617-Win64-VS18"
# Stop manual processes first
Get-Process httpd -EA SilentlyContinue | Stop-Process -Force -EA SilentlyContinue
Start-Sleep 1
& $Httpd -k uninstall -n "KCJApache8080" 2>$null
& $Httpd -k install -n "KCJApache8080" 2>&1
Start-Service KCJApache8080 -ErrorAction SilentlyContinue
Start-Sleep 2
Get-Service KCJApache8080 -ErrorAction SilentlyContinue | Format-List Status,Name,StartType
netstat -ano | findstr "LISTENING" | findstr "8080"
curl.exe -s -o NUL -w "svc=%{http_code}`n" --max-time 5 http://10.0.0.101:8080/
