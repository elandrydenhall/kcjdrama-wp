# Pack theme, media, session, and handoff for Beast.
# Share parent is read-only; writable dest is uploads/_export-leaf.
# Always also writes C:\Scripts\wordpress\export-leaf

$ErrorActionPreference = "Continue"
$PSNativeCommandUseErrorActionPreference = $false

$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

$Share = "\\10.0.0.194\mnt\drive-a\wp-dev\kcjdrama"
$LocalLeaf = Join-Path $Root "export-leaf"
$ShareLeaf = Join-Path $Share "uploads\_export-leaf"
$SessionId = "019ffe4d-c606-77b1-8ce9-c25e0e9f57de"
$SessionSrc = "C:\Users\dallen\.grok\sessions\C%3A%5CScripts%5Cwordpress\$SessionId"
if (-not (Test-Path $SessionSrc)) {
    $alt = "C:\Users\dallen\.grok\sessions\C%3A%5CScripts%5CWordPress\$SessionId"
    if (Test-Path $alt) { $SessionSrc = $alt }
}

if (-not (Test-Path $Share)) {
    throw "Beast share not reachable: $Share"
}

function New-LeafTree([string]$LeafRoot) {
    New-Item -ItemType Directory -Force -Path @(
        (Join-Path $LeafRoot "theme"),
        (Join-Path $LeafRoot "session\$using:SessionId"),
        (Join-Path $LeafRoot "export")
    ) | Out-Null
}

# $using: only works in remoting; just use $SessionId
function New-LeafTree2([string]$LeafRoot) {
    New-Item -ItemType Directory -Force -Path @(
        (Join-Path $LeafRoot "theme"),
        (Join-Path $LeafRoot "session\$SessionId"),
        (Join-Path $LeafRoot "export")
    ) | Out-Null
}

New-LeafTree2 $LocalLeaf
$ShareWritable = $false
try {
    New-LeafTree2 $ShareLeaf
    $probe = Join-Path $ShareLeaf "_w.txt"
    Set-Content $probe "ok"
    Remove-Item $probe -Force
    $ShareWritable = $true
} catch {
    Write-Host "Share leaf not writable. Local only: $LocalLeaf"
}

$targets = @($LocalLeaf)
if ($ShareWritable) { $targets += $ShareLeaf }

function Copy-ThemeTo([string]$LeafRoot) {
    Write-Host "Theme → $LeafRoot\theme"
    robocopy (Join-Path $Root "kcjdrama") (Join-Path $LeafRoot "theme") /E /XO /COPY:DT /R:1 /W:1 /NFL /NDL /NJH /NJS /nc /ns /np /XD .git | Out-Null
    Write-Host "  robocopy=$LastExitCode"
}

function Copy-SessionTo([string]$LeafRoot) {
    $destRoot = Join-Path $LeafRoot "session\$SessionId"
    New-Item -ItemType Directory -Force -Path $destRoot | Out-Null
    if (-not (Test-Path $SessionSrc)) {
        Write-Host "Session missing: $SessionSrc"
        return
    }
    Get-ChildItem $SessionSrc -Force | Where-Object {
        $_.Name -notmatch '\.(lock|tmp)$' -and $_.Name -ne "terminal"
    } | ForEach-Object {
        $dest = Join-Path $destRoot $_.Name
        if ($_.PSIsContainer) {
            robocopy $_.FullName $dest /E /COPY:DT /R:1 /W:1 /NFL /NDL /NJH /NJS /nc /ns /np /XF *.lock *.tmp | Out-Null
        } else {
            Copy-Item -Force $_.FullName $dest
        }
    }
    $plan = Join-Path $SessionSrc "plan.md"
    if (Test-Path $plan) { Copy-Item -Force $plan (Join-Path $LeafRoot "plan.md") }
}

foreach ($t in $targets) { Copy-ThemeTo $t }

Write-Host "Uploads → share uploads/"
$localUploads = Join-Path $Root "wordpress\wp-content\uploads"
$uploadsDst = Join-Path $Share "uploads"
if ((Test-Path $localUploads) -and (Test-Path $uploadsDst)) {
    robocopy $localUploads $uploadsDst /E /XO /COPY:DT /R:1 /W:1 /NFL /NDL /NJH /NJS /nc /ns /np /XD _export-leaf /XF _kcj-export.xml | Out-Null
    Write-Host "  robocopy=$LastExitCode"
}

