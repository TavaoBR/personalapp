#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Script de Backup Automatizado: MySQL + Uploads Locais (Tenants)
# ==============================================================================

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${PROJECT_DIR}/backups"
TIMESTAMP="$(date +'%Y%m%d_%H%M%S')"
TARGET_DIR="${BACKUP_DIR}/${TIMESTAMP}"

DB_USER="${DB_USER:-personal_user}"
DB_PASSWORD="${DB_PASSWORD:-personal_pass}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3307}"
DB_NAME="${DB_NAME:-personalapp}"

STORAGE_PATH="${PROJECT_DIR}/api/var/storage"
RETENTION_DAYS=7

echo "[+] Iniciando rotina de backup: ${TIMESTAMP}"
mkdir -p "${TARGET_DIR}"

# 1. Backup do MySQL
echo "[+] Exportando banco de dados ${DB_NAME}..."
if command -v mysqldump &> /dev/null; then
    MYSQL_PWD="${DB_PASSWORD}" mysqldump -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" --single-transaction --quick "${DB_NAME}" | gzip > "${TARGET_DIR}/database.sql.gz"
    echo "[✓] Dump do MySQL gerado com sucesso: ${TARGET_DIR}/database.sql.gz"
elif command -v docker &> /dev/null; then
    docker compose exec -T mysql mysqldump -u "${DB_USER}" -p"${DB_PASSWORD}" --single-transaction --quick "${DB_NAME}" | gzip > "${TARGET_DIR}/database.sql.gz"
    echo "[✓] Dump do MySQL via Docker gerado: ${TARGET_DIR}/database.sql.gz"
else
    echo "[!] mysqldump ou docker não encontrado no PATH. Pulando dump de banco."
fi

# 2. Backup do Volume de Uploads
echo "[+] Compactando arquivos de armazenamento local dos tenants..."
if [ -d "${STORAGE_PATH}" ]; then
    tar -czf "${TARGET_DIR}/uploads.tar.gz" -C "${STORAGE_PATH}" .
    echo "[✓] Uploads compactados com sucesso: ${TARGET_DIR}/uploads.tar.gz"
else
    echo "[!] Diretório de uploads não encontrado em ${STORAGE_PATH}."
fi

# 3. Metadados do Backup
cat <<EOF > "${TARGET_DIR}/metadata.json"
{
  "timestamp": "${TIMESTAMP}",
  "db_name": "${DB_NAME}",
  "storage_source": "${STORAGE_PATH}",
  "created_at": "$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
}
EOF

# 4. Rotação e Retenção de Backups
echo "[+] Aplicando política de retenção de ${RETENTION_DAYS} dias..."
find "${BACKUP_DIR}" -mindepth 1 -maxdepth 1 -type d -mtime +"${RETENTION_DAYS}" -exec rm -rf {} +
echo "[✓] Rotina de backup finalizada com sucesso em ${TARGET_DIR}."
