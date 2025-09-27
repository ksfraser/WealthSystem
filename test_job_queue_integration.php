<?php
/**
 * Job Queue Integration Test
 * Tests the complete job queue system functionality
 */

require_once __DIR__ . '/ProgressiveHistoricalLoaderV2.php';
require_once __DIR__ . '/JobQueue.php';
require_once __DIR__ . '/JobWorker.php';

echo "=== JOB QUEUE SYSTEM INTEGRATION TEST ===\n\n";

try {
    // Test 1: Initialize job queue system
    echo "1. Initializing job queue system...\n";
    $loader = new ProgressiveHistoricalLoaderV2();
    echo "   ✅ ProgressiveHistoricalLoaderV2 initialized\n";
    
    // Test 2: Queue a single symbol job
    echo "\n2. Queueing single symbol job (AAPL)...\n";
    $result = $loader->loadSymbol('AAPL', '2024-09-01', 1);
    
    if ($result['success']) {
        echo "   ✅ Job queued successfully: #{$result['job_id']}\n";
        echo "   📊 Estimated chunks: {$result['estimated_chunks']}\n";
        $jobId = $result['job_id'];
    } else {
        throw new Exception("Failed to queue single symbol job");
    }
    
    // Test 3: Check job status
    echo "\n3. Checking job status...\n";
    $progress = $loader->getJobProgress($jobId);
    echo "   📈 Job #{$jobId} Status: {$progress['status']}\n";
    echo "   📊 Progress: {$progress['progress']['current']}/{$progress['progress']['total']} ({$progress['progress']['percentage']}%)\n";
    
    // Test 4: Process the job
    echo "\n4. Processing job manually...\n";
    $processResult = $loader->processNextJob(25);
    
    if ($processResult['status'] !== 'no_jobs') {
        echo "   ⚡ Processed job #{$processResult['job_id']} - Status: {$processResult['status']}\n";
        
        if (isset($processResult['execution_time'])) {
            echo "   ⏱️  Execution time: {$processResult['execution_time']} seconds\n";
        }
    } else {
        echo "   ℹ️  No jobs to process (might have been processed already)\n";
    }
    
    // Test 5: Check updated status
    echo "\n5. Checking updated job status...\n";
    $updatedProgress = $loader->getJobProgress($jobId);
    echo "   📈 Job #{$jobId} Status: {$updatedProgress['status']}\n";
    echo "   📊 Progress: {$updatedProgress['progress']['current']}/{$updatedProgress['progress']['total']} ({$updatedProgress['progress']['percentage']}%)\n";
    
    // Test 6: Data coverage analysis
    echo "\n6. Analyzing data coverage for AAPL...\n";
    $coverage = $loader->getDataCoverage('AAPL');
    
    if ($coverage['has_data']) {
        echo "   📊 Records: " . number_format($coverage['record_count']) . "\n";
        echo "   📅 Date range: {$coverage['date_range']['start']} to {$coverage['date_range']['end']}\n";
        echo "   📈 Completeness: {$coverage['completeness_percentage']}%\n";
        echo "   🔄 Needs update: " . ($coverage['needs_update'] ? 'Yes' : 'No') . "\n";
        
        if (!empty($coverage['gaps'])) {
            echo "   ⚠️  Data gaps found: " . count($coverage['gaps']) . "\n";
        } else {
            echo "   ✅ No significant data gaps\n";
        }
    } else {
        echo "   ℹ️  No data found for AAPL yet\n";
    }
    
    // Test 7: Queue portfolio job (small test)
    echo "\n7. Queueing small portfolio job...\n";
    $portfolioResult = $loader->loadMultipleSymbols(['MSFT', 'GOOGL'], '2024-09-01', 1);
    
    if ($portfolioResult['success']) {
        echo "   ✅ Portfolio job queued: #{$portfolioResult['job_id']}\n";
        echo "   📊 Symbols: " . count($portfolioResult['symbols']) . "\n";
        echo "   📊 Estimated chunks: {$portfolioResult['estimated_chunks']}\n";
    } else {
        echo "   ❌ Failed to queue portfolio job\n";
    }
    
    // Test 8: List all jobs
    echo "\n8. Listing recent jobs...\n";
    $allJobs = $loader->getAllJobs(null, 10);
    echo "   📋 Total jobs: " . count($allJobs) . "\n";
    
    foreach ($allJobs as $job) {
        $statusIcon = [
            'pending' => '⏳',
            'processing' => '⚡',
            'completed' => '✅',
            'failed' => '❌',
            'paused' => '⏸️'
        ][$job['status']] ?? '❓';
        
        echo "   {$statusIcon} Job #{$job['id']}: {$job['job_type']} - {$job['status']} ({$job['progress_percentage']}%)\n";
    }
    
    echo "\n=== INTEGRATION TEST COMPLETED SUCCESSFULLY ===\n";
    echo "\n💡 Next Steps:\n";
    echo "   1. Open web_ui/job_manager.html for visual job management\n";
    echo "   2. Run 'php JobWorker.php run' for continuous processing\n";
    echo "   3. Set up Windows Task Scheduler with run_worker.bat\n";
    echo "   4. Use web_ui/admin/progressive_data_loader.php for advanced loading\n";
    
} catch (Exception $e) {
    echo "\n❌ INTEGRATION TEST FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    
    exit(1);
}

echo "\n🎯 The job queue system is ready to solve your timeout problems!\n";
?>