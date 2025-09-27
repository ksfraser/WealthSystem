# Job Queue System - User Guide

This comprehensive job queue system solves the timeout issues you experienced by breaking long-running operations into manageable chunks that run in the background.

## 🚀 Quick Start

### 1. **Immediate Solution for Your Timeout Problem**

Instead of calling the old `ProgressiveHistoricalLoader->loadMultipleSymbols()` that times out, use:

```php
<?php
require_once 'ProgressiveHistoricalLoaderV2.php';

$loader = new ProgressiveHistoricalLoaderV2();

// Queue the job instead of running it directly
$result = $loader->loadMultipleSymbols(['AAPL', 'MSFT', 'GOOGL']);

echo "Job queued: #{$result['job_id']}\n";
echo "Estimated chunks: {$result['estimated_chunks']}\n";
?>
```

### 2. **Track Progress**

```php
// Check job progress
$progress = $loader->getJobProgress($result['job_id']);
echo "Status: {$progress['status']}\n";
echo "Progress: {$progress['progress']['current']}/{$progress['progress']['total']} ({$progress['progress']['percentage']}%)\n";
```

### 3. **Process Jobs**

You have several options to process the queued jobs:

#### Option A: Manual Processing (for testing)
```bash
# Process one job at a time
php JobWorker.php single
```

#### Option B: Continuous Processing
```bash
# Run worker continuously
php JobWorker.php run
```

#### Option C: Web Interface
Open `web_ui/job_manager.html` in your browser for a visual job management interface.

## 📋 System Components

### 1. **JobQueue.php** - Core Job Management
- Queues jobs in database
- Breaks large operations into chunks
- Handles retries and error recovery
- Supports parent/child job relationships

### 2. **JobWorker.php** - Background Processing
- Processes jobs from the queue
- Handles timeouts gracefully (25-second limit per chunk)
- Can run as cron job or continuous daemon
- Provides CLI interface

### 3. **ProgressiveHistoricalLoaderV2.php** - Updated Loader
- Drop-in replacement for original loader
- Returns job IDs instead of blocking
- Provides progress tracking
- Includes data coverage analysis

### 4. **Web Interface** - Visual Management
- `web_ui/job_manager.html` - Main interface
- `web_ui/job_manager_api.php` - REST API
- Real-time progress monitoring
- Job creation and management

## 🔧 Setup Instructions

### 1. **Database Setup**
The job queue tables are created automatically when you first use the system. No manual setup required.

### 2. **Background Processing Setup**

#### Windows (Your System)
1. **Manual Processing** (for immediate testing):
   ```powershell
   cd "C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment"
   php JobWorker.php single
   ```

2. **Scheduled Processing** (recommended):
   - Open Windows Task Scheduler
   - Create new task: "Stock Data Job Worker"
   - Set trigger: Every 1 minute
   - Set action: Run `run_worker.bat`
   - Enable "Run whether user is logged on or not"

3. **Continuous Processing** (alternative):
   ```powershell
   # Run in background (keep PowerShell window open)
   php JobWorker.php run
   ```

#### Linux/Mac
Use the cron setup from `cron_setup.txt`:
```bash
# Edit crontab
crontab -e

# Add this line for every-minute processing
* * * * * /usr/bin/php /path/to/your/project/JobWorker.php single >> /path/to/your/project/logs/cron.log 2>&1
```

### 3. **Web Interface Setup**
1. Ensure your web server can access `web_ui/job_manager.html`
2. Make sure `web_ui/job_manager_api.php` is accessible
3. Open the interface in your browser: `http://localhost/your-project/web_ui/job_manager.html`

## 📊 Usage Examples

### Loading Historical Data

```php
<?php
require_once 'ProgressiveHistoricalLoaderV2.php';

$loader = new ProgressiveHistoricalLoaderV2();

// Load single stock
$result = $loader->loadSymbol('AAPL', '2020-01-01');
echo "Queued job #{$result['job_id']} for AAPL\n";

// Load portfolio
$symbols = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA'];
$result = $loader->loadMultipleSymbols($symbols, '2022-01-01');
echo "Queued portfolio job #{$result['job_id']} for " . count($symbols) . " symbols\n";

// Monitor progress
do {
    sleep(5); // Wait 5 seconds
    $progress = $loader->getJobProgress($result['job_id']);
    echo "Progress: {$progress['progress']['percentage']}% - Status: {$progress['status']}\n";
} while ($progress['status'] === 'pending' || $progress['status'] === 'processing');

echo "Final status: {$progress['status']}\n";
if ($progress['status'] === 'completed') {
    print_r($progress['result']);
}
?>
```

### Data Coverage Analysis

