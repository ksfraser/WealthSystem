/**
 * Chart Service - Centralized charting utilities using Chart.js
 * 
 * Provides reusable chart creation functions for sector analysis and index benchmarking.
 * 
 * Dependencies:
 * - Chart.js 4.x
 * - jQuery (for AJAX)
 * 
 * @version 1.0.0
 * @author Development Team
 */

const ChartService = {
    /**
     * Default chart colors palette
     */
    colors: [
        'rgba(54, 162, 235, 0.8)',   // Blue
        'rgba(255, 99, 132, 0.8)',   // Red
        'rgba(255, 206, 86, 0.8)',   // Yellow
        'rgba(75, 192, 192, 0.8)',   // Teal
        'rgba(153, 102, 255, 0.8)',  // Purple
        'rgba(255, 159, 64, 0.8)',   // Orange
        'rgba(199, 199, 199, 0.8)',  // Gray
        'rgba(83, 102, 255, 0.8)',   // Indigo
        'rgba(255, 99, 255, 0.8)',   // Pink
        'rgba(99, 255, 132, 0.8)',   // Green
        'rgba(255, 199, 64, 0.8)'    // Gold
    ],

    /**
     * Create sector allocation pie chart
     * 
     * @param {string} canvasId Canvas element ID
     * @param {Object} data Chart data from API
     * @returns {Chart} Chart.js instance
     */
    createSectorPieChart(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        return new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.datasets[0].data,
                    backgroundColor: this.colors,
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                return data.labels.map((label, i) => {
                                    const value = data.datasets[0].data[i];
                                    return {
                                        text: `${label}: ${value.toFixed(1)}%`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed.toFixed(2)}%`;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Portfolio Sector Allocation',
                        font: {
                            size: 18,
                            weight: 'bold'
                        }
                    }
                }
            }
        });
    },

    /**
     * Create sector comparison bar chart
     * 
     * @param {string} canvasId Canvas element ID
     * @param {Object} data Chart data from API
     * @returns {Chart} Chart.js instance
     */
    createSectorComparisonChart(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Your Portfolio',
                        data: data.datasets[0].data,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'S&P 500',
                        data: data.datasets[1].data,
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y.toFixed(2)}%`;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Portfolio vs S&P 500 Sector Allocation',
                        font: {
                            size: 18,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Allocation (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Sector'
                        }
                    }
                }
            }
        });
    },

    /**
     * Create index performance line chart
     * 
     * @param {string} canvasId Canvas element ID
     * @param {Object} data Chart data from API
     * @returns {Chart} Chart.js instance
     */
    createIndexPerformanceChart(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: data.datasets.map((dataset, index) => ({
                    label: dataset.label,
                    data: dataset.data,
                    borderColor: this.colors[index],
                    backgroundColor: this.colors[index].replace('0.8', '0.1'),
                    fill: dataset.fill || false,
                    tension: dataset.tension || 0.1,
                    borderWidth: 2,
                    pointRadius: 2,
                    pointHoverRadius: 5
                }))
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y.toFixed(2)}%`;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Performance vs Index',
                        font: {
                            size: 18,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    y: {
                        title: {
                            display: true,
                            text: 'Return (%)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1) + '%';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });
    },

    /**
     * Fetch sector analysis data from API
     * 
     * @param {number} userId User ID
     * @returns {Promise<Object>} Sector analysis data
     */
    async fetchSectorAnalysis(userId) {
        try {
            const response = await fetch(`/api/sector-analysis.php?user_id=${userId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Error fetching sector analysis:', error);
            throw error;
        }
    },

    /**
     * Fetch index benchmark data from API
     * 
     * @param {string} symbol Portfolio/stock symbol
     * @param {string} indexSymbol Index symbol (SPX, IXIC, DJI)
     * @param {string} period Time period (1M, 3M, 6M, 1Y, 3Y, 5Y)
     * @returns {Promise<Object>} Index benchmark data
     */
    async fetchIndexBenchmark(symbol, indexSymbol, period) {
        try {
            const params = new URLSearchParams({
                symbol: symbol,
                index: indexSymbol,
                period: period
            });
            const response = await fetch(`/api/index-benchmark.php?${params}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('Error fetching index benchmark:', error);
            throw error;
        }
    },

    /**
     * Destroy chart instance if it exists
     * 
     * @param {Chart} chart Chart.js instance
     */
    destroyChart(chart) {
        if (chart) {
            chart.destroy();
        }
    },

    /**
     * Format metrics table HTML
     * 
     * @param {Object} metricsData Metrics data from API
     * @returns {string} HTML table string
     */
    formatMetricsTable(metricsData) {
        if (!metricsData || !metricsData.rows) {
            return '<p>No metrics data available</p>';
        }

        let html = '<table class="table table-striped table-hover">';
        html += '<thead><tr>';
        
        metricsData.headers.forEach(header => {
            html += `<th>${header}</th>`;
        });
        
        html += '</tr></thead><tbody>';
        
        metricsData.rows.forEach(row => {
            html += '<tr>';
            html += `<td><strong>${row.metric}</strong></td>`;
            html += `<td>${row.portfolio}</td>`;
            html += `<td>${row.index}</td>`;
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        return html;
    },

    /**
     * Show loading spinner
     * 
     * @param {string} containerId Container element ID
     */
    showLoading(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>';
        }
    },

    /**
     * Show error message
     * 
     * @param {string} containerId Container element ID
     * @param {string} message Error message
     */
    showError(containerId, message) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `<div class="alert alert-danger" role="alert">${message}</div>`;
        }
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChartService;
}
