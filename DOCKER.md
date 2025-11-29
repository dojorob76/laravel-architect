# Docker Development Environment

This Laravel Architect package includes a lightweight Docker setup optimized for package development and multi-database testing.

## Overview

The Docker environment provides:
- PHP 8.4 CLI container with all required extensions
- MySQL 8.0 for testing MySQL repositories
- PostgreSQL 16 for testing PostgreSQL repositories
- SQLite support (in-memory and file-based)
- Helper scripts for common development tasks

## ⚠️ Security Notice

**This Docker setup is FOR PACKAGE DEVELOPMENT AND TESTING ONLY.**

This environment is designed for developers contributing to or testing the Laravel Architect package itself. It is NOT intended for production use.

### Important Security Information:

- ✅ **Test credentials are intentionally simple** - These databases only exist for running package tests
- ✅ **Test databases contain zero real data** - Tables are created and destroyed during test runs
- ✅ **Only run on local development machines** - Not designed for production or cloud deployments
- ✅ **Ports are localhost-only** - Database ports are bound to 127.0.0.1 for security
- ❌ **DO NOT use this setup for production applications** - Use proper production database configuration
- ❌ **DO NOT run on publicly accessible servers** - This is a local development tool

### For Laravel Application Developers:

If you're using the Laravel Architect package in your Laravel application (via `composer require`), you do **NOT** need this Docker setup. This is only for package contributors. Your Laravel application should use its own database configuration as normal.

## Prerequisites

- Docker Desktop (or Docker Engine + Docker Compose)
- At least 2GB of available RAM
- 1GB of available disk space

## Configuration (Optional)

The Docker setup works with default test credentials out of the box. If you need to customize them:

```bash
# Copy the example environment file
cp .env.example .env

# Edit .env with your preferred test credentials (optional)
nano .env
```

**Note:** The default credentials in `.env.example` are perfectly fine for most developers. Customization is rarely needed.

## Quick Start

### 1. Build and Start Containers

```bash
./docker.sh build
./docker.sh up
```

### 2. Install Dependencies

```bash
./docker.sh composer-install
```

### 3. Run Tests

```bash
# Run all tests with SQLite (default)
./docker.sh test

# Run tests with MySQL
./docker.sh test-mysql

# Run tests with PostgreSQL
./docker.sh test-postgres

# Run tests with ALL databases
./docker.sh test-all
```

## Available Commands

### Container Management

```bash
./docker.sh build           # Build containers
./docker.sh up              # Start containers
./docker.sh down            # Stop containers
./docker.sh restart         # Restart containers
./docker.sh status          # Show container status
./docker.sh clean           # Remove containers and volumes
```

### Dependency Management

```bash
./docker.sh composer-install    # Install dependencies
./docker.sh composer-update     # Update dependencies
```

### Testing

```bash
# Run all tests with default database (SQLite)
./docker.sh test

# Run specific test file
./docker.sh test tests/Feature/MakeApiModelTest.php

# Run tests with filter
./docker.sh test --filter=repository

# Run tests with MySQL
./docker.sh test-mysql

# Run tests with PostgreSQL
./docker.sh test-postgres

# Run tests with all databases
./docker.sh test-all

# Run specific tests with MySQL
./docker.sh test-mysql --filter=MakeApiRepository
```

### Container Access

```bash
# Access PHP container shell
./docker.sh shell

# Access MySQL CLI
./docker.sh mysql-cli

# Access PostgreSQL CLI
./docker.sh postgres-cli

# View logs
./docker.sh logs
./docker.sh logs php
./docker.sh logs mysql
```

## Architecture

### Services

#### PHP Container
- **Image:** Custom built from `Dockerfile`
- **Base:** PHP 8.4 CLI Alpine
- **Extensions:** PDO, PDO_MySQL, PDO_PostgreSQL, PDO_SQLite, Zip, PCNTL, Intl, MBString
- **User:** Non-root user `laravel` (UID 1000)
- **Volume:** Project root mounted at `/var/www`

#### MySQL Container
- **Image:** `mysql:8.0`
- **Port:** `33060:3306` (host:container)
- **Database:** `laravel_architect_test`
- **User:** `laravel` / `secret`
- **Root Password:** `root`

#### PostgreSQL Container
- **Image:** `postgres:16-alpine`
- **Port:** `54320:5432` (host:container)
- **Database:** `laravel_architect_test`
- **User:** `laravel` / `secret`

### Network

All containers are connected via the `laravel_architect` bridge network, allowing them to communicate using service names as hostnames.

### Volumes

- `mysql_data` - Persists MySQL database data
- `postgres_data` - Persists PostgreSQL database data
- Project directory mounted as delegated volume for better performance

## Testing Strategy

### SQLite Testing (Default)

SQLite is used for fast, in-memory testing of the package scaffolding commands.

**When to use:**
- Rapid test execution
- CI/CD pipelines
- Basic functionality testing

**Command:**
```bash
./docker.sh test
```

### MySQL Testing

Tests MySQL-specific repository implementations and queries.

**When to use:**
- Testing MySQL repository generators
- Validating MySQL-specific SQL syntax
- Testing MySQL-specific features

