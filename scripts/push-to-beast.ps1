# Push this repo to Beast (emergency mirror).
# Remote: elan@10.0.0.194:/mnt/drive-a/wp-dev/repos/kcjdrama-wp.git
# On Beast: git -C /mnt/drive-a/wp-dev/kcjdrama-wp pull

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root
git push beast
Write-Host "Beast pull: ssh 10.0.0.194 `"git -C /mnt/drive-a/wp-dev/kcjdrama-wp pull`""
