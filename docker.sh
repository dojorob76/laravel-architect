#!/bin/bash

# Laravel Architect Docker Helper Script
# Usage: ./docker.sh [command] [options]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Helper functions
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
}

# Build containers
build() {
    print_info "Building Docker containers..."
    docker-compose build --no-cache
    print_success "Containers built successfully"
}

# Start containers
up() {
    print_info "Starting Docker containers..."
    docker-compose up -d
    print_info "Waiting for databases to be ready..."
    sleep 10
    print_success "Containers started successfully"
}

# Stop containers
down() {
    print_info "Stopping Docker containers..."
    docker-compose down
    print_success "Containers stopped"
}

# Restart containers
restart() {
    down
    up
}

# Install/update Composer dependencies
composer_install() {
    print_info "Installing Composer dependencies..."
    docker-compose exec php composer install
    print_success "Dependencies installed"
}

# Update Composer dependencies
composer_update() {
    print_info "Updating Composer dependencies..."
    docker-compose exec php composer update
    print_success "Dependencies updated"
}

# Run all tests with default database (SQLite)
test() {
    print_info "Running tests with SQLite..."
    docker-compose exec php vendor/bin/pest "${@}"
}

# Run tests with MySQL
test_mysql() {
    print_info "Running tests with MySQL..."
    docker-compose exec -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_PORT=3306 -e DB_DATABASE=laravel_architect_test -e DB_USERNAME=laravel -e DB_PASSWORD=secret php vendor/bin/pest "${@}"
}

# Run tests with PostgreSQL
test_postgres() {
    print_info "Running tests with PostgreSQL..."
    docker-compose exec -e DB_CONNECTION=pgsql -e DB_HOST=postgres -e DB_PORT=5432 -e DB_DATABASE=laravel_architect_test -e DB_USERNAME=laravel -e DB_PASSWORD=secret php vendor/bin/pest "${@}"
}

# Run tests with all databases
test_all() {
    print_info "Running tests with ALL databases..."

    local sqlite_status=0
    local mysql_status=0
    local postgres_status=0

    echo ""
    print_info "=== Testing with SQLite ==="
    set +e  # Temporarily disable exit on error
    docker-compose exec -T php vendor/bin/pest "${@}"
    sqlite_status=$?
    set -e

    if [ $sqlite_status -eq 0 ]; then
        print_success "SQLite tests passed"
    else
        print_error "SQLite tests failed (exit code: $sqlite_status)"
        return 1
    fi

    echo ""
    print_info "=== Testing with MySQL ==="
    set +e
    docker-compose exec -T -e DB_CONNECTION=mysql -e DB_HOST=mysql -e DB_PORT=3306 -e DB_DATABASE=laravel_architect_test -e DB_USERNAME=laravel -e DB_PASSWORD=secret php vendor/bin/pest "${@}"
    mysql_status=$?
    set -e

    if [ $mysql_status -eq 0 ]; then
        print_success "MySQL tests passed"
    else
        print_error "MySQL tests failed (exit code: $mysql_status)"
        return 1
    fi

    echo ""
    print_info "=== Testing with PostgreSQL ==="
    set +e
    docker-compose exec -T -e DB_CONNECTION=pgsql -e DB_HOST=postgres -e DB_PORT=5432 -e DB_DATABASE=laravel_architect_test -e DB_USERNAME=laravel -e DB_PASSWORD=secret php vendor/bin/pest "${@}"
    postgres_status=$?
    set -e

    if [ $postgres_status -eq 0 ]; then
        print_success "PostgreSQL tests passed"
    else
        print_error "PostgreSQL tests failed (exit code: $postgres_status)"
        return 1
    fi

    echo ""
    print_success "All database tests completed successfully!"
}

# Access PHP container shell
shell() {
    print_info "Accessing PHP container shell..."
    docker-compose exec php sh
}

# Access MySQL CLI
mysql_cli() {
    print_info "Accessing MySQL CLI..."
    docker-compose exec mysql mysql -uroot -proot laravel_architect_test
}

# Access PostgreSQL CLI
postgres_cli() {
    print_info "Accessing PostgreSQL CLI..."
    docker-compose exec postgres psql -U laravel -d laravel_architect_test
}

# Show logs
logs() {
    docker-compose logs -f "${@}"
}

# Clean up (remove volumes)
clean() {
    print_info "Cleaning up Docker volumes and containers..."
    docker-compose down -v
    print_success "Cleanup complete"
}

# Show status
status() {
    docker-compose ps
}

# Show help
help() {
    cat << EOF
Laravel Architect Docker Helper

Usage: ./docker.sh [command] [options]

Commands:
    build               Build Docker containers
    up                  Start containers
    down                Stop containers
    restart             Restart containers

    composer-install    Install Composer dependencies
    composer-update     Update Composer dependencies

    test [options]      Run PEST tests with SQLite
    test-mysql [opts]   Run PEST tests with MySQL
    test-postgres [opts] Run PEST tests with PostgreSQL
    test-all [options]  Run tests with ALL databases

    shell               Access PHP container shell
    mysql-cli           Access MySQL command line
    postgres-cli        Access PostgreSQL command line

    logs [service]      Show container logs
    status              Show container status
    clean               Remove containers and volumes

    help                Show this help message

Examples:
    ./docker.sh up                          # Start all containers
    ./docker.sh test                        # Run all tests with SQLite
    ./docker.sh test-mysql --filter=Model  # Run specific MySQL tests
    ./docker.sh test-all                    # Run tests with all databases
    ./docker.sh shell                       # Access PHP container
    ./docker.sh mysql-cli                   # Access MySQL database

EOF
}

# Main command router
main() {
    check_docker

    case "${1:-help}" in
        build)
            build
            ;;
        up|start)
            up
            ;;
        down|stop)
            down
            ;;
        restart)
            restart
            ;;
        composer-install|install)
            composer_install
            ;;
        composer-update|update)
            composer_update
            ;;
        test)
            shift
            test "$@"
            ;;
        test-mysql|mysql-test)
            shift
            test_mysql "$@"
            ;;
        test-postgres|postgres-test)
            shift
            test_postgres "$@"
            ;;
        test-all|all-tests)
            shift
            test_all "$@"
            ;;
        shell|bash|sh)
            shell
            ;;
        mysql-cli|mysql)
            mysql_cli
            ;;
        postgres-cli|postgres|psql)
            postgres_cli
            ;;
        logs)
            shift
            logs "$@"
            ;;
        status|ps)
            status
            ;;
        clean|cleanup)
            clean
            ;;
        help|--help|-h)
            help
            ;;
        *)
            print_error "Unknown command: $1"
            echo ""
            help
            exit 1
            ;;
    esac
}

main "$@"
