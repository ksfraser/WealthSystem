@echo off
REM Daily Stock Data Fetch Script for Windows
REM Can be run via Task Scheduler or manually
REM Usage: daily_stock_fetch.bat

REM Set the working directory to the project root
cd /d "%~dp0"

REM Log file for scheduled runs
set LOGFILE=web_ui\data\cron_fetch_log.txt

REM Create data directory if it doesn't exist
if not exist web_ui\data mkdir web_ui\data

REM Function to log with timestamp
echo %date% %time% - Starting daily stock data fetch >> %LOGFILE%
echo %date% %time% - Starting daily stock data fetch

REM Run the PHP auto-fetch script
php web_ui\cron_auto_fetch.php
if %errorlevel% neq 0 (
    echo %date% %time% - ERROR: Daily stock data fetch failed >> %LOGFILE%
    echo %date% %time% - ERROR: Daily stock data fetch failed
    exit /b 1
)

echo %date% %time% - Daily stock data fetch completed successfully >> %LOGFILE%
echo %date% %time% - Daily stock data fetch completed successfully

echo %date% %time% - Daily stock data fetch script finished >> %LOGFILE%
echo %date% %time% - Daily stock data fetch script finished