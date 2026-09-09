# ==============================================================================
# Script de Backup Automatizado (PowerShell): MySQL + Uploads Locais (Tenants)
# ==============================================================================

$ProjectDir = Split-Path -Parent $PSScriptRoot
$BackupDir = Join-Path $ProjectDir "backups"
$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$TargetDir = Join-Path $BackupDir $Timestamp

$DbUser = if ($env:DB_USER) { $env:DB_USER } else { "personal_user" }
$DbPassword = if ($env:DB_PASSWORD) { $env:DB_PASSWORD } else { "personal_pass" }
$DbName = if ($env:DB_NAME) { $env:DB_NAME } else { "personalapp" }
$StoragePath = Join-Path $ProjectDir "api\var\storage"

Write-Host "[+] Iniciando rotina de backup: $Timestamp"
New-Item -ItemType Directory -Path $TargetDir -Force | Out-Null

# 1. Backup do MySQL via Docker Compose se disponível
Write-Host "[+] Exportando banco de dados $DbName..."
$SqlDumpPath = Join-Path $TargetDir "database.sql"

try {
    if (Get-Command docker -ErrorAction SilentlyContinue) {
        docker compose exec -T mysql mysqldump -u "$DbUser" "-p$DbPassword" --single-transaction --quick "$DbName" > $SqlDumpPath
        Write-Host "[✓] Dump do MySQL gerado: $SqlDumpPath"
    } else {
        Write-Host "[!] Docker não encontrado. Pulando exportação do banco de dados."
    }
} catch {
    Write-Warning "Falha ao exportar banco de dados: $_"
}

# 2. Compactar uploads
if (Test-Path $StoragePath) {
    Write-Host "[+] Compactando pasta de storage dos tenants..."
    $ZipPath = Join-Path $TargetDir "uploads.zip"
    Compress-Archive -Path "$StoragePath\*" -DestinationPath $ZipPath -Force
    Write-Host "[✓] Arquivo compactado gerado: $ZipPath"
}

# 3. Metadados
$Metadata = @{
    timestamp = $Timestamp
    db_name = $DbName
    created_at = (Get-Date).ToUniversalTime().ToString("yyyy-MM-ddTHH:mm:ssZ")
} | ConvertTo-Json

Set-Content -Path (Join-Path $TargetDir "metadata.json") -Value $Metadata
Write-Host "[✓] Backup finalizado com sucesso em $TargetDir."
