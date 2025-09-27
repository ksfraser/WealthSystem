# Job Queue Management Script for Windows PowerShell
# Provides easy commands for managing the job queue system

param(
    [Parameter(Position=0)]
    [string]$Command = "help",
    
    [Parameter(Position=1)]
    [string]$Symbol = "",
    
    [Parameter(Position=2)]
    [string]$StartDate = "",
    
    [switch]$Help
)

$ProjectPath = "C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment"

function Show-Help {
    Write-Host "=== JOB QUEUE MANAGEMENT SCRIPT ===" -ForegroundColor Green
    Write-Host ""
    Write-Host "Usage: .\manage_jobs.ps1 <command> [options]" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Commands:" -ForegroundColor Cyan
    Write-Host "  test                    - Run integration test"
    Write-Host "  queue <SYMBOL> [date]   - Queue single symbol job"
    Write-Host "  portfolio [date]        - Queue portfolio job"
    Write-Host "  process                 - Process next job manually"
    Write-Host "  status [job_id]         - Show job status"
    Write-Host "  list                    - List all jobs"
    Write-Host "  worker                  - Start continuous worker"
    Write-Host "  setup                   - Set up Windows Task Scheduler"
    Write-Host "  web                     - Open job manager web interface"
    Write-Host ""
    Write-Host "Examples:" -ForegroundColor Yellow
    Write-Host "  .\manage_jobs.ps1 queue AAPL 2024-01-01"
    Write-Host "  .\manage_jobs.ps1 portfolio 2024-01-01" 
    Write-Host "  .\manage_jobs.ps1 process"
    Write-Host "  .\manage_jobs.ps1 worker"
    Write-Host ""
}

function Test-Integration {
    Write-Host "🧪 Running integration test..." -ForegroundColor Yellow
    Set-Location $ProjectPath
    php test_job_queue_integration.php
}

function Start-Worker {
    Write-Host "🚀 Starting continuous job worker..." -ForegroundColor Green
    Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
    Set-Location $ProjectPath
    php JobWorker.php run
}

function Queue-Symbol {
    param($Symbol, $StartDate)
    
    if (-not $Symbol) {
        Write-Host "❌ Symbol required. Usage: queue <SYMBOL> [date]" -ForegroundColor Red
        return
    }
    
    Write-Host "📝 Queueing symbol: $Symbol" -ForegroundColor Green
    Set-Location $ProjectPath
    
    if ($StartDate) {
        php ProgressiveHistoricalLoaderV2.php load-symbol $Symbol $StartDate
    } else {
        php ProgressiveHistoricalLoaderV2.php load-symbol $Symbol
    }
}

function Queue-Portfolio {
    param($StartDate)
    
    Write-Host "📊 Queueing portfolio job..." -ForegroundColor Green
    Set-Location $ProjectPath
    
    # Get portfolio symbols from CSV
    $portfolioFile = "$ProjectPath\Scripts and CSV Files\chatgpt_portfolio_update.csv"
    
    if (Test-Path $portfolioFile) {
        $symbols = Import-Csv $portfolioFile | Select-Object -ExpandProperty Symbol -First 10
        $symbolList = $symbols -join ","
        
        Write-Host "Found $($symbols.Count) portfolio symbols" -ForegroundColor Cyan
        
        if ($StartDate) {
            php ProgressiveHistoricalLoaderV2.php load-portfolio $symbolList $StartDate
        } else {
            php ProgressiveHistoricalLoaderV2.php load-portfolio $symbolList
        }
    } else {
        Write-Host "❌ Portfolio CSV file not found: $portfolioFile" -ForegroundColor Red
    }
}

function Process-Job {
    Write-Host "⚡ Processing next job..." -ForegroundColor Yellow
    Set-Location $ProjectPath
    php JobWorker.php single
}

function Show-JobStatus {
    param($JobId)
    
    if ($JobId) {
        Write-Host "📊 Checking status for job #$JobId..." -ForegroundColor Cyan
        Set-Location $ProjectPath
        php ProgressiveHistoricalLoaderV2.php status $JobId
    } else {
        Write-Host "📋 Listing all jobs..." -ForegroundColor Cyan
        Set-Location $ProjectPath
        php JobQueue.php list
    }
}

function Setup-TaskScheduler {
    Write-Host "⚙️  Setting up Windows Task Scheduler..." -ForegroundColor Green
    
    $taskName = "StockDataJobWorker"
    $scriptPath = "$ProjectPath\run_worker.bat"
    
    # Create the scheduled task
    $action = New-ScheduledTaskAction -Execute $scriptPath
    $trigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 1) -Once -At (Get-Date)
    $settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
    
    try {
        Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Settings $settings -Force
        Write-Host "✅ Task scheduler setup complete!" -ForegroundColor Green
        Write-Host "Task '$taskName' will run every minute" -ForegroundColor Cyan
    } catch {
        Write-Host "❌ Failed to setup task scheduler. Run as Administrator?" -ForegroundColor Red
        Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    }
}

function Open-WebInterface {
    Write-Host "🌐 Opening job manager web interface..." -ForegroundColor Cyan
    $webPath = "$ProjectPath\web_ui\job_manager.html"
    
    if (Test-Path $webPath) {
        Start-Process $webPath
    } else {
        Write-Host "❌ Web interface not found: $webPath" -ForegroundColor Red
    }
}

# Main script logic
Set-Location $ProjectPath

if ($Help) {
    Show-Help
    exit
}

switch ($Command.ToLower()) {
    "help" { Show-Help }
    "test" { Test-Integration }
    "queue" { Queue-Symbol $Symbol $StartDate }
    "portfolio" { Queue-Portfolio $StartDate }
    "process" { Process-Job }
    "status" { Show-JobStatus $Symbol }
    "list" { Show-JobStatus }
    "worker" { Start-Worker }
    "setup" { Setup-TaskScheduler }
    "web" { Open-WebInterface }
    default { 
        Write-Host "❌ Unknown command: $Command" -ForegroundColor Red
        Show-Help 
    }
}