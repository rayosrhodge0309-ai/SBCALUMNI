<#
.SYNOPSIS
    Copies selected files to the production server, backs up the live copies,
    installs the new ones, and clears Laravel's compiled views.

.DESCRIPTION
    Same steps as the manual deploy:
      1. scp each file to the staging folder  /home/ubuntu/alumni-link/<path>
      2. back up the live files in /var/www/html to
         /home/ubuntu/deployment-backups/<label>-<timestamp>/<path>
      3. sudo install the new files into /var/www/html (owner www-data, mode 644)
      4. php artisan view:clear

    Before uploading, it checks that every stylesheet or script a Blade file loads
    with filemtime(public_path(...)) exists locally. A missing file would make
    filemtime() fail and break every page that uses that layout.

.EXAMPLE
    $key = "C:\path\to\your-key.pem"
    $server = "ubuntu@your-server-address"
    .\scripts\deploy-production.ps1 -Key $key -Server $server -Label glass-theme -Files `
        public/css/glass-theme.css, public/css/landing-glass.css, resources/views/layouts/app.blade.php

.EXAMPLE
    # Preview what would happen without touching the server.
    .\scripts\deploy-production.ps1 -Key $key -Server $server -Files public/css/glass-theme.css -DryRun
#>
param(
    [Parameter(Mandatory = $true)] [string]   $Key,
    [Parameter(Mandatory = $true)] [string]   $Server,
    [Parameter(Mandatory = $true)] [string[]] $Files,
    [string] $Label = 'update',
    [string] $StagingRoot = '/home/ubuntu/alumni-link',
    [string] $LiveRoot = '/var/www/html',
    [string] $BackupRoot = '/home/ubuntu/deployment-backups',
    [switch] $DryRun
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

if ($Label -notmatch '^[A-Za-z0-9._-]+$') {
    throw "Label may only contain letters, numbers, dot, dash and underscore."
}

# Normalise to forward-slash relative paths and confirm each file exists.
$relativeFiles = foreach ($file in $Files) {
    $rel = ($file -replace '\\', '/').TrimStart('./')
    if ($rel -match "['`"\s;$]") { throw "Unsupported character in path: $rel" }
    if (-not (Test-Path -LiteralPath $rel -PathType Leaf)) { throw "File not found: $rel" }
    $rel
}

# Safety check: assets referenced via filemtime(public_path('...')) must exist.
$missing = @()
foreach ($rel in $relativeFiles | Where-Object { $_ -like '*.blade.php' }) {
    $refs = Select-String -LiteralPath $rel -Pattern "filemtime\(public_path\('([^']+)'\)\)" -AllMatches |
        ForEach-Object { $_.Matches } | ForEach-Object { $_.Groups[1].Value } | Sort-Object -Unique
    foreach ($asset in $refs) {
        if (-not (Test-Path -LiteralPath (Join-Path 'public' $asset))) {
            $missing += "$rel loads public/$asset, which does not exist locally"
        } elseif ($relativeFiles -notcontains "public/$asset") {
            Write-Host "Note: $rel loads public/$asset. Make sure it is already on the server, or add it to -Files." -ForegroundColor Yellow
        }
    }
}
if ($missing.Count -gt 0) {
    $missing | ForEach-Object { Write-Host "ERROR: $_" -ForegroundColor Red }
    throw "Stopped before uploading: a layout points at a file that is missing."
}

Write-Host "Files to deploy:" -ForegroundColor Cyan
$relativeFiles | ForEach-Object { Write-Host "  $_" }

if ($DryRun) {
    Write-Host "Dry run only. Nothing was uploaded." -ForegroundColor Yellow
    return
}

# 1. Make sure staging folders exist, then upload.
$stagingDirs = $relativeFiles | ForEach-Object { "$StagingRoot/" + (Split-Path $_ -Parent).Replace('\', '/') } | Sort-Object -Unique
& ssh -i $Key $Server ("mkdir -p " + (($stagingDirs | ForEach-Object { "'$_'" }) -join ' '))
if ($LASTEXITCODE -ne 0) { throw "Could not create staging folders on the server." }

foreach ($rel in $relativeFiles) {
    $destDir = "$StagingRoot/" + (Split-Path $rel -Parent).Replace('\', '/') + '/'
    & scp -i $Key $rel "${Server}:$destDir"
    if ($LASTEXITCODE -ne 0) { throw "Upload failed for $rel" }
}

# 2-4. Back up live copies, install new ones, clear compiled views.
$steps = @(
    'set -e',
    "cd $LiveRoot",
    "B=$BackupRoot/$Label-`$(date +%Y%m%d-%H%M%S)"
)
foreach ($rel in $relativeFiles) {
    $dir = (Split-Path $rel -Parent).Replace('\', '/')
    $steps += "sudo mkdir -p `"`$B/$dir`""
    $steps += "if sudo test -f '$rel'; then sudo cp -a '$rel' `"`$B/$dir/`"; fi"
    $steps += "sudo install -D -o www-data -g www-data -m 644 '$StagingRoot/$rel' '$rel'"
}
$steps += 'sudo -u www-data php artisan view:clear'
$steps += 'echo "Backup saved in $B"'
$steps += 'echo DEPLOYED'

& ssh -i $Key $Server ($steps -join '; ')
if ($LASTEXITCODE -ne 0) { throw "Remote install failed. Live files were backed up first; check the backup folder." }

Write-Host "Deployment finished. Open the site and press Ctrl + Shift + R." -ForegroundColor Green
