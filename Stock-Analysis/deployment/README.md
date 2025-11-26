# Stock Analysis Application - Docker Deployment

This directory contains files for containerized deployment of the Stock Analysis application.

## Prerequisites

- Docker 20.10+
- Docker Compose 2.0+ (for docker-compose.yml)
- Ansible 2.9+ (for Ansible deployment)

## Quick Start with Docker Compose

### 1. Set Environment Variables

Create a `.env` file in the Stock-Analysis directory:

```bash
# Database passwords
MYSQL_ROOT_PASSWORD=your_secure_root_password
MYSQL_APP_PASSWORD=your_secure_app_password

# API Keys (optional)
ALPHA_VANTAGE_API_KEY=your_api_key
YAHOO_FINANCE_API_KEY=your_api_key

# Deployment options
RUN_MIGRATIONS=true
```

### 2. Build and Start Services

```bash
# Build the application image
docker-compose build

# Start all services
docker-compose up -d

# View logs
docker-compose logs -f app

# Check service status
docker-compose ps
```

### 3. Access the Application

- **Main Application**: http://localhost:8080
- **phpMyAdmin** (if enabled): http://localhost:8081

### 4. Stop Services

```bash
# Stop services
docker-compose down

# Stop and remove volumes (WARNING: deletes data)
docker-compose down -v
```

## Deployment with Ansible

### 1. Prepare Inventory

Edit `deployment/inventory.ini` to specify your target host:

```ini
[stockanalysis]
production-server ansible_host=your-server-ip ansible_user=deploy
```

### 2. Set Variables

Create `deployment/vars.yml`:

```yaml
app_domain: stock-analysis.example.com
mysql_root_password: "{{ vault_mysql_root_password }}"
mysql_app_password: "{{ vault_mysql_app_password }}"
```

### 3. Run Playbook

```bash
# Check connectivity
ansible stockanalysis -i deployment/inventory.ini -m ping

# Run deployment (dry-run)
ansible-playbook -i deployment/inventory.ini deployment/playbook.yml --check

# Run deployment
ansible-playbook -i deployment/inventory.ini deployment/playbook.yml

# Run with tags (e.g., only deploy code)
ansible-playbook -i deployment/inventory.ini deployment/playbook.yml --tags application
```

## Dockerfile Details

The Dockerfile creates an Ubuntu 22.04-based image with:

- **Apache 2.4** - Web server
- **PHP 8.2** - Application runtime
  - Extensions: mysql, xml, curl, gd, mbstring, zip, opcache, intl, bcmath
- **Python 3.11** - Analysis engine
  - Virtual environment with: pandas, numpy, ta
- **MySQL Client** - Database access
- **Supervisor** - Process management

### Build Arguments

```bash
# Build with custom Python version
docker build --build-arg PYTHON_VERSION=3.10 -t stock-analysis .

# Build with custom PHP version
docker build --build-arg PHP_VERSION=8.1 -t stock-analysis .
```

## Docker Compose Services

### Services

1. **db** - MySQL 8.0 database
   - Port: 3306
   - Volume: mysql_data (persistent)
   - Health check enabled

2. **app** - Stock Analysis application
   - Port: 8080 → 80
   - Depends on: db
   - Volumes: code, data, logs, uploads
   - Health check enabled

3. **phpmyadmin** - Database management (optional)
   - Port: 8081
   - Profile: tools (enable with `--profile tools`)

### Volumes

- `mysql_data` - Database files (persistent)
- `app_data` - Application data (CSV files, cache, sessions)
- `app_logs` - Application and web server logs
- `app_uploads` - User uploaded files

## Configuration Files

### deployment/docker-vhost.conf
Apache virtual host configuration for container.

### deployment/docker-entrypoint.sh
Container startup script:
- Creates directories
- Sets permissions
- Waits for MySQL
- Runs migrations
- Validates environment

### deployment/supervisord.conf
Supervisor configuration:
- Manages Apache process
- Manages cron process

## Ansible Playbook Details

### playbook.yml

Main deployment playbook with tasks:

1. **System Setup**
   - Update packages
   - Install dependencies

2. **Apache Installation**
   - Install Apache 2
   - Enable modules (rewrite, ssl, headers)
   - Configure virtual host

3. **PHP Installation**
   - Add Ondřej Surý PPA
   - Install PHP 8.2 and extensions
   - Configure php.ini

4. **Composer Installation**
   - Download and install globally

5. **Python Installation**
   - Add deadsnakes PPA
   - Install Python 3.11
   - Create virtual environment

6. **MySQL Setup**
   - Install MySQL 8.0
   - Create database and user
   - Set permissions

7. **Application Deployment**
   - Clone/copy application code
   - Install Composer dependencies
   - Install Python dependencies
   - Set permissions

8. **Configuration**
   - Generate .env file
   - Configure Apache virtual host
   - Set up cron jobs

9. **Firewall**
   - Install and configure UFW
   - Allow HTTP, HTTPS, SSH

