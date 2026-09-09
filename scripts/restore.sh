#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Script de Restauração de Backup: MySQL + Uploads Locais (Tenants)
# ==============================================================================

if [ -z "${1:-}" ]; then
    echo "Uso: $0 <caminho_do_diretorio_de_backup>"
    echo "Exemplo: $0 backups/20260909_120000"
    exit 1
fi

BACKUP_SOURCE="$1"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STORAGE_PATH="${PROJECT_DIR}/api/var/storage"

DB_USER="${DB_USER:-personal_user}"
DB_PASSWORD="${DB_PASSWORD:-personal_pass}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3307}"
DB_NAME="${DB_NAME:-personalapp}"

echo "[+] Restaurando a partir de: ${BACKUP_SOURCE}"

# 1. Restaurar MySQL
if [ -f "${BACKUP_SOURCE}/database.sql.gz" ]; then
    echo "[+] Restaurando banco de dados a partir de database.sql.gz..."
    if command -v mysql &> /dev/null; then
        gunzip -c "${BACKUP_SOURCE}/database.sql.gz" | MYSQL_PWD="${DB_PASSWORD}" mysql -h "${DB_HOST}" -P "${DB_PORT}" -u "${DB_USER}" "${DB_NAME}"
        echo "[✓] Banco de dados restaurado com sucesso."
    elif command -v docker &> /dev/null; then
        gunzip -c "${BACKUP_SOURCE}/database.sql.gz" | docker compose exec -T mysql mysql -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}"
        echo "[✓] Banco de dados restaurado via Docker com sucesso."
    fi
elif [ -f "${BACKUP_SOURCE}/database.sql" ]; then
    echo "[+] Restaurando banco de dados a partir de database.sql..."
    if command -v docker &> /dev/null; then
        docker compose exec -T mysql mysql -u "${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" < "${BACKUP_SOURCE}/database.sql"
        echo "[✓] Banco de dados restaurado com sucesso."
    fi
fi

# 2. Restaurar uploads
if [ -f "${BACKUP_SOURCE}/uploads.tar.gz" ]; then
    echo "[+] Restaurando volume de uploads dos tenants..."
    mkdir -p "${STORAGE_PATH}"
    tar -xzf "${BACKUP_SOURCE}/uploads.tar.gz" -C "${STORAGE_PATH}"
    echo "[✓] Volume de uploads restaurado com sucesso em ${STORAGE_PATH}."
fi

echo "[✓] Processo de restauração concluído com sucesso."
