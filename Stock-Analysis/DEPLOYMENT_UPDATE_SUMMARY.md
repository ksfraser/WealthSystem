# Deployment Configuration Update - Summary

**Date**: December 4, 2025  
**Commit**: 2ee552f0  
**Branch**: TradingStrategies

## Overview

Updated the Stock Analysis application deployment configuration to include Redis as a second container with full support for flexible deployment options including Docker Compose standalone containers and Kubernetes pods. All configurations have been genericized with environment variables and industry-standard defaults.

## Changes Made

### 1. Docker Compose Updates (`docker-compose.yml`)

#### Added Services

**Redis Cache Server**
- Image: `redis:7-alpine`
- Features:
  - Password authentication (`REDIS_PASSWORD`)
  - Memory limit: 256MB with `allkeys-lru` eviction policy
  - Health checks using `redis-cli`
  - Persistent volume: `redis_data`
  - Configurable port (default: 6379)

**Redis Commander (Management Tool)**
- Image: `rediscommander/redis-commander:latest`
- Optional service via `tools` profile
- Access: http://localhost:8082 (configurable)
- Auto-configured with Redis credentials

#### Genericized Configuration

All hardcoded values replaced with environment variables using `${VAR:-default}` pattern:

| Variable | Default | Purpose |
|----------|---------|---------|
| `APP_NAME` | `stock-analysis` | Application identifier |
| `APP_DOMAIN` | `example.com` | Domain name |
| `APP_PORT` | `8080` | HTTP port |
| `NETWORK_NAME` | `stock-analysis-network` | Docker network |
| `MYSQL_ROOT_PASSWORD` | `changeme` | MySQL root password |
| `MYSQL_DATABASE` | `stock_analysis` | Database name |
| `MYSQL_USER` | `stock_app` | Database user |
| `MYSQL_APP_PASSWORD` | `changeme` | Database password |
| `MYSQL_PORT` | `3306` | MySQL port |
| `REDIS_PASSWORD` | `changeme` | Redis password |
| `REDIS_PORT` | `6379` | Redis port |
| `REDIS_DATABASE` | `0` | Redis database number |
| `PHPMYADMIN_PORT` | `8081` | phpMyAdmin port |
| `REDIS_COMMANDER_PORT` | `8082` | Redis Commander port |

#### Service Dependencies

Updated application service to depend on both database and Redis with health checks:

```yaml
depends_on:
  db:
    condition: service_healthy
  redis:
    condition: service_healthy
```

#### Volumes

Added Redis data persistence:
- `redis_data` - Redis persistent storage

### 2. Dockerfile Updates

**Added PHP Extension**
- Installed `php8.2-redis` for Redis connectivity
- Enables PHP to communicate with Redis cache

### 3. Ansible Playbook Updates (`deployment/playbook.yml`)

#### New Variables

```yaml
app_name: "{{ lookup('env', 'APP_NAME') | default('stock-analysis', true) }}"
app_domain: "{{ lookup('env', 'APP_DOMAIN') | default('example.com', true) }}"
redis_password: "{{ lookup('env', 'REDIS_PASSWORD') | default('changeme', true) }}"
redis_port: "{{ lookup('env', 'REDIS_PORT') | default('6379', true) }}"
redis_maxmemory: "{{ lookup('env', 'REDIS_MAXMEMORY') | default('256mb', true) }}"
```

#### Redis Installation Tasks

1. **Install Redis Server**
   - Packages: `redis-server`, `redis-tools`
   - PHP extension: `php8.2-redis`

2. **Configure Redis**
   - Password authentication (`requirepass`)
   - Port configuration
   - Memory limits (`maxmemory`, `maxmemory-policy`)
   - Bind to localhost only (security)

3. **Start Redis Service**
   - Enabled at boot
   - Handler for configuration changes

#### Updated PHP Installation

Added `php{{ php_version }}-redis` to PHP extensions list.

### 4. Kubernetes Pod Configuration (`deployment/pod.yml`)

Created comprehensive Kubernetes configuration for pod-based deployment:

#### Pod Specification

**Multi-Container Pod** with 3 containers:
1. MySQL Database
2. Redis Cache
3. Application

**Shared Network**: All containers share pod's network namespace (localhost connectivity)

#### Container Configurations

**MySQL Container**
- Image: `mysql:8.0`
- Port: 3306
- Resources: 512Mi-1Gi memory, 250m-500m CPU
- Health check: `mysqladmin ping`
- Volume: `mysql-data` PVC (10Gi)

**Redis Container**
- Image: `redis:7-alpine`
- Port: 6379
- Resources: 256Mi-512Mi memory, 100m-250m CPU
- Health check: `redis-cli ping`
- Volume: `redis-data` PVC (2Gi)
- Memory limit: 256MB with `allkeys-lru` policy

**Application Container**
- Image: `stock-analysis:latest`
- Port: 80
- Resources: 1Gi-2Gi memory, 500m-1000m CPU
- Health checks: HTTP GET on `/`
- Volumes: 3 PVCs (data, logs, uploads)

