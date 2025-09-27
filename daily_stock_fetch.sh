#!/bin/bash

# Daily Stock Data Fetch Script
# Can be run via cron or manually
# Usage: ./daily_stock_fetch.sh

# Set the working directory to the project root
cd "$(dirname "$0")"

# Log file for cron runs
LOGFILE="web_ui/data/cron_fetch_log.txt"

# Create data directory if it doesn't exist
mkdir -p web_ui/data

# Function to log with timestamp
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOGFILE"
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1"
}

log_message "Starting daily stock data fetch"

# Check if auto-fetch is enabled
if php -f web_ui/cron_auto_fetch.php; then
    log_message "Daily stock data fetch completed successfully"
else
    log_message "ERROR: Daily stock data fetch failed"
    exit 1
fi

log_message "Daily stock data fetch script finished"