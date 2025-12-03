<?php
/**
 * Job Queue Manager - Requires Authentication
 * 
 * Allows authenticated users to view and manage background jobs.
 */

// Load DI Container
$container = require_once __DIR__ . '/bootstrap.php';

// Resolve authentication service
$auth = $container->get(UserAuthDAO::class);

// Require login with proper exception handling
try {
    $auth->requireLogin();
    $user = $auth->getCurrentUser();
} catch (\App\Auth\LoginRequiredException $e) {
    $returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'job_manager.php');
    header('Location: login.php?return_url=' . $returnUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Queue Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .job-card {
            border-left: 4px solid #6c757d;
            margin-bottom: 1rem;
        }
        .job-card.pending { border-left-color: #ffc107; }
        .job-card.processing { border-left-color: #0dcaf0; }
        .job-card.completed { border-left-color: #198754; }
        .job-card.failed { border-left-color: #dc3545; }
        .job-card.paused { border-left-color: #fd7e14; }
        
        .progress-ring {
            transform: rotate(-90deg);
        }
        
        .status-badge {
            font-size: 0.75em;
        }
        
        .job-actions {
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .job-card:hover .job-actions {
            opacity: 1;
        }
        
        .auto-refresh {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .stats-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .loading-spinner {
            display: none;
        }
        
        .loading .loading-spinner {
            display: inline-block;
        }
        
        .json-viewer {
            background: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.875em;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Auto-refresh toggle -->
    <div class="auto-refresh">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
            <label class="form-check-label" for="autoRefresh">
                <i class="fas fa-sync-alt"></i> Auto Refresh
            </label>
        </div>
    </div>

    <!-- User Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <span class="navbar-brand">⏱️ Job Queue Manager</span>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                <a href="dashboard.php" class="btn btn-sm btn-light me-2">Dashboard</a>
                <a href="strategy-config.php" class="btn btn-sm btn-light me-2">Strategy Config</a>
                <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-tasks"></i> Job Queue Manager</h1>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newJobModal">
                            <i class="fas fa-plus"></i> New Job
                        </button>
                        <button type="button" class="btn btn-success" onclick="processNextJob()">
                            <i class="fas fa-play"></i> Process Next
                        </button>
                        <button type="button" class="btn btn-info" onclick="refreshJobs()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4" id="statsCards">
                    <div class="col-md-2">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <i class="fas fa-clock text-warning fa-2x mb-2"></i>
                                <h5 class="card-title mb-0" id="pendingCount">0</h5>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <i class="fas fa-spinner text-info fa-2x mb-2"></i>
                                <h5 class="card-title mb-0" id="processingCount">0</h5>
                                <small class="text-muted">Processing</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <i class="fas fa-check text-success fa-2x mb-2"></i>
                                <h5 class="card-title mb-0" id="completedCount">0</h5>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <i class="fas fa-exclamation-triangle text-danger fa-2x mb-2"></i>
                                <h5 class="card-title mb-0" id="failedCount">0</h5>
                                <small class="text-muted">Failed</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <i class="fas fa-pause text-warning fa-2x mb-2"></i>
                                <h5 class="card-title mb-0" id="pausedCount">0</h5>
                                <small class="text-muted">Paused</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stats-card text-center">
                            <div class="card-body">
                                <i class="fas fa-list text-secondary fa-2x mb-2"></i>
                                <h5 class="card-title mb-0" id="totalCount">0</h5>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs -->
                <ul class="nav nav-tabs mb-3" id="statusFilter">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-filter="all">All Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-filter="pending">Pending</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-filter="processing">Processing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-filter="completed">Completed</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-filter="failed">Failed</a>
                    </li>
                </ul>

                <!-- Jobs List -->
                <div id="jobsList" class="mb-4">
                    <!-- Jobs will be loaded here -->
                </div>

                <!-- Loading indicator -->
                <div class="text-center loading-spinner">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Job Modal -->
    <div class="modal fade" id="newJobModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Job</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newJobForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Job Type</label>
                            <select class="form-select" id="jobType" required>
                                <option value="">Select job type...</option>
                                <option value="stock_load">Single Stock Load</option>
                                <option value="portfolio_load">Portfolio Load</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="symbolField">
                            <label class="form-label">Stock Symbol(s)</label>
                            <input type="text" class="form-control" id="symbols" 
                                   placeholder="e.g., AAPL or AAPL,MSFT,GOOGL">
                            <div class="form-text">For portfolio jobs, separate symbols with commas</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Start Date (Optional)</label>
                            <input type="date" class="form-control" id="startDate">
                            <div class="form-text">Leave empty to start from 5 years ago</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" id="priority">
                                <option value="0">Normal</option>
                                <option value="1">High</option>
                                <option value="2">Urgent</option>
                                <option value="-1">Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Job
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Job Details Modal -->
    <div class="modal fade" id="jobDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Job Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="jobDetailsContent">
                    <!-- Job details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentFilter = 'all';
        let autoRefreshInterval;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            refreshJobs();
            setupAutoRefresh();
            setupEventListeners();
        });

        // Setup event listeners
        function setupEventListeners() {
            // Filter tabs
            document.querySelectorAll('#statusFilter a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Update active tab
                    document.querySelectorAll('#statusFilter a').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update filter
                    currentFilter = this.dataset.filter;
                    refreshJobs();
                });
            });

            // New job form
            document.getElementById('newJobForm').addEventListener('submit', function(e) {
                e.preventDefault();
                createJob();
            });

            // Auto refresh toggle
            document.getElementById('autoRefresh').addEventListener('change', function() {
                setupAutoRefresh();
            });
        }

        // Setup auto refresh
        function setupAutoRefresh() {
            clearInterval(autoRefreshInterval);
            
            if (document.getElementById('autoRefresh').checked) {
                autoRefreshInterval = setInterval(refreshJobs, 5000); // Refresh every 5 seconds
            }
        }

        // Refresh jobs list
        async function refreshJobs() {
            try {
                document.body.classList.add('loading');
                
                const response = await fetch('job_manager_api.php?action=list&status=' + 
                                           (currentFilter === 'all' ? '' : currentFilter));
                const data = await response.json();
                
                if (data.success) {
                    updateStatistics(data.jobs);
                    renderJobs(data.jobs);
                } else {
                    showAlert('Error loading jobs: ' + data.error, 'danger');
                }
            } catch (error) {
                showAlert('Network error: ' + error.message, 'danger');
            } finally {
                document.body.classList.remove('loading');
            }
        }

        // Update statistics cards
        function updateStatistics(jobs) {
            const stats = jobs.reduce((acc, job) => {
                acc[job.status] = (acc[job.status] || 0) + 1;
                acc.total++;
                return acc;
            }, { pending: 0, processing: 0, completed: 0, failed: 0, paused: 0, total: 0 });

            document.getElementById('pendingCount').textContent = stats.pending;
            document.getElementById('processingCount').textContent = stats.processing;
            document.getElementById('completedCount').textContent = stats.completed;
            document.getElementById('failedCount').textContent = stats.failed;
            document.getElementById('pausedCount').textContent = stats.paused;
            document.getElementById('totalCount').textContent = stats.total;
        }

        // Render jobs list
        function renderJobs(jobs) {
            const container = document.getElementById('jobsList');
            
            if (jobs.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No jobs found</h5>
                        <p class="text-muted">Create a new job to get started</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = jobs.map(job => `
                <div class="card job-card ${job.status}">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-1">
                                <h6 class="mb-0">#${job.id}</h6>
                            </div>
                            <div class="col-md-2">
                                <span class="badge bg-${getStatusColor(job.status)} status-badge">
                                    ${job.status.toUpperCase()}
                                </span>
                                <div class="small text-muted mt-1">${job.job_type}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fw-bold">${getJobTitle(job)}</div>
                                <div class="small text-muted">${getJobDescription(job)}</div>
                            </div>
                            <div class="col-md-2">
                                ${renderProgress(job)}
                            </div>
                            <div class="col-md-2">
                                <div class="small text-muted">Created: ${formatDateTime(job.created_at)}</div>
                                ${job.started_at ? `<div class="small text-muted">Started: ${formatDateTime(job.started_at)}</div>` : ''}
                                ${job.completed_at ? `<div class="small text-muted">Completed: ${formatDateTime(job.completed_at)}</div>` : ''}
                            </div>
                            <div class="col-md-2 text-end">
                                <div class="job-actions">
                                    <button class="btn btn-sm btn-outline-info" onclick="showJobDetails(${job.id})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    ${job.status === 'pending' || job.status === 'failed' ? `
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteJob(${job.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        
                        ${job.error_message ? `
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="alert alert-danger alert-sm mb-0">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        ${job.error_message}
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }

        // Helper functions
        function getStatusColor(status) {
            const colors = {
                pending: 'warning',
                processing: 'info',
                completed: 'success',
                failed: 'danger',
                paused: 'secondary'
            };
            return colors[status] || 'secondary';
        }

        function getJobTitle(job) {
            const data = job.data;
            switch (job.job_type) {
                case 'stock_load':
                    return `Stock: ${data.symbol}`;
                case 'portfolio_load':
                    return `Portfolio: ${data.symbols ? data.symbols.join(', ') : 'Multiple'}`;
                case 'data_chunk':
                    return `Chunk: ${data.symbol} (${data.start_date} to ${data.end_date})`;
                default:
                    return job.job_type;
            }
        }

        function getJobDescription(job) {
            const data = job.data;
            if (data.start_date) {
                return `From: ${data.start_date}`;
            }
            return 'Historical data load';
        }

        function renderProgress(job) {
            if (job.progress_total > 0) {
                const percentage = Math.round((job.progress_current / job.progress_total) * 100);
                return `
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-${getStatusColor(job.status)}" 
                             style="width: ${percentage}%"></div>
                    </div>
                    <div class="small text-muted mt-1">${job.progress_current}/${job.progress_total} (${percentage}%)</div>
                `;
            }
            return '<div class="small text-muted">No progress data</div>';
        }

        function formatDateTime(datetime) {
            return new Date(datetime).toLocaleString();
        }

        // Actions
        async function processNextJob() {
            try {
                showAlert('Processing next job...', 'info');
                
                const response = await fetch('job_manager_api.php?action=process', {
                    method: 'POST'
                });
                const data = await response.json();
                
                if (data.success) {
                    showAlert(`Job processed: ${data.result.status}`, 'success');
                    refreshJobs();
                } else {
                    showAlert('Error processing job: ' + data.error, 'danger');
                }
            } catch (error) {
                showAlert('Network error: ' + error.message, 'danger');
            }
        }

        async function createJob() {
            try {
                const formData = new FormData(document.getElementById('newJobForm'));
                const jobData = {
                    action: 'create',
                    job_type: formData.get('jobType'),
                    symbols: formData.get('symbols'),
                    start_date: formData.get('startDate') || null,
                    priority: parseInt(formData.get('priority'))
                };

                const response = await fetch('job_manager_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(jobData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(`Job created: #${data.job_id}`, 'success');
                    document.getElementById('newJobModal').querySelector('.btn-close').click();
                    document.getElementById('newJobForm').reset();
                    refreshJobs();
                } else {
                    showAlert('Error creating job: ' + data.error, 'danger');
                }
            } catch (error) {
                showAlert('Network error: ' + error.message, 'danger');
            }
        }

        async function showJobDetails(jobId) {
            try {
                const response = await fetch(`job_manager_api.php?action=details&job_id=${jobId}`);
                const data = await response.json();
                
                if (data.success) {
                    const job = data.job;
                    document.getElementById('jobDetailsContent').innerHTML = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Job ID:</strong> #${job.id}<br>
                                <strong>Type:</strong> ${job.job_type}<br>
                                <strong>Status:</strong> <span class="badge bg-${getStatusColor(job.status)}">${job.status}</span><br>
                                <strong>Priority:</strong> ${job.priority}<br>
                                <strong>Attempts:</strong> ${job.attempts}/${job.max_attempts}
                            </div>
                            <div class="col-md-6">
                                <strong>Created:</strong> ${formatDateTime(job.created_at)}<br>
                                ${job.started_at ? `<strong>Started:</strong> ${formatDateTime(job.started_at)}<br>` : ''}
                                ${job.completed_at ? `<strong>Completed:</strong> ${formatDateTime(job.completed_at)}<br>` : ''}
                                <strong>Progress:</strong> ${job.progress_current}/${job.progress_total} (${job.progress_percentage}%)
                            </div>
                        </div>
                        
                        ${job.error_message ? `
                            <div class="mb-3">
                                <strong>Error Message:</strong>
                                <div class="alert alert-danger">${job.error_message}</div>
                            </div>
                        ` : ''}
                        
                        <div class="mb-3">
                            <strong>Job Data:</strong>
                            <div class="json-viewer">${JSON.stringify(job.data, null, 2)}</div>
                        </div>
                        
                        ${job.result ? `
                            <div class="mb-3">
                                <strong>Result:</strong>
                                <div class="json-viewer">${JSON.stringify(job.result, null, 2)}</div>
                            </div>
                        ` : ''}
                    `;
                    
                    new bootstrap.Modal(document.getElementById('jobDetailsModal')).show();
                } else {
                    showAlert('Error loading job details: ' + data.error, 'danger');
                }
            } catch (error) {
                showAlert('Network error: ' + error.message, 'danger');
            }
        }

        async function deleteJob(jobId) {
            if (!confirm('Are you sure you want to delete this job?')) {
                return;
            }

            try {
                const response = await fetch('job_manager_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        job_id: jobId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('Job deleted successfully', 'success');
                    refreshJobs();
                } else {
                    showAlert('Error deleting job: ' + data.error, 'danger');
                }
            } catch (error) {
                showAlert('Network error: ' + error.message, 'danger');
            }
        }

        // Show alert messages
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 70px; right: 20px; z-index: 1060; max-width: 400px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>