#### Kubernetes Resources

**ConfigMap** (`stock-analysis-config`)
- Non-sensitive configuration
- APP_NAME, APP_DOMAIN, database names

**Secret** (`stock-analysis-secrets`)
- Base64-encoded passwords
- MySQL root, app, and Redis passwords

**PersistentVolumeClaims** (5 total)
- `mysql-data`: 10Gi
- `redis-data`: 2Gi
- `app-data`: 5Gi
- `app-logs`: 2Gi
- `app-uploads`: 5Gi

**Service** (`stock-analysis-service`)
- Type: LoadBalancer
- Port: 80
- Selector: app=stock-analysis

### 5. Environment Configuration (`.env.example`)

Created comprehensive environment file with 100+ variables organized into sections:

#### Sections

1. **Application Settings** - Name, domain, environment, debug mode
2. **Network Settings** - Docker network configuration
3. **Database Settings** - MySQL configuration
4. **Redis Cache Settings** - Redis configuration
5. **Management Tools Ports** - phpMyAdmin, Redis Commander
6. **Application Paths** - Root, Python, data directories
7. **Migrations** - Database migration settings
8. **API Keys** - Alpha Vantage, Yahoo Finance (optional)
9. **PHP Settings** - Memory, upload limits, execution time
10. **Python Settings** - Version, paths
11. **Timezone** - Default timezone
12. **Logging** - Log level and paths
13. **Session** - Session configuration
14. **Cache** - Cache driver and TTL
15. **Security** - App key, CORS settings
16. **Deployment Settings** - Git, migrations flags
17. **Monitoring** - APM configuration
18. **Backup Settings** - Automated backups
19. **Email Settings** - SMTP for notifications
20. **Alerts** - Alert system configuration
21. **Performance** - Query cache, compression
22. **Development Settings** - Debug tools
23. **Docker Compose Profiles** - Optional services

### 6. Documentation Updates (`deployment/README.md`)

#### New Sections

**Kubernetes Pod Deployment**
- Prerequisites
- Deploy pod instructions
- Access application via port-forward
- Update deployment
- Cleanup commands

**Environment Variables - Expanded**
- Redis configuration section
- Management tools ports
- Networking configuration
- All variables documented with defaults

**Quick Start - Updated**
- Added `.env` configuration step
- Added management tools startup
- Added Redis Commander access URL
- Added service verification steps

**Redis Operations**
- Connect to Redis CLI
- Monitor commands
- Check memory usage
- Flush cache
- Check keys count

**Production Recommendations - Expanded**
- Use managed services (MySQL, Redis)
- Implement auto-scaling (HPA)
- Secrets management (Vault, AWS Secrets Manager)
- Resource limits and requests

## Deployment Options

### Option 1: Docker Compose (Standalone)

```bash
# Core services only
docker-compose up -d

# Access:
# - Application: http://localhost:8080
```

### Option 2: Docker Compose with Tools

```bash
# All services including management tools
docker-compose --profile tools up -d

# Access:
# - Application: http://localhost:8080
# - phpMyAdmin: http://localhost:8081
# - Redis Commander: http://localhost:8082
```

### Option 3: Kubernetes Pod

```bash
# Deploy pod
kubectl apply -f deployment/pod.yml

# Port forward
kubectl port-forward pod/stock-analysis 8080:80

# Access: http://localhost:8080
```

### Option 4: Ansible Automated

```bash
# Deploy to remote server
ansible-playbook -i deployment/inventory.ini deployment/playbook.yml

# Installs: Apache, PHP 8.2, Python 3.11, MySQL, Redis
# Configures: Virtual host, firewall, cron jobs
```

## Configuration Examples

### Minimal Configuration

Create `.env` with just passwords:

```bash
MYSQL_ROOT_PASSWORD=secure_root_pass
MYSQL_APP_PASSWORD=secure_app_pass
REDIS_PASSWORD=secure_redis_pass
```

All other values use sensible defaults.

### Multi-Tenant Configuration

Deploy multiple instances on same host:

```bash
# Instance 1
APP_NAME=client1-analysis
APP_PORT=8080
MYSQL_PORT=3306
REDIS_PORT=6379

# Instance 2
APP_NAME=client2-analysis
APP_PORT=8090
MYSQL_PORT=3307
REDIS_PORT=6380
```

### Production Configuration

```bash
# Production settings
APP_NAME=stock-analysis-prod
APP_DOMAIN=stocks.example.com
APP_ENV=production
APP_DEBUG=false

# Secure passwords
MYSQL_ROOT_PASSWORD=<generate-secure-password>
MYSQL_APP_PASSWORD=<generate-secure-password>
REDIS_PASSWORD=<generate-secure-password>

# Performance
REDIS_MAXMEMORY=512mb
PHP_MEMORY_LIMIT=1024M

# Monitoring
ENABLE_MONITORING=true
APM_SERVER_URL=https://apm.example.com

# Backups
ENABLE_BACKUPS=true
BACKUP_SCHEDULE="0 2 * * *"
BACKUP_RETENTION_DAYS=30
```

