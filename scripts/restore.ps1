# ==============================================================================
# Script de Restauração de Backup (PowerShell): MySQL + Uploads Locais (Tenants)
# ==============================================================================

param (
    [Parameter(Mandatory=$true)]
    [string]$BackupSource
)

$ProjectDir = Split-Path -Parent $PSScriptRoot
$StoragePath = Join-Path $ProjectDir "api\var\storage"

$DbUser = if ($env:DB_USER) { $env:DB_USER } else { "personal_user" }
$DbPassword = if ($env:DB_PASSWORD) { $env:DB_PASSWORD } else { "personal_pass" }
$DbName = if ($env:DB_NAME) { $env:DB_NAME } else { "personalapp" }

Write-Host "[+] Restaurando backup de: $BackupSource"

$SqlDump = Join-Path $BackupSource "database.sql"
if (Test-Path $SqlDump) {
    Write-Host "[+] Restaurando banco de dados a partir de $SqlDump..."
    try {
        if (Get-Command docker -ErrorAction SilentlyContinue) {
            Get-Content $SqlDump | docker compose exec -T mysql mysql -u "$DbUser" "-p$DbPassword" "$DbName"
            Write-Host "[✓] Banco de dados restaurado via Docker com sucesso."
        }
    } catch {
        Write-Warning "Erro ao restaurar banco de dados: $_"
    }
}

$ZipUploads = Join-Path $BackupSource "uploads.zip"
if (Test-Path $ZipUploads) {
    Write-Host "[+] Descompactando volume de uploads em $StoragePath..."
    if (-not (Test-Path $StoragePath)) {
        New-Item -ItemType Directory -Path $StoragePath -Force | Out-Null
    }
    Expand-Archive -Path $ZipUploads -DestinationPath $StoragePath -Force
    Write-Host "[✓] Arquivos de storage dos tenants restaurados com sucesso."
}

Write-Host "[✓] Restauração concluída com sucesso."