**Command:**
```bash
./docker.sh test-mysql
```

### PostgreSQL Testing

Tests PostgreSQL-specific repository implementations and queries.

**When to use:**
- Testing PostgreSQL repository generators
- Validating PostgreSQL-specific SQL syntax
- Testing PostgreSQL-specific features

**Command:**
```bash
./docker.sh test-postgres
```

### Comprehensive Testing

Runs the entire test suite against all three databases.

**When to use:**
- Before releases
- Pre-commit verification
- Comprehensive validation

**Command:**
```bash
./docker.sh test-all
```

## Database Configuration

### Connecting from Host Machine

You can connect to the databases from your host machine (e.g., using TablePlus, DBeaver, or command-line clients) using these credentials:

**MySQL:**
- Host: `127.0.0.1` (localhost only - not accessible from other machines)
- Port: `33060`
- Database: `laravel_architect_test`
- Username: `laravel`
- Password: `secret` (default, customizable via `.env`)

**PostgreSQL:**
- Host: `127.0.0.1` (localhost only - not accessible from other machines)
- Port: `54320`
- Database: `laravel_architect_test`
- Username: `laravel`
- Password: `secret` (default, customizable via `.env`)

### Connecting from PHP Container

From within the PHP container, use service names as hostnames:

**MySQL:**
- Host: `mysql`
- Port: `3306`

**PostgreSQL:**
- Host: `postgres`
- Port: `5432`

## Development Workflow

### Typical Development Session

```bash
# 1. Start the environment
./docker.sh up

# 2. Install dependencies (first time only)
./docker.sh composer-install

# 3. Make code changes in your editor

# 4. Run tests
./docker.sh test

# 5. Run database-specific tests if needed
./docker.sh test-mysql
./docker.sh test-postgres

# 6. Stop containers when done
./docker.sh down
```

### Debugging

```bash
# Access PHP container for debugging
./docker.sh shell

# Inside container, you can run:
php -v                      # Check PHP version
composer --version          # Check Composer version
vendor/bin/pest --help      # PEST help

# Check database connections
./docker.sh mysql-cli       # Access MySQL
./docker.sh postgres-cli    # Access PostgreSQL

# View container logs
./docker.sh logs php
./docker.sh logs mysql
./docker.sh logs postgres
```

### Troubleshooting

#### Containers won't start

```bash
# Check Docker is running
docker info

# Check for port conflicts
lsof -i :33060  # MySQL
lsof -i :54320  # PostgreSQL

# Clean up and rebuild
./docker.sh clean
./docker.sh build
./docker.sh up
```

#### Tests failing with database connection errors

```bash
# Check database health
./docker.sh status

# Wait for databases to be ready (they need ~10 seconds)
sleep 10
./docker.sh test-mysql

# Check database logs
./docker.sh logs mysql
./docker.sh logs postgres
```

#### Permission errors

```bash
# Fix vendor directory permissions
sudo chown -R $USER:$USER vendor/

# Or rebuild container
./docker.sh build
```

#### Slow performance on macOS

The setup uses `:delegated` volume mounts for better performance. If still slow:

```bash
# Ensure Docker Desktop has adequate resources:
# Settings > Resources > Advanced
# - CPUs: 4+
# - Memory: 4GB+
# - Swap: 1GB+
```

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Build Docker containers
        run: ./docker.sh build

      - name: Start containers
        run: ./docker.sh up

      - name: Install dependencies
        run: ./docker.sh composer-install

      - name: Run tests with all databases
        run: ./docker.sh test-all

      - name: Stop containers
        run: ./docker.sh down
```

## Performance Considerations

### Resource Usage

- **PHP Container:** ~50MB RAM, negligible CPU when idle
- **MySQL Container:** ~200MB RAM
- **PostgreSQL Container:** ~50MB RAM
- **Total:** ~300MB RAM footprint

### Test Execution Times (Approximate)

- SQLite: ~2-5 seconds (in-memory)
- MySQL: ~5-10 seconds (container overhead)
- PostgreSQL: ~5-10 seconds (container overhead)
- All databases: ~15-30 seconds

## Best Practices

1. **Always run `test-all` before pushing changes**
2. **Use SQLite for rapid iteration during development**
3. **Test MySQL/PostgreSQL when working on repository generators**
4. **Stop containers when not in use** (`./docker.sh down`)
5. **Clean volumes periodically** (`./docker.sh clean`)
6. **Keep Docker Desktop updated** for best performance

## Extending the Setup

### Adding New Database Service

1. Add service to `docker-compose.yml`
2. Create initialization script in `docker/[database]/init/`
3. Add test command to `docker.sh`
4. Create corresponding `phpunit.[database].xml`
5. Update `tests/TestCase.php` to support new connection

### Custom PHP Extensions

Edit `Dockerfile` and add extensions:

```dockerfile
RUN docker-php-ext-install your_extension
```

Then rebuild:

```bash
./docker.sh build
```

## Support

For issues specific to the Docker setup, please check:
1. Docker Desktop is running and up to date
2. Ports 33060 and 54320 are available
3. Docker has adequate resources allocated
4. All files have correct permissions

For package-related issues, see the main README.md.