```php
// Check what data you already have
$coverage = $loader->getDataCoverage('AAPL');
echo "AAPL has {$coverage['record_count']} records\n";
echo "Date range: {$coverage['date_range']['start']} to {$coverage['date_range']['end']}\n";
echo "Completeness: {$coverage['completeness_percentage']}%\n";

if ($coverage['needs_update']) {
    echo "Data needs update - queuing refresh job...\n";
    $result = $loader->loadSymbol('AAPL');
}
```

### Command Line Interface

```bash
# Queue jobs via CLI
php ProgressiveHistoricalLoaderV2.php load-symbol AAPL 2020-01-01
php ProgressiveHistoricalLoaderV2.php load-portfolio "AAPL,MSFT,GOOGL"

# Check job status
php ProgressiveHistoricalLoaderV2.php status 123

# Check data coverage
php ProgressiveHistoricalLoaderV2.php coverage AAPL
php ProgressiveHistoricalLoaderV2.php portfolio-coverage "AAPL,MSFT,GOOGL"

# Process jobs
php JobWorker.php single          # Process one job
php JobWorker.php run             # Run continuously
php JobWorker.php run-limited 10  # Process 10 jobs then stop
```

## 🔍 Monitoring and Troubleshooting

### Log Files
- `logs/job_worker.log` - Worker activity
- `logs/cron.log` - Cron job output
- `logs/cleanup.log` - Cleanup operations

### Job States
- **pending** - Waiting to be processed
- **processing** - Currently being worked on
- **completed** - Successfully finished
- **failed** - Error occurred (will retry up to 3 times)
- **paused** - Manually paused (future feature)

### Common Issues

1. **Jobs stuck in processing**
   - Restart the worker: Stop current worker and start again
   - Check logs for error messages

2. **No jobs being processed**
   - Verify worker is running: `php JobWorker.php single`
   - Check database connectivity
   - Review log files

3. **High failure rate**
   - Check internet connectivity
   - Verify Yahoo Finance API limits
   - Review error messages in job details

### Performance Tuning

```php
// Adjust chunk sizes for better performance
$jobQueue = new JobQueue($db);

// High priority for urgent jobs
$jobId = $jobQueue->queueStockLoad('AAPL', null, 2); // priority 2 = urgent

// Process multiple jobs in sequence
for ($i = 0; $i < 10; $i++) {
    $result = $jobQueue->processNextJob(25);
    if ($result['status'] === 'no_jobs') break;
}
```

## 🎯 Benefits Over Original System

1. **No More Timeouts** - Each chunk processes in under 25 seconds
2. **Resumable Operations** - Failed jobs can be retried
3. **Progress Tracking** - Real-time visibility into operations
4. **Background Processing** - UI remains responsive
5. **Scalable Architecture** - Can process multiple jobs in parallel
6. **Error Recovery** - Automatic retry with exponential backoff
7. **Web Interface** - Visual management and monitoring

## 🔧 Integration with Existing Code

### Replace Old Loader Calls

**Old (timeout-prone) code:**
```php
$loader = new ProgressiveHistoricalLoader();
$loader->loadMultipleSymbols($symbols); // TIMES OUT!
```

**New (queue-based) code:**
```php
$loader = new ProgressiveHistoricalLoaderV2();
$result = $loader->loadMultipleSymbols($symbols);
echo "Job queued: #{$result['job_id']}\n";

// Optional: Wait for completion
$progress = $loader->getJobProgress($result['job_id']);
while ($progress['status'] === 'pending' || $progress['status'] === 'processing') {
    sleep(5);
    $progress = $loader->getJobProgress($result['job_id']);
}
```

### Add Progress UI to Existing Pages

```javascript
// Add this to your existing web pages
function checkJobProgress(jobId) {
    fetch(`web_ui/job_manager_api.php?action=details&job_id=${jobId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const progress = data.job.progress_percentage;
                document.getElementById('progress-bar').style.width = progress + '%';
                document.getElementById('progress-text').textContent = 
                    `${data.job.progress_current}/${data.job.progress_total} (${progress}%)`;
                
                if (data.job.status === 'completed') {
                    showCompletionMessage();
                } else if (data.job.status === 'processing') {
                    setTimeout(() => checkJobProgress(jobId), 2000);
                }
            }
        });
}
```

## 🚀 Next Steps

1. **Test the System**: Start with a small job to verify everything works
2. **Set Up Background Processing**: Use Task Scheduler (Windows) or cron (Linux/Mac)
3. **Monitor Performance**: Check the web interface regularly
4. **Scale as Needed**: Add more workers if you have many symbols to process

This job queue system completely eliminates the timeout issues while providing better control, monitoring, and reliability for your historical data loading operations.