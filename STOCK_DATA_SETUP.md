# Stock Data Management Setup Guide

## Overview
The stock data management system provides both manual and automated fetching of daily stock price data from Yahoo Finance. It integrates with the existing admin interface and can fetch data either when users log in or via scheduled tasks.

## Features

### Admin Interface
- **Manual Data Fetching**: Fetch current data for individual stocks or entire portfolio
- **Historical Data Population**: Populate historical price data for any stock symbol
- **Auto-Fetch Configuration**: Enable/disable automatic daily data fetching
- **Status Monitoring**: View current system status and last fetch information

### Automated Data Fetching
Two methods for automated daily data fetching:

1. **User Login Trigger** (Default): Automatically fetches daily data when any user logs in
2. **Scheduled Task/Cron**: Run data fetch at scheduled times using system scheduler

## File Structure

```
web_ui/
├── admin/
│   ├── index.php                 # Main admin dashboard
│   └── stock_data_admin.php      # Stock data management interface
├── AutoFetchService.php          # Auto-fetch service class
├── cron_auto_fetch.php          # Cron-compatible fetch script
├── StockDAO.php                 # Database access for stock data
└── data/
    ├── auto_fetch_config.json   # Auto-fetch configuration
    ├── auto_fetch_log.txt       # Auto-fetch activity log
    └── cron_fetch_log.txt       # Cron/scheduled task log

daily_stock_fetch.sh             # Linux/Mac scheduled script
daily_stock_fetch.bat            # Windows scheduled script
fetch_historical_data.py         # Python data fetching script
```

## Setup Instructions

### 1. Admin Interface Access
1. Log in as an admin user
2. Navigate to the admin section (👥 Admin badge in navigation)
3. Click "📈 Stock Data" to access stock data management

### 2. Enable Auto-Fetch
From the Stock Data Management page:
1. Click "Enable Auto-Fetch" button
2. This enables automatic daily data fetching when users log in

### 3. Manual Data Fetching
- **Single Stock**: Enter symbol and click "Fetch Data"
- **Portfolio**: Click "Fetch Portfolio Data" to update all portfolio stocks
- **Historical Data**: Enter symbol and days (up to 5 years) to populate historical records

### 4. Scheduled Task Setup (Optional)

#### Windows Task Scheduler
1. Open Task Scheduler
2. Create Basic Task
3. Set trigger to "Daily" at desired time (e.g., 6:00 AM)
4. Action: Start a program
5. Program: Full path to `daily_stock_fetch.bat`
6. Start in: Project root directory

#### Linux/Mac Cron
1. Edit crontab: `crontab -e`
2. Add line: `0 6 * * 1-5 /path/to/project/daily_stock_fetch.sh`
   (Runs weekdays at 6:00 AM)

#### Manual Command Line
```bash
# Linux/Mac
./daily_stock_fetch.sh

# Windows
daily_stock_fetch.bat

# Direct PHP (any OS)
php web_ui/cron_auto_fetch.php
```

## Configuration Files

### auto_fetch_config.json
```json
{
  "auto_fetch_enabled": true,
  "last_fetch_date": "2024-01-15"
}
```

### Portfolio Source
The system reads stock symbols from:
`Scripts and CSV Files/chatgpt_portfolio_update.csv`

## Data Storage
- Individual database tables per stock symbol (e.g., `AAPL_prices`, `MSFT_prices`)
- Price data includes: Date, Open, High, Low, Close, Volume
- Uses `StockDAO` class for database operations

## Monitoring
- Admin dashboard shows auto-fetch status
- Log files track fetch activity
- Error handling prevents system disruption

## Troubleshooting

### Python Script Issues
- Ensure `yfinance` and `pandas` are installed: `pip install yfinance pandas`
- Check Python path in system PATH environment variable
- Verify internet connectivity for Yahoo Finance API

### Database Connection
- Confirm MySQL server is running
- Check database connection settings in `includes/config.php`
- Verify database name "microcap_trading" exists

### Permission Issues
- Ensure web server has write permissions to `web_ui/data/` directory
- Check file permissions for batch/shell scripts (executable)

### Auto-Fetch Not Working
1. Check auto-fetch status in admin interface
2. Review log files in `web_ui/data/`
3. Test manual fetch to isolate issues
4. Verify portfolio CSV file exists and contains valid symbols

## API Limits
- Yahoo Finance API has rate limits
- System fetches data once per day maximum
- Handles errors gracefully without affecting user login

## Security
- Admin-only access to stock data management
- Auto-fetch runs silently without exposing errors to users
- Logs activities for audit purposes
- Input validation for stock symbols and parameters