@echo off
REM Windows batch file to run job worker as a scheduled task
REM Place this file in your project directory and schedule it in Windows Task Scheduler

cd /d "C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment"

REM Process a single job (for scheduled task every minute)
php.exe JobWorker.php single >> logs\worker.log 2>&1

REM Alternative: Run continuously (for background service)
REM php.exe JobWorker.php run >> logs\worker.log 2>&1