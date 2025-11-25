        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Mobile sidebar toggle
        function toggleSidebar() {
            $('.sidebar').toggleClass('show');
        }
        
        // Close sidebar when clicking outside on mobile
        $(document).click(function(event) {
            if (!$(event.target).closest('.sidebar, .btn').length) {
                $('.sidebar').removeClass('show');
            }
        });
        
        // Initialize DataTables with default settings
        $(document).ready(function() {
            $('.data-table').DataTable({
                pageLength: 25,
                responsive: true,
                order: [[0, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search..."
                }
            });
        });
        
        // Global AJAX setup
        $.ajaxSetup({
            beforeSend: function() {
                $('.loading-spinner').show();
            },
            complete: function() {
                $('.loading-spinner').hide();
            },
            error: function(xhr, status, error) {
                showAlert('Error: ' + error, 'danger');
            }
        });
        
        // Show alert message
        function showAlert(message, type = 'info') {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('#alert-container').html(alertHtml);
        }
        
        // Progress bar update
        function updateProgress(jobId, callback) {
            $.get('/Legacy/ui/api/job_status.php', {id: jobId}, function(data) {
                if (data.status === 'success') {
                    const job = data.job;
                    const progressBar = $(`#progress-${jobId}`);
                    progressBar.css('width', job.progress + '%');
                    progressBar.text(job.progress + '%');
                    
                    if (job.status === 'COMPLETED') {
                        progressBar.removeClass('bg-primary').addClass('bg-success');
                        if (callback) callback(job);
                    } else if (job.status === 'FAILED') {
                        progressBar.removeClass('bg-primary').addClass('bg-danger');
                        showAlert('Job failed: ' + job.error_message, 'danger');
                    } else if (job.status === 'RUNNING') {
                        setTimeout(() => updateProgress(jobId, callback), 2000);
                    }
                }
            });
        }
        
        // Format numbers
        function formatNumber(num, decimals = 2) {
            if (num === null || num === undefined) return 'N/A';
            return parseFloat(num).toFixed(decimals);
        }
        
        // Format percentage
        function formatPercentage(num, decimals = 1) {
            if (num === null || num === undefined) return 'N/A';
            return parseFloat(num).toFixed(decimals) + '%';
        }
        
        // Format currency
        function formatCurrency(num, decimals = 2) {
            if (num === null || num === undefined) return 'N/A';
            return '$' + parseFloat(num).toFixed(decimals);
        }
        
        // Create candlestick chart
        function createCandlestickChart(containerId, data) {
            const chart = LightweightCharts.createChart(document.getElementById(containerId), {
                width: document.getElementById(containerId).clientWidth,
                height: 500,
                layout: {
                    backgroundColor: '#ffffff',
                    textColor: 'rgba(33, 56, 77, 1)',
                },
                grid: {
                    vertLines: {
                        color: 'rgba(197, 203, 206, 0.5)',
                    },
                    horzLines: {
                        color: 'rgba(197, 203, 206, 0.5)',
                    },
                },
                crosshair: {
                    mode: LightweightCharts.CrosshairMode.Normal,
                },
                rightPriceScale: {
                    borderColor: 'rgba(197, 203, 206, 0.8)',
                },
                timeScale: {
                    borderColor: 'rgba(197, 203, 206, 0.8)',
                },
            });
            
            const candlestickSeries = chart.addCandlestickSeries({
                upColor: 'rgba(38, 166, 154, 1)',
                downColor: 'rgba(239, 83, 80, 1)',
                borderDownColor: 'rgba(239, 83, 80, 1)',
                borderUpColor: 'rgba(38, 166, 154, 1)',
                wickDownColor: 'rgba(239, 83, 80, 1)',
                wickUpColor: 'rgba(38, 166, 154, 1)',
            });
            
            candlestickSeries.setData(data);
            
            return chart;
        }
        
        // Create line chart for indicators
        function createIndicatorChart(containerId, data, title) {
            const ctx = document.getElementById(containerId).getContext('2d');
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: title
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        }
        
        // Refresh data functionality
        function refreshData(url, containerId) {
            $.get(url, function(data) {
                $(containerId).html(data);
            });
        }
        
        // Auto-refresh for real-time data
        function startAutoRefresh(url, containerId, interval = 30000) {
            setInterval(() => refreshData(url, containerId), interval);
        }
    </script>
    
    <?php if (isset($additionalScripts)): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>
