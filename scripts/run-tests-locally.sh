#!/usr/bin/env bash
#
# Run Behat behavioral tests locally.
#
# Prerequisites:
#   - Docker (for MariaDB)
#   - PHP >= 8.2 with extensions: mbstring, xml, pdo_mysql, intl
#   - Composer v2
#
# Usage:
#   bash scripts/run-tests-locally.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PACKAGE_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
DIST_DIR="${NEOS_DIST_DIR:-/home/user/neos-test-instance}"
DB_CONTAINER="neos-behat-db"
DB_PORT=13306
DB_NAME="neos_testing_behat"
DB_USER="neos"
DB_PASSWORD="neos"
FLOW_CONTEXT="Testing/Behat"

echo "=== Neos Debug Bar - Behavioral Test Runner ==="
echo ""

# ── 1. Ensure MariaDB is running ────────────────────────────────────────────
if docker ps --format '{{.Names}}' | grep -q "^${DB_CONTAINER}$"; then
    echo "[OK] MariaDB container '${DB_CONTAINER}' is running."
else
    echo "[..] Starting MariaDB container '${DB_CONTAINER}'..."
    docker rm -f "${DB_CONTAINER}" 2>/dev/null || true
    docker run -d --name "${DB_CONTAINER}" \
        -e MYSQL_ROOT_PASSWORD="${DB_PASSWORD}" \
        -e MYSQL_DATABASE="${DB_NAME}" \
        -e MYSQL_USER="${DB_USER}" \
        -e MYSQL_PASSWORD="${DB_PASSWORD}" \
        -p "${DB_PORT}:3306" \
        mariadb:10.11

    echo "[..] Waiting for MariaDB to be ready..."
    for i in $(seq 1 30); do
        if docker exec "${DB_CONTAINER}" mysqladmin ping -h127.0.0.1 --silent 2>/dev/null; then
            break
        fi
        sleep 1
    done
    echo "[OK] MariaDB is ready."
fi

# Ensure the test database exists
docker exec "${DB_CONTAINER}" mysql -uroot -p"${DB_PASSWORD}" \
    -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;" 2>/dev/null

# ── 2. Create Neos distribution if needed ────────────────────────────────────
if [ ! -d "${DIST_DIR}" ]; then
    echo "[..] Creating Neos distribution at ${DIST_DIR}..."
    COMPOSER_ALLOW_SUPERUSER=1 composer create-project --no-interaction \
        --stability dev neos/neos-development-distribution:9.0.x-dev "${DIST_DIR}"
fi

# ── 3. Link the debug bar package ────────────────────────────────────────────
echo "[..] Linking debug bar package..."
cd "${DIST_DIR}"
COMPOSER_ALLOW_SUPERUSER=1 composer config repositories.debugbar path "${PACKAGE_DIR}"
COMPOSER_ALLOW_SUPERUSER=1 composer require --no-interaction sandstorm/neos-debug-bar:@dev
COMPOSER_ALLOW_SUPERUSER=1 composer require --dev --no-interaction neos/behat:^9.0 behat/behat:^3.13

# ── 4. Configure database for Testing/Behat ──────────────────────────────────
echo "[..] Writing Testing/Behat database configuration..."
mkdir -p "${DIST_DIR}/Configuration/Testing/Behat"
cat > "${DIST_DIR}/Configuration/Testing/Behat/Settings.yaml" <<YAML
Neos:
  Flow:
    persistence:
      backendOptions:
        driver: 'pdo_mysql'
        host: '127.0.0.1'
        port: ${DB_PORT}
        dbname: '${DB_NAME}'
        user: '${DB_USER}'
        password: '${DB_PASSWORD}'
Sandstorm:
  NeosDebugBar:
    enabled: true
YAML

# ── 5. Run doctrine migrations ───────────────────────────────────────────────
echo "[..] Running doctrine migrations..."
cd "${DIST_DIR}"
FLOW_CONTEXT="${FLOW_CONTEXT}" ./flow doctrine:migrate

# ── 6. Warm up caches ───────────────────────────────────────────────────────
echo "[..] Warming up caches..."
FLOW_CONTEXT="${FLOW_CONTEXT}" ./flow flow:cache:warmup

# ── 7. Run Behat ─────────────────────────────────────────────────────────────
echo ""
echo "=== Running Behat tests ==="
echo ""
cd "${DIST_DIR}"
FLOW_CONTEXT="${FLOW_CONTEXT}" ./bin/behat \
    -c "Packages/Application/Sandstorm.NeosDebugBar/Tests/Behavior/behat.yml.dist" \
    --no-interaction -v "$@"

echo ""
echo "=== Done ==="
