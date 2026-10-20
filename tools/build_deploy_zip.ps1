# M.O.R.P.H.I.C - build a clean upload package for cPanel
# ============================================================================
# Creates a zip that contains only what the live site needs: no .git, no SQL
# dumps, no docs, no CLI tools. Hidden .htaccess files ARE included.
#
# Usage (from anywhere):
#   powershell -ExecutionPolicy Bypass -File tools\build_deploy_zip.ps1
#   powershell -ExecutionPolicy Bypass -File tools\build_deploy_zip.ps1 -Destination C:\tmp\morphic.zip
#
param(
	[string]$Destination = (Join-Path (Split-Path -Parent $PSScriptRoot) "dist\morphic-hosting.zip")
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$stage = Join-Path $env:TEMP ("morphic-deploy-" + [guid]::NewGuid().ToString("N"))

# Everything the browser must never receive.
$excludeDirs = @(".git", "tools", "dist")
$excludeFiles = @(
	"database.sql",
	"database_hosting.sql",
	"AGENTS.md",
	"DEPLOY.md",
	"README.md",
	"design_system.md",
	"composer.json",
	"composer.lock",
	".gitignore",
	".editorconfig"
)

Write-Host "Staging $root ..."
New-Item -ItemType Directory -Path $stage | Out-Null

Get-ChildItem -LiteralPath $root -Force | ForEach-Object {
	if ($excludeDirs -contains $_.Name) { return }
	Copy-Item -LiteralPath $_.FullName -Destination $stage -Recurse -Force
}

foreach ($name in $excludeFiles) {
	$path = Join-Path $stage $name
	if (Test-Path -LiteralPath $path) { Remove-Item -LiteralPath $path -Force }
}

$destDir = Split-Path -Parent $Destination
if (-not (Test-Path -LiteralPath $destDir)) {
	New-Item -ItemType Directory -Path $destDir | Out-Null
}
if (Test-Path -LiteralPath $Destination) {
	Remove-Item -LiteralPath $Destination -Force
}

Write-Host "Zipping to $Destination ..."
Compress-Archive -Path (Join-Path $stage "*") -DestinationPath $Destination -CompressionLevel Optimal
Remove-Item -LiteralPath $stage -Recurse -Force

$size = [math]::Round((Get-Item -LiteralPath $Destination).Length / 1MB, 2)
Write-Host "OK: $Destination ($size MB)"
Write-Host "Upload the zip to public_html, extract it there, then follow DEPLOY.md."
