# Stock Analysis Application - Quick Reference

## 🚀 Quick Start

### Docker Compose (Recommended)

```bash
# 1. Clone repository
git clone https://github.com/proteusbr1/TradingStrategies.git
cd TradingStrategies/Stock-Analysis

# 2. Configure environment
cp .env.example .env
# Edit .env and change passwords!

# 3. Start services
docker-compose up -d

# 4. Access application
# http://localhost:8080
```

### With Management Tools

```bash
# Start with phpMyAdmin and Redis Commander
docker-compose --profile tools up -d

# Access:
# - App: http://localhost:8080
# - phpMyAdmin: http://localhost:8081
# - Redis Commander: http://localhost:8082
```

## 📦 What's Deployed

| Service | Container | Port | Purpose |
|---------|-----------|------|---------|
| Application | `stock-analysis-app` | 8080 | Main web application |
| MySQL | `stock-analysis-db` | 3306 | Database |
| Redis | `stock-analysis-redis` | 6379 | Cache server |
| phpMyAdmin | `stock-analysis-phpmyadmin` | 8081 | DB management (optional) |
| Redis Commander | `stock-analysis-redis-commander` | 8082 | Cache management (optional) |

## ⚙️ Essential Commands

### Service Management

```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart specific service
docker-compose restart app

# View status
docker-compose ps

# View logs
docker-compose logs -f app
docker-compose logs -f redis
docker-compose logs -f db
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

### Redis Operations

```bash
# Redis CLI
docker exec -it stock-analysis-redis redis-cli -a changeme

# Monitor cache
docker exec stock-analysis-redis redis-cli -a changeme MONITOR

# Check memory
docker exec stock-analysis-redis redis-cli -a changeme INFO memory

# Flush cache
docker exec stock-analysis-redis redis-cli -a changeme FLUSHALL
```

### Application Shell

```bash
# Access application container
docker exec -it stock-analysis-app /bin/bash

# Run PHP script
docker exec stock-analysis-app php /var/www/stock-analysis/scripts/sync-market-data.php

# Run Python script
docker exec stock-analysis-app /var/www/stock-analysis/venv/bin/python /var/www/stock-analysis/python_analysis/analysis.py
```

## 🔧 Configuration

### Required Variables (.env)

```bash
# Change these passwords!
MYSQL_ROOT_PASSWORD=your_secure_root_password
MYSQL_APP_PASSWORD=your_secure_app_password
REDIS_PASSWORD=your_secure_redis_password
```

### Optional Variables (Have Defaults)

```bash
# Application
APP_NAME=stock-analysis
APP_DOMAIN=example.com
APP_PORT=8080

# Ports
MYSQL_PORT=3306
REDIS_PORT=6379
PHPMYADMIN_PORT=8081
REDIS_COMMANDER_PORT=8082
```

## 🔍 Health Checks

```bash
# Check all services are healthy
docker-compose ps

# Expected output:
# All services should show "healthy" or "running"

# Test application
curl http://localhost:8080

# Test Redis
docker exec stock-analysis-redis redis-cli -a changeme PING
# Expected: PONG

# Test MySQL
docker exec stock-analysis-db mysqladmin ping -h localhost
# Expected: mysqld is alive
```

## 🐛 Troubleshooting

### Services Won't Start

```bash
# View logs
docker-compose logs

# Check for port conflicts
netstat -an | findstr "8080"  # Windows
lsof -i :8080                  # Linux/Mac

# Change ports in .env if needed
APP_PORT=8090
```

### Database Connection Issues

```bash
# Verify MySQL is running
docker-compose ps db

# Check database logs
docker-compose logs db

# Test connection
docker exec stock-analysis-app php -r "new PDO('mysql:host=db;dbname=stock_analysis', 'stock_app', 'changeme');"
```

### Redis Connection Issues

```bash
# Verify Redis is running
docker-compose ps redis

# Check Redis logs
docker-compose logs redis

# Test connection
docker exec stock-analysis-app php -r "\$r = new Redis(); \$r->connect('redis', 6379); \$r->auth('changeme'); echo \$r->ping();"
```

### Container Crashes

```bash
# View container logs
docker logs stock-analysis-app

