param(
    [string]$OutputPath = ".\classjournal.zip"
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$stagingRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("classjournal-build-" + [System.Guid]::NewGuid().ToString("N"))
$stagingPlugin = Join-Path $stagingRoot "classjournal"
$archivePath = if ([System.IO.Path]::IsPathRooted($OutputPath)) {
    $OutputPath
} else {
    Join-Path $root $OutputPath
}

$requiredPaths = @(
    "version.php",
    "lib.php",
    "mod_form.php",
    "db\install.xml",
    "lang\en\classjournal.php"
)

try {
    foreach ($relativePath in $requiredPaths) {
        $path = Join-Path $root $relativePath
        if (-not (Test-Path -LiteralPath $path)) {
            throw "Required plugin file is missing: $relativePath"
        }
    }

    $php = Get-Command php -ErrorAction SilentlyContinue
    if ($php) {
        Get-ChildItem -LiteralPath $root -Recurse -File -Filter "*.php" |
            Where-Object { $_.FullName -notmatch "\\\.git\\" } |
            ForEach-Object {
                & $php.Source -l $_.FullName | Out-Host
                if ($LASTEXITCODE -ne 0) {
                    throw "PHP syntax check failed: $($_.FullName)"
                }
            }
    } else {
        Write-Host "PHP is not available in PATH; skipping php -l checks."
    }

    New-Item -ItemType Directory -Path $stagingPlugin | Out-Null
    Get-ChildItem -LiteralPath $root -Force |
        Where-Object {
            $_.Name -notin @(".git", ".dist") -and
            $_.FullName -ne $stagingRoot -and
            $_.Extension -ne ".zip"
        } |
        ForEach-Object {
            Copy-Item -LiteralPath $_.FullName -Destination $stagingPlugin -Recurse -Force
        }

    $archiveParent = Split-Path -Parent $archivePath
    if ($archiveParent -and -not (Test-Path -LiteralPath $archiveParent)) {
        New-Item -ItemType Directory -Path $archiveParent | Out-Null
    }

    if (Test-Path -LiteralPath $archivePath) {
        Remove-Item -LiteralPath $archivePath -Force
    }

    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $zip = [System.IO.Compression.ZipFile]::Open($archivePath, [System.IO.Compression.ZipArchiveMode]::Create)
    try {
        Get-ChildItem -LiteralPath $stagingPlugin -Recurse -File |
            ForEach-Object {
                $relativePath = $_.FullName.Substring($stagingRoot.Length + 1)
                $entryName = $relativePath.Replace([System.IO.Path]::DirectorySeparatorChar, '/')
                [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $entryName) | Out-Null
            }
    } finally {
        $zip.Dispose()
    }

    $entries = tar -tf $archivePath
    if (-not $entries -or ($entries | Where-Object { $_ -notmatch "^classjournal/" } | Select-Object -First 1)) {
        throw "Archive structure is invalid. Every entry must be under classjournal/."
    }
    Add-Type -AssemblyName System.IO.Compression
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    $checkzip = [System.IO.Compression.ZipFile]::OpenRead($archivePath)
    try {
        if ($checkzip.Entries | Where-Object { $_.FullName -match "\\" } | Select-Object -First 1) {
            throw "Archive contains Windows backslash paths. ZIP entries must use forward slashes."
        }
    } finally {
        $checkzip.Dispose()
    }
    if ($entries | Where-Object { $_ -match "\.zip$" } | Select-Object -First 1) {
        throw "Archive contains a nested ZIP file."
    }

    Write-Host "Built $archivePath"
} finally {
    if (Test-Path -LiteralPath $stagingRoot) {
        Remove-Item -LiteralPath $stagingRoot -Recurse -Force
    }
}