## Security Considerations

### Passwords

⚠️ **CRITICAL**: Change all default passwords before deployment:

```bash
# Generate secure passwords
openssl rand -base64 32  # Use for each password

# Update in .env:
MYSQL_ROOT_PASSWORD=<generated-password-1>
MYSQL_APP_PASSWORD=<generated-password-2>
REDIS_PASSWORD=<generated-password-3>
```

### Redis Security

1. **Password Protection**: Redis requires password for all operations
2. **Memory Limits**: 256MB limit prevents memory exhaustion
3. **Network Binding**: Bind to localhost only (via Ansible)
4. **No Persistence**: Cache-only usage, data not critical

### Kubernetes Secrets

```bash
# Create secrets securely
kubectl create secret generic stock-analysis-secrets \
  --from-literal=mysql-root-password=$(openssl rand -base64 32) \
  --from-literal=mysql-password=$(openssl rand -base64 32) \
  --from-literal=redis-password=$(openssl rand -base64 32)

# Verify (passwords will be base64 encoded)
kubectl get secret stock-analysis-secrets -o yaml
```

## Testing

### Verify Redis Connection

```bash
# From application container
docker exec stock-analysis-app php -r "
\$redis = new Redis();
\$redis->connect('redis', 6379);
\$redis->auth('changeme');
echo 'Connected: ' . \$redis->ping() . PHP_EOL;
"
```

### Verify Cache Operations

```bash
# Set a key
docker exec stock-analysis-redis redis-cli -a changeme SET test "Hello Redis"

# Get the key
docker exec stock-analysis-redis redis-cli -a changeme GET test

# Check TTL
docker exec stock-analysis-redis redis-cli -a changeme TTL test
```

### Health Checks

```bash
# Docker Compose
docker-compose ps  # All services should show "healthy"

# Kubernetes
kubectl get pods  # Pod should show "Running" and "3/3" ready
```

## Performance Tuning

### Redis Memory Optimization

```bash
# Increase memory limit for larger workloads
REDIS_MAXMEMORY=512mb

# Change eviction policy
# - allkeys-lru: Evict least recently used keys (default)
# - allkeys-lfu: Evict least frequently used keys
# - volatile-ttl: Evict keys with TTL set
```

### Database Optimization

```bash
# Increase MySQL buffer pool (in docker-compose.yml)
command: --innodb-buffer-pool-size=1G --max-connections=200
```

### Application Scaling

**Docker Compose**:
```bash
# Scale application containers
docker-compose up -d --scale app=3
```

**Kubernetes**:
```yaml
# Convert pod to Deployment for horizontal scaling
replicas: 3
```

## Migration Path

### From Previous Version

1. **Pull latest code**:
   ```bash
   git pull origin TradingStrategies
   ```

2. **Create .env file**:
   ```bash
   cp .env.example .env
   # Edit .env with your values
   ```

3. **Stop old services**:
   ```bash
   docker-compose down
   ```

4. **Start new services**:
   ```bash
   docker-compose build
   docker-compose up -d
   ```

5. **Verify Redis**:
   ```bash
   docker-compose ps redis  # Should show "healthy"
   ```

## Troubleshooting

### Redis Connection Issues

```bash
# Check Redis is running
docker-compose ps redis

# Check Redis logs
docker-compose logs redis

# Test connection
docker exec stock-analysis-app php -r "new Redis(); echo 'PHP Redis installed';"
```

### Port Conflicts

```bash
# If ports are in use, change in .env:
APP_PORT=8090
MYSQL_PORT=3307
REDIS_PORT=6380
PHPMYADMIN_PORT=8082
REDIS_COMMANDER_PORT=8083
```

### Kubernetes Pod Issues

```bash
# Check pod status
kubectl describe pod stock-analysis

# Check container logs
kubectl logs stock-analysis -c redis
kubectl logs stock-analysis -c mysql
kubectl logs stock-analysis -c app

# Check events
kubectl get events --sort-by=.metadata.creationTimestamp
```

## Next Steps

1. **Update Production**: Deploy to production environment with secure passwords
2. **Configure Monitoring**: Set up Prometheus/Grafana for metrics
3. **Implement Backups**: Configure automated database and Redis backups
4. **Set Up CI/CD**: Automate testing and deployment pipeline
5. **Configure SSL**: Add SSL certificates and reverse proxy (Nginx/Traefik)
6. **Load Testing**: Test application under load to tune resource limits
7. **Documentation**: Update application documentation with Redis usage

## References

- **Docker Compose**: https://docs.docker.com/compose/
- **Kubernetes**: https://kubernetes.io/docs/
- **Redis**: https://redis.io/documentation
- **Ansible**: https://docs.ansible.com/
- **mPDF**: https://mpdf.github.io/

## Support

For questions or issues:
1. Check logs: `docker-compose logs -f`
2. Review documentation: `deployment/README.md`
3. GitHub Issues: Open issue with logs and configuration
4. Email: contact development team

---

**Deployment Configuration Complete** ✅

All services configured with Redis caching, flexible deployment options, and production-ready defaults.
