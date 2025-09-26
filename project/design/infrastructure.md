# Infrastructure Overview

## System Architecture
- **Language:** PHP 7.3+
- **Database:** MySQL/MariaDB
- **Queue:** MQTT (Mosquitto), with support for Redis, RabbitMQ, and DB
- **Job Processing:** Distributed, multi-backend, with worker registration and monitoring
- **Table Structure:** Per-symbol tables (7 per symbol) for all stock data
- **Deployment:** Multi-machine, supports VMs and external boxes
- **Monitoring:** Real-time dashboard, REST API

## Directory Structure
- `/src` — Core business logic, actions, handlers, interfaces
- `/scripts` — CLI entry points for all management and migration tasks
- `/tests` — PHPUnit test suite
- `/docs` — Documentation
- `/Experiment Details`, `/Weekly Deep Research (MD|PDF)` — Research and results

## Dependencies
- PHP 7.3+
- Composer (for autoloading and PHPUnit)
- MySQL/MariaDB
- Mosquitto (MQTT broker)
- Redis/RabbitMQ (optional)
- PHPUnit (for testing)

## Deployment
- Clone repo to all worker and server machines
- Configure DB and queue settings in `DatabaseConfig.php` and config files
- Use `scripts/AddNewSymbol.php` and related scripts for symbol/table management
- Use `scripts/MigrateToPerSymbolTables.php` for migration
- Use `scripts/ManageSymbols.php` for ongoing maintenance

## Security
- DB credentials stored in config files (YAML/INI)
- Table and symbol validation to prevent SQL injection
- CLI scripts validate input and require confirmation for destructive actions

## Backup & Recovery
- Per-symbol table structure allows for granular backup/restore
- Use MySQL dump tools to export/import individual symbol tables
- Migration script supports dry-run for safe testing