# Check resource usage
docker stats

# Restart specific container
docker-compose restart app
```

## 📊 Features Overview

### Export Features (Task #4)

**Generate PDF Reports**:
- Sector Analysis: Portfolio allocation by sector
- Index Benchmarks: Performance vs market indices
- Advanced Charts: Correlation matrices

**Usage**:
1. Navigate to Sector Analysis page
2. Click "Export PDF" button
3. PDF downloads automatically

**API**:
```bash
# Export sector analysis
curl "http://localhost:8080/api/export.php?action=sector_analysis" -o report.pdf

# Export index benchmark
curl "http://localhost:8080/api/export.php?action=index_benchmark" -o benchmark.pdf

# Export advanced charts
curl "http://localhost:8080/api/export.php?action=advanced_charts" -o charts.pdf
```

### Alert System (Task #5)

**Monitor Portfolio**:
- Concentration Risk: When portfolio too concentrated (HHI > 1500/2500)
- Rebalancing Needs: When sector allocation deviates > 5%
- Performance Warnings: When underperforming 3+ periods

**Widget**: Displays on pages with `AlertsWidget::render()`

**API**:
```bash
# Generate alerts
curl -X POST "http://localhost:8080/api/alerts.php?action=generate"

# Get active alerts
curl "http://localhost:8080/api/alerts.php?action=get_active"

# Dismiss alert
curl -X POST "http://localhost:8080/api/alerts.php?action=dismiss" \
  -H "Content-Type: application/json" \
  -d '{"alert_id": "concentration_risk"}'
```

## 🔒 Security Checklist

- [ ] Change `MYSQL_ROOT_PASSWORD` in .env
- [ ] Change `MYSQL_APP_PASSWORD` in .env
- [ ] Change `REDIS_PASSWORD` in .env
- [ ] Set `APP_DEBUG=false` for production
- [ ] Configure SSL/TLS (via reverse proxy)
- [ ] Enable firewall (UFW, iptables)
- [ ] Set up automated backups
- [ ] Configure monitoring and alerting

## 📚 Documentation

- **Complete Guide**: `deployment/README.md`
- **Tasks #4 & #5**: `TASKS_4_5_SUMMARY.md`
- **Deployment Update**: `DEPLOYMENT_UPDATE_SUMMARY.md`
- **Session Summary**: `SESSION_SUMMARY.md`
- **Environment Variables**: `.env.example`

## 🆘 Getting Help

1. **Check Logs**: `docker-compose logs -f [service]`
2. **Review Docs**: See documentation files above
3. **Check Health**: `docker-compose ps`
4. **GitHub Issues**: Report bugs with logs
5. **Contact**: Development team

## 🔄 Update Procedure

```bash
# 1. Backup data
docker exec stock-analysis-db mysqldump -u root -p stock_analysis > backup.sql

# 2. Pull updates
git pull origin TradingStrategies

# 3. Rebuild images
docker-compose build

# 4. Restart services
docker-compose down
docker-compose up -d

# 5. Verify
docker-compose ps
```

## 📈 Next Steps

1. **Test Application**: Verify all features work
2. **Configure Production**: Set secure passwords and SSL
3. **Set Up Monitoring**: Add Prometheus/Grafana
4. **Enable Backups**: Automate database backups
5. **Configure CI/CD**: Set up automated deployments

---

## 🎯 Quick Tips

- **Logs**: Always check logs first when troubleshooting
- **Health**: Use `docker-compose ps` to verify container health
- **Passwords**: Never commit real passwords to Git
- **Profiles**: Use `--profile tools` for management interfaces
- **Resources**: Monitor with `docker stats` if performance issues
- **Backups**: Schedule regular database backups

## 📱 Access URLs

| Service | URL | Credentials |
|---------|-----|-------------|
| Application | http://localhost:8080 | N/A |
| phpMyAdmin | http://localhost:8081 | User: `stock_app`<br>Password: from .env |
| Redis Commander | http://localhost:8082 | Auto-configured |

---

**Version**: 1.0  
**Last Updated**: December 4, 2025  
**Branch**: TradingStrategies  
**Commit**: 1c135dc8
