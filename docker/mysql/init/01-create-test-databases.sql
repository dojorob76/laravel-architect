-- Create additional test database for isolation
CREATE DATABASE IF NOT EXISTS laravel_architect_test_mysql;
GRANT ALL PRIVILEGES ON laravel_architect_test_mysql.* TO 'laravel'@'%';

-- Grant full privileges to test user
GRANT ALL PRIVILEGES ON *.* TO 'laravel'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
