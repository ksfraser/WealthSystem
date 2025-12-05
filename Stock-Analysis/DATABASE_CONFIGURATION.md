# Database Configuration Guide

This guide explains how to configure the Stock Analysis application with different database options.

## Table of Contents

1. [Database Options](#database-options)
2. [Docker Compose Configurations](#docker-compose-configurations)
3. [Kubernetes Configurations](#kubernetes-configurations)
4. [Ansible Deployment](#ansible-deployment)
5. [Migration Between Databases](#migration-between-databases)
6. [Troubleshooting](#troubleshooting)

---

## Database Options

The application supports three deployment scenarios:

### 1. **MySQL (Default)**
- **Image**: `mysql:8.0`
- **Use Case**: Standard deployments, tested extensively
- **Container Name**: `stock-analysis-mysql`

### 2. **MariaDB**
- **Image**: `mariadb:11.2`
- **Use Case**: Drop-in MySQL replacement, often better performance
- **Container Name**: `stock-analysis-mariadb`

### 3. **External Database**
- **Use Case**: Production deployments with managed databases (AWS RDS, Azure Database, Google Cloud SQL)
- **Benefits**: Automatic backups, high availability, managed updates

---

## Docker Compose Configurations

### Option 1: Use MySQL (Default)

**Configuration**: No changes needed

```bash
# Start with MySQL
docker-compose up -d

# MySQL will be available at:
# - Host: mysql (internal) or localhost:3306 (external)
# - Database: stock_analysis
# - User: stock_app
```

### Option 2: Use MariaDB

**Method A: Using Profile**

```bash
# Start with MariaDB using profile
docker-compose --profile mariadb up -d
```

**Method B: Using Override File**

Create `docker-compose.override.yml`:

```yaml
version: '3.8'

services:
  app:
    depends_on:
      mariadb:
        condition: service_healthy
      redis:
        condition: service_healthy
    environment:
      DB_HOST: mariadb
      DB_TYPE: mariadb
```

Then start:

```bash
docker-compose up -d
```

**Method C: Using Environment Variables**

Create `.env` file:

```bash
DB_TYPE=mariadb
DB_HOST=mariadb
```

Then:

```bash
docker-compose --profile mariadb up -d
```

### Option 3: Use External Database

**Step 1**: Create `docker-compose.override.yml`:

```yaml
version: '3.8'

services:
  app:
    depends_on:
      redis:
        condition: service_healthy
    environment:
      USE_EXTERNAL_DB: "true"
      DB_TYPE: mysql  # or mariadb
      DB_HOST: my-rds-instance.us-east-1.rds.amazonaws.com
      DB_PORT: 3306
      DB_DATABASE: stock_analysis
      DB_USERNAME: stock_app
      DB_PASSWORD: your_secure_password
```

**Step 2**: Start only app and redis:

```bash
# Start without database containers
docker-compose up -d app redis

# Or specify services
docker-compose up -d
```

**Step 3**: Verify connection:

```bash
docker exec stock-analysis-app php -r "
try {
  \$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'),
    getenv('DB_USERNAME'),
    getenv('DB_PASSWORD')
  );
  echo 'Connected successfully to external database\n';
} catch (PDOException \$e) {
  echo 'Connection failed: ' . \$e->getMessage() . '\n';
}
"
```

---

## Kubernetes Configurations

### Option 1: MySQL Pod (Default)

Use the standard `pod.yml`:

```bash
kubectl apply -f deployment/pod.yml
```

### Option 2: MariaDB Pod

Use the MariaDB-specific configuration:

```bash
kubectl apply -f deployment/pod-mariadb.yml
```

### Option 3: External Database with Kubernetes

**Step 1**: Create external database secret:

```bash
kubectl create secret generic stock-analysis-db-external \
  --from-literal=host=my-db-instance.region.provider.com \
  --from-literal=port=3306 \
  --from-literal=database=stock_analysis \
  --from-literal=username=stock_app \
  --from-literal=password=your_secure_password
```

**Step 2**: Create modified pod configuration:

```yaml
apiVersion: v1
kind: Pod
metadata:
  name: stock-analysis-external-db
spec:
  containers:
  # Remove MySQL/MariaDB container
  
  # Redis container (unchanged)
  - name: redis
    # ... redis config ...
  
  # Application container with external DB
  - name: app
    image: stock-analysis:latest
    env:
    - name: USE_EXTERNAL_DB
      value: "true"
    - name: DB_HOST
      valueFrom:
        secretKeyRef:
          name: stock-analysis-db-external
          key: host
    - name: DB_PORT
      valueFrom:
        secretKeyRef:
          name: stock-analysis-db-external
          key: port
    - name: DB_DATABASE
      valueFrom:
        secretKeyRef:
          name: stock-analysis-db-external
          key: database
    - name: DB_USERNAME
      valueFrom:
        secretKeyRef:
          name: stock-analysis-db-external
          key: username
    - name: DB_PASSWORD
      valueFrom:
        secretKeyRef:
          name: stock-analysis-db-external
          key: password
    - name: DB_TYPE
      value: "mysql"  # or mariadb
```

**Step 3**: Apply configuration:

```bash
kubectl apply -f deployment/pod-external-db.yml
```

---

## Ansible Deployment

### Option 1: Deploy with MySQL

**Set variables in inventory or command line**:

```bash
ansible-playbook deployment/playbook.yml \
  -e "db_type=mysql" \
  -e "use_external_db=false"
```

### Option 2: Deploy with MariaDB

```bash
ansible-playbook deployment/playbook.yml \
  -e "db_type=mariadb" \
  -e "use_external_db=false"
```

### Option 3: Use External Database

**Step 1**: Set variables:

```bash
ansible-playbook deployment/playbook.yml \
  -e "use_external_db=true" \
  -e "external_db_host=my-db-instance.example.com" \
  -e "external_db_port=3306" \
  -e "mysql_database=stock_analysis" \
  -e "mysql_user=stock_app" \
  -e "mysql_password=secure_password"
```

**Step 2**: Only database client will be installed (no server)

**Using vars file**:

Create `deployment/external-db-vars.yml`:

```yaml
use_external_db: true
db_type: mysql
external_db_host: my-rds-instance.us-east-1.rds.amazonaws.com
external_db_port: 3306
mysql_database: stock_analysis
mysql_user: stock_app
mysql_password: "{{ vault_mysql_password }}"
```

Run:

```bash
ansible-playbook deployment/playbook.yml \
  -e @deployment/external-db-vars.yml \
  --ask-vault-pass
```

---

## Migration Between Databases

### MySQL to MariaDB

MariaDB is drop-in compatible with MySQL. Migration is straightforward:

**Step 1**: Backup MySQL data:

```bash
docker exec stock-analysis-mysql mysqldump -u root -p stock_analysis > backup.sql
```

**Step 2**: Stop MySQL:

```bash
docker-compose down
```

**Step 3**: Update configuration for MariaDB (see above)

**Step 4**: Start MariaDB:

```bash
docker-compose --profile mariadb up -d
```

**Step 5**: Restore data:

```bash
docker exec -i stock-analysis-mariadb mysql -u root -p stock_analysis < backup.sql
```

### Container Database to External Database

**Step 1**: Backup container database:

```bash
# MySQL
docker exec stock-analysis-mysql mysqldump -u root -p stock_analysis > backup.sql

# MariaDB
docker exec stock-analysis-mariadb mysqldump -u root -p stock_analysis > backup.sql
```

**Step 2**: Create database on external server:

```sql
CREATE DATABASE stock_analysis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'stock_app'@'%' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON stock_analysis.* TO 'stock_app'@'%';
FLUSH PRIVILEGES;
```

**Step 3**: Restore to external database:

```bash
mysql -h my-external-db.example.com -u stock_app -p stock_analysis < backup.sql
```

**Step 4**: Update docker-compose configuration (see External Database section above)

**Step 5**: Restart application:

```bash
docker-compose down
docker-compose up -d app redis
```

### External Database to Container Database

**Step 1**: Backup external database:

```bash
mysqldump -h my-external-db.example.com -u stock_app -p stock_analysis > backup.sql
```

**Step 2**: Remove override file:

```bash
rm docker-compose.override.yml
```

**Step 3**: Start with container database:

```bash
docker-compose up -d
```

**Step 4**: Restore data:

```bash
docker exec -i stock-analysis-mysql mysql -u root -p stock_analysis < backup.sql
```

---

## Troubleshooting

### Connection Issues

**Check database is running**:

```bash
# MySQL
docker-compose ps mysql

# MariaDB
docker-compose ps mariadb

# External - test from app container
docker exec stock-analysis-app mysqladmin -h $DB_HOST -u $DB_USERNAME -p ping
```

**Test connection from application**:

```bash
docker exec stock-analysis-app php -r "
\$host = getenv('DB_HOST');
\$db = getenv('DB_DATABASE');
\$user = getenv('DB_USERNAME');
\$pass = getenv('DB_PASSWORD');

try {
  \$pdo = new PDO(\"mysql:host=\$host;dbname=\$db\", \$user, \$pass);
  echo \"✓ Connected to \$host/\$db as \$user\n\";
  echo \"Database type: \" . getenv('DB_TYPE') . \"\n\";
  echo \"External DB: \" . (getenv('USE_EXTERNAL_DB') ?: 'false') . \"\n\";
} catch (PDOException \$e) {
  echo \"✗ Connection failed: \" . \$e->getMessage() . \"\n\";
}
"
```

### Port Conflicts

If database port 3306 is already in use:

```bash
# Change port in .env
MYSQL_PORT=3307

# Restart
docker-compose down
docker-compose up -d
```

### Performance Issues

**MySQL optimization**:

Add to `docker-compose.override.yml`:

```yaml
services:
  mysql:
    command: 
      - --innodb-buffer-pool-size=1G
      - --max-connections=200
      - --query-cache-size=64M
```

**MariaDB optimization**:

```yaml
services:
  mariadb:
    command:
      - --innodb-buffer-pool-size=1G
      - --max-connections=200
      - --query-cache-size=64M
      - --query-cache-type=1
```

### Migration Script Failures

If migrations fail after switching databases:

```bash
# Check migration status
docker exec stock-analysis-app php scripts/check-migrations.php

# Run migrations manually
docker exec stock-analysis-app php scripts/migrate.php

# Check database schema
docker exec stock-analysis-mysql mysql -u root -p -e "SHOW TABLES FROM stock_analysis;"
```

### External Database Connectivity

**Check network connectivity**:

```bash
# From host
telnet my-db-instance.example.com 3306

# From container
docker exec stock-analysis-app nc -zv my-db-instance.example.com 3306
```

**Check firewall rules**:
- Ensure security group allows inbound on port 3306
- Verify database is publicly accessible (if needed)
- Check VPC settings for managed databases

**Verify credentials**:

```bash
# Test from local machine
mysql -h my-db-instance.example.com -u stock_app -p

# Test from container
docker exec stock-analysis-app mysql -h $DB_HOST -u $DB_USERNAME -p
```

---

## Environment Variables Reference

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_TYPE` | `mysql` | Database type: `mysql` or `mariadb` |
| `USE_EXTERNAL_DB` | `false` | Use external database instead of container |
| `DB_HOST` | `mysql` | Database hostname |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `stock_analysis` | Database name |
| `DB_USERNAME` | `stock_app` | Database username |
| `DB_PASSWORD` | `changeme` | Database password |
| `MYSQL_ROOT_PASSWORD` | `changeme` | Root password for container database |
| `MYSQL_PORT` | `3306` | Exposed port for container database |

---

## Best Practices

### Security

1. **Use strong passwords**: Generate with `openssl rand -base64 32`
2. **Use secrets management**: Don't commit passwords to Git
3. **Restrict network access**: Use firewall rules and security groups
4. **Enable SSL/TLS**: For external database connections
5. **Regular updates**: Keep database versions updated

### Performance

1. **Use external databases for production**: Better reliability and backups
2. **Configure connection pooling**: Optimize database connections
3. **Monitor query performance**: Use slow query logs
4. **Regular maintenance**: Run OPTIMIZE TABLE periodically
5. **Proper indexing**: Ensure tables have appropriate indexes

### Backups

1. **Automated backups**: Configure daily backups
2. **Test restores**: Regularly test backup restoration
3. **Retention policy**: Keep backups for 30 days minimum
4. **Off-site storage**: Store backups in different region/provider

### High Availability

1. **Use managed databases**: AWS RDS, Azure Database, etc.
2. **Enable multi-AZ**: For automatic failover
3. **Read replicas**: For read-heavy workloads
4. **Connection retry logic**: Handle temporary outages gracefully

---

## Support

For issues or questions:
1. Check logs: `docker-compose logs mysql` or `docker-compose logs mariadb`
2. Review this guide
3. Check GitHub issues
4. Contact development team

---

**Version**: 1.0  
**Last Updated**: December 4, 2025