Write-Host "Session snapshot..."
foreach ($t in $targets) { Copy-SessionTo $t }

Write-Host "WP-CLI export..."
$wxrCopied = $false
cmd /c "docker info >NUL 2>&1"
if ($LASTEXITCODE -eq 0) {
    & docker compose --profile cli run --rm --no-TTY wpcli wp export --post_type=page,kcj_hero --dir=/var/www/html/wp-content/uploads --filename_format=_kcj-export.xml
    $localWxr = Join-Path $localUploads "_kcj-export.xml"
    if (Test-Path $localWxr) {
        foreach ($t in $targets) {
            Copy-Item -Force $localWxr (Join-Path $t "export\pages-heroes.xml")
        }
        Remove-Item -Force $localWxr -ErrorAction SilentlyContinue
        $wxrCopied = $true
    }
    $optFile = Join-Path $env:TEMP "kcj-options.json"
    & docker compose --profile cli run --rm --no-TTY wpcli wp option list --search=kcj_* --format=json > $optFile
    if (Test-Path $optFile) {
        foreach ($t in $targets) {
            Copy-Item -Force $optFile (Join-Path $t "export\kcj-options.json")
        }
    }
} else {
    Write-Host "Docker not running; skipped WXR"
}

foreach ($t in $targets) {
    Copy-Item -Force (Join-Path $PSScriptRoot "export-to-beast.ps1") (Join-Path $t "export-to-beast.ps1") -ErrorAction SilentlyContinue
    Copy-Item -Force (Join-Path $PSScriptRoot "apply-on-beast.sh") (Join-Path $t "apply-on-beast.sh") -ErrorAction SilentlyContinue
}

$handoff = @"
# kcjdrama handoff (BeeLink → Beast)

Export leaf. WordPress core is not in here.

On the share (uploads is the only writable folder from BeeLink):

``/mnt/drive-a/wp-dev/kcjdrama/uploads/_export-leaf/``

Also on BeeLink: ``C:\Scripts\wordpress\export-leaf\``

- Theme: ``theme/``
- Media: ``/mnt/drive-a/wp-dev/kcjdrama/uploads/``
- Session: ``session/019ffe4d-c606-77b1-8ce9-c25e0e9f57de/``
- Plan: ``plan.md``
- Apply: ``apply-on-beast.sh``

BeeLink = secondary Docker localhost:8080
Beast = primary existing WordPress localhost:8080

## Resume this thread in native Grok (no Hermes)

``````bash
WORK="/mnt/drive-a/wp-dev/kcjdrama"
# or the Beast WP root that contains wp-config.php

ENC=`$(python3 -c "import urllib.parse,sys; print(urllib.parse.quote(sys.argv[1], safe=''))" "`$WORK")
mkdir -p ~/.grok/sessions/"`$ENC"
cp -a /mnt/drive-a/wp-dev/kcjdrama/uploads/_export-leaf/session/019ffe4d-c606-77b1-8ce9-c25e0e9f57de \
      ~/.grok/sessions/"`$ENC"/
cd "`$WORK"
grok --resume 019ffe4d-c606-77b1-8ce9-c25e0e9f57de
``````

If resume does not list the thread, start Grok in that workspace and open HANDOFF.md + plan.md.

## Apply theme to Beast WordPress

``````bash
export WP_ROOT=/path/to/wordpress
export LEAF=/mnt/drive-a/wp-dev/kcjdrama/uploads/_export-leaf
export SHARE=/mnt/drive-a/wp-dev/kcjdrama
bash "`$LEAF/apply-on-beast.sh"
``````

Do not run a full ``wp db import`` on a site you care about.

Packed: $(Get-Date -Format "yyyy-MM-dd HH:mm")
WXR exported: $wxrCopied
"@

foreach ($t in $targets) {
    Set-Content -Path (Join-Path $t "HANDOFF.md") -Value $handoff -Encoding UTF8
}

Write-Host ""
Write-Host "Local leaf: $LocalLeaf"
if ($ShareWritable) { Write-Host "Share leaf: $ShareLeaf" }
Get-ChildItem $LocalLeaf | Format-Table Name, Mode
