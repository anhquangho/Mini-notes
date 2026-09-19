param(
    [string]$WordPressZip = "$env:USERPROFILE\Downloads\wordpress-7.1.zip",
    [string]$Xampp = "C:\xampp",
    [string]$Database = "wp_mini_notes",
    [string]$DbUser = "root"
)
$ErrorActionPreference = "Stop"
$target = Join-Path $Xampp "htdocs\wp-mini-notes"
$php = Join-Path $Xampp "php\php.exe"
if (!(Test-Path -LiteralPath $php)) { throw "PHP not found: $php" }
if (!(Test-Path -LiteralPath $WordPressZip)) { throw "Pass -WordPressZip with the official WordPress ZIP path." }
if (Test-Path -LiteralPath $target) { throw "Target already exists. Installer will not overwrite: $target" }
# Start Apache (8080) and MySQL (3306) in XAMPP before running.
$mysql = [Net.Sockets.TcpClient]::new()
try {
    $mysql.Connect("127.0.0.1", 3306)
} catch {
    throw "MySQL is not ready. Start MySQL in XAMPP first."
} finally {
    $mysql.Dispose()
}
$stage = Join-Path $PSScriptRoot ("work-install-" + [guid]::NewGuid().ToString("N"))
New-Item -ItemType Directory -Path $stage | Out-Null
Expand-Archive -LiteralPath $WordPressZip -DestinationPath $stage
$core = Join-Path $stage "wordpress"
if (!(Test-Path -LiteralPath (Join-Path $core "wp-load.php"))) { throw "ZIP must contain the official wordpress folder." }
# Resolve and verify both paths before moving a directory.
$resolvedCore = [IO.Path]::GetFullPath($core)
$resolvedStage = [IO.Path]::GetFullPath($stage).TrimEnd('\') + '\'
$resolvedTarget = [IO.Path]::GetFullPath($target)
$resolvedHtdocs = [IO.Path]::GetFullPath((Join-Path $Xampp "htdocs")).TrimEnd('\') + '\'
if (!$resolvedCore.StartsWith($resolvedStage, [StringComparison]::OrdinalIgnoreCase) -or !$resolvedTarget.StartsWith($resolvedHtdocs, [StringComparison]::OrdinalIgnoreCase)) { throw "Unexpected installation path." }
Copy-Item -LiteralPath (Join-Path $PSScriptRoot "wp-content\themes\mini-notes") -Destination (Join-Path $core "wp-content\themes") -Recurse
Copy-Item -LiteralPath (Join-Path $PSScriptRoot "wp-content\plugins\mini-notes-core") -Destination (Join-Path $core "wp-content\plugins") -Recurse
Move-Item -LiteralPath $resolvedCore -Destination $resolvedTarget
$env:MN_ROOT = $resolvedTarget
$env:MN_URL = "http://localhost:8080/wp-mini-notes"
$env:MN_DB = $Database
$env:MN_DB_USER = $DbUser
# If MySQL has a password, set MN_DB_PASS in this terminal before running; never commit it.
$env:MN_ACCESS_FILE = Join-Path $PSScriptRoot "LOCAL-ACCESS.json"
try {
    & $php (Join-Path $PSScriptRoot "tools\setup.php")
    if ($LASTEXITCODE -ne 0) { throw "Setup failed. Review the error; existing data is never dropped." }
    Write-Host "Ready: $env:MN_URL"
    Write-Host "Local credentials: $env:MN_ACCESS_FILE (keep private; do not share)"
} finally {
    Remove-Item Env:\MN_ROOT,Env:\MN_URL,Env:\MN_DB,Env:\MN_DB_USER,Env:\MN_ACCESS_FILE -ErrorAction SilentlyContinue
}