### Inventory Files

- `inventory.ini` - Host definitions
- Variables can be set in:
  - `inventory.ini` (host vars)
  - `vars.yml` (playbook vars)
  - `host_vars/hostname.yml` (host-specific)
  - `group_vars/stockanalysis.yml` (group-specific)

### Templates

- `vhost.conf.j2` - Apache virtual host template
- `env.j2` - Environment file template

## Environment Variables

### Application

- `APP_ENV` - Environment (production, development, testing)
- `APP_DEBUG` - Debug mode (true/false)
- `APP_ROOT` - Application root directory

### Database

- `DB_HOST` - Database host
- `DB_PORT` - Database port (default: 3306)
- `DB_DATABASE` - Database name
- `DB_USERNAME` - Database user
- `DB_PASSWORD` - Database password

### Python

- `PYTHON_PATH` - Path to Python executable
- `PYTHON_ANALYSIS_SCRIPT` - Path to analysis.py

### Paths

- `LOG_PATH` - Logs directory
- `DATA_PATH` - Data directory
- `TEMP_PATH` - Temporary files
- `UPLOAD_PATH` - User uploads

## Maintenance

### View Logs

```bash
# Docker Compose
docker-compose logs -f app
docker-compose logs -f db

# Docker (container directly)
docker logs -f stock-analysis-app

# Application logs
docker exec stock-analysis-app tail -f /var/www/stock-analysis/logs/app.log

# Apache logs
docker exec stock-analysis-app tail -f /var/log/apache2/stock-analysis-error.log
```

### Execute Commands

```bash
# PHP
docker exec stock-analysis-app php /var/www/stock-analysis/scripts/sync-market-data.php

# Python
docker exec stock-analysis-app /var/www/stock-analysis/venv/bin/python /var/www/stock-analysis/python_analysis/analysis.py

# Shell access
docker exec -it stock-analysis-app /bin/bash

# As www-data user
docker exec -it --user www-data stock-analysis-app /bin/bash
```

### Database Operations

```bash
# Backup database
docker exec stock-analysis-db mysqldump -u root -p stock_analysis > backup.sql

# Restore database
docker exec -i stock-analysis-db mysql -u root -p stock_analysis < backup.sql

# MySQL shell
docker exec -it stock-analysis-db mysql -u root -p
```

### Update Application

```bash
# Pull latest code
git pull

# Rebuild image
docker-compose build app

# Restart service
docker-compose up -d app

# Run migrations
docker exec stock-analysis-app php /var/www/stock-analysis/scripts/migrate.php
```

## Troubleshooting

### Container won't start

```bash
# Check logs
docker-compose logs app

# Check container status
docker-compose ps

# Inspect container
docker inspect stock-analysis-app
```

### Database connection issues

```bash
# Verify DB is running
docker-compose ps db

# Check DB health
docker exec stock-analysis-db mysqladmin ping -h localhost

# Test connection from app container
docker exec stock-analysis-app php -r "new PDO('mysql:host=db;dbname=stock_analysis', 'stock_app', 'password');"
```

### Python environment issues

```bash
# Check Python version
docker exec stock-analysis-app /var/www/stock-analysis/venv/bin/python --version

# Check installed packages
docker exec stock-analysis-app /var/www/stock-analysis/venv/bin/pip list

# Test Python script
docker exec stock-analysis-app /var/www/stock-analysis/venv/bin/python -c "import pandas; import numpy; import ta; print('OK')"
```

### Permission issues

```bash
# Fix permissions
docker exec stock-analysis-app chown -R www-data:www-data /var/www/stock-analysis/logs
docker exec stock-analysis-app chown -R www-data:www-data /var/www/stock-analysis/data
docker exec stock-analysis-app chmod -R 775 /var/www/stock-analysis/logs
docker exec stock-analysis-app chmod -R 775 /var/www/stock-analysis/data
```

## Security Considerations

1. **Change default passwords** in `.env` file
2. **Use environment variables** for sensitive data
3. **Enable SSL/TLS** for production (configure in vhost)
4. **Update firewall rules** to restrict access
5. **Regular backups** of database and data volumes
6. **Keep images updated** - rebuild regularly
7. **Use Docker secrets** for sensitive data in production
8. **Scan images** for vulnerabilities

## Production Recommendations

1. **Use separate database server** - don't run MySQL in container for production
2. **Use orchestration** - Kubernetes, Docker Swarm for high availability
3. **Configure logging** - centralized log aggregation
4. **Monitor performance** - Prometheus, Grafana
5. **Set up CI/CD** - automated testing and deployment
6. **Use reverse proxy** - Nginx or Traefik for SSL termination and load balancing
7. **Implement backups** - automated database and volume backups
8. **Configure alerting** - notify on errors and resource issues

## Support

For issues or questions:
1. Check logs (see Troubleshooting section)
2. Review Docker/Ansible documentation
3. Check GitHub issues
4. Contact development team
