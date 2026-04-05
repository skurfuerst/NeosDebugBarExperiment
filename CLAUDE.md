# Neos Debug Bar - Development Setup

## Test Environment Setup

### 1. Create a Neos development distribution

```bash
cd /home/user
COMPOSER_ALLOW_SUPERUSER=1 composer create-project --stability dev neos/neos-development-distribution:9.0.x-dev neos-test-instance
```

### 2. Add the package as a local path dependency

```bash
cd /home/user/neos-test-instance
COMPOSER_ALLOW_SUPERUSER=1 composer config repositories.debugbar path /home/user/NeosDebugBarExperiment
COMPOSER_ALLOW_SUPERUSER=1 composer require sandstorm/neos-debug-bar:@dev
```

### 3. Start MariaDB via Docker

```bash
docker run -d --name neos-db \
  -e MYSQL_ROOT_PASSWORD=neos \
  -e MYSQL_DATABASE=neos \
  -e MYSQL_USER=neos \
  -e MYSQL_PASSWORD=neos \
  -p 13306:3306 \
  mariadb:10.11
```

### 4. Configure database connection

Create `Configuration/Development/Settings.yaml`:

```yaml
Neos:
  Flow:
    persistence:
      backendOptions:
        driver: 'pdo_mysql'
        host: '127.0.0.1'
        port: 13306
        dbname: 'neos'
        user: 'neos'
        password: 'neos'
```

### 5. Set up Neos

```bash
cd /home/user/neos-test-instance
FLOW_CONTEXT=Development ./flow doctrine:migrate
FLOW_CONTEXT=Development ./flow cr:setup
FLOW_CONTEXT=Development ./flow site:importall --package-key Neos.Demo
FLOW_CONTEXT=Development ./flow user:create --roles Administrator admin password Admin User
```

### 6. Start the development server

```bash
cd /home/user/neos-test-instance
FLOW_CONTEXT=Development php -S 127.0.0.1:8081 -t Web/ Web/index.php
```

### 7. Test

- **Neos demo site**: http://127.0.0.1:8081/ (debug bar should appear at bottom)
- **Debug bar test page**: http://127.0.0.1:8081/debugbar-test (minimal HTML page)
- **Neos backend**: http://127.0.0.1:8081/neos/ (login: admin / password)

### Clearing caches after code changes

```bash
rm -rf Data/Temporary/Development/Cache/Code/Flow_Object_Classes/Sandstorm_*
```

Or for a full cache clear:

```bash
rm -rf Data/Temporary/Development/
```

## Architecture

- **Package namespace**: `Sandstorm\NeosDebugBar`
- **Middleware**: `Classes/Http/DebugBarMiddleware.php` - PSR-15 middleware at position `start 200` (outermost)
- **Configuration**: `Configuration/Settings.yaml` - registers middleware and `enabled` flag
- **Test route**: `Configuration/Routes.yaml` - `/debugbar-test` endpoint for quick testing
- **Assets**: Rendered inline (CSS/JS embedded in HTML) to avoid needing to serve vendor files
