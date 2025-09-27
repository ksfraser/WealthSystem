# Debug Directory

This directory contains debugging files and scripts that were moved from the web_ui directory for security and organization purposes.

## Security Note
Debug files should NEVER be placed in the web root (`web_ui/`) as they can expose:
- Database connection details
- Internal system information  
- Authentication mechanisms
- Error details that could help attackers

## Directory Structure

```
debug/
├── web_ui/                 # Debug files related to web interface
│   ├── auth/              # Authentication debugging
│   ├── admin/             # Admin interface debugging  
│   ├── errors/            # Error investigation scripts
│   └── outputs/           # Debug output files and logs
├── job_queue/             # Job queue system debugging
├── stock_data/            # Stock data service debugging
└── archived/              # Old debug files (for reference)
```

## Usage

### Running Debug Scripts
```bash
# From project root
php debug/web_ui/auth/debug_userauth.php
php debug/web_ui/errors/debug_500_investigation.php
```

### Development Guidelines
1. **Always create debug files in this directory structure**
2. **Never put debug files in web_ui/ or other web-accessible directories**
3. **Use descriptive names with date/purpose when creating debug files**
4. **Clean up debug files regularly - don't let them accumulate**
5. **Add sensitive debug files to .gitignore**

## File Categories

### Authentication Debug Files
- User authentication testing
- Session management debugging  
- Permission/role verification

### Error Investigation Files  
- 500 error debugging
- Database connection issues
- Application error analysis

### Admin Debug Files
- Admin interface functionality testing
- User management debugging
- System configuration verification

### Output Files
- Debug logs and results
- Test output files
- Performance analysis data

## Security Best Practices

1. **Never expose these files via web server**
2. **Don't include sensitive credentials in debug files**
3. **Use environment variables for configuration in debug scripts**
4. **Regularly review and clean up old debug files**
5. **Add debug/ directory to .gitignore if it contains sensitive data**

## Cleanup Schedule

- **Weekly**: Remove temporary debug output files
- **Monthly**: Archive old debug scripts that are no longer needed
- **Quarterly**: Review entire debug directory for obsolete files