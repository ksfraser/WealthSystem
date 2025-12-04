/**
 * Advanced Chart Service - Extended charting utilities for advanced visualizations
 * 
 * Provides heatmap, treemap, and historical trend chart functions.
 * 
 * Dependencies:
 * - Chart.js 4.x
 * - Chart.js Matrix plugin (for heatmaps)
 * - Chart.js Treemap plugin (for treemaps)
 * 
 * @version 1.0.0
 * @author Development Team
 */

const AdvancedChartService = {
    /**
     * Create correlation heatmap
     * 
     * @param {string} canvasId Canvas element ID
     * @param {Object} data Heatmap data from API
     * @returns {Chart} Chart.js instance
     */
    createCorrelationHeatmap(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        // Transform matrix data to Chart.js format
        const chartData = [];
        for (let y = 0; y < data.labels.length; y++) {
            for (let x = 0; x < data.labels.length; x++) {
                chartData.push({
                    x: x,
                    y: y,
                    v: data.data[y][x]
                });
            }
        }

        return new Chart(ctx, {
            type: 'matrix',
            data: {
                datasets: [{
                    label: 'Correlation',
                    data: chartData,
                    backgroundColor(context) {
                        const value = context.dataset.data[context.dataIndex].v;
                        return AdvancedChartService.getCorrelationColor(value);
                    },
                    borderWidth: 1,
                    borderColor: 'rgba(0, 0, 0, 0.1)',
                    width(context) {
                        const a = context.chart.chartArea;
                        return (a.right - a.left) / data.labels.length;
                    },
                    height(context) {
                        const a = context.chart.chartArea;
                        return (a.bottom - a.top) / data.labels.length;
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title() {
                                return '';
                            },
                            label(context) {
                                const v = context.dataset.data[context.dataIndex];
                                const xLabel = data.labels[v.x];
                                const yLabel = data.labels[v.y];
                                return `${yLabel} vs ${xLabel}: ${v.v.toFixed(3)}`;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Sector Correlation Matrix',
                        font: {
                            size: 18,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'category',
                        labels: data.labels,
                        offset: true,
                        ticks: {
                            display: true
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        type: 'category',
                        labels: data.labels,
                        offset: true,
                        ticks: {
                            display: true
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    },

    /**
     * Get color for correlation value
     * 
     * @param {number} correlation Correlation value (-1 to +1)
     * @returns {string} RGBA color string
     */
    getCorrelationColor(correlation) {
        if (correlation > 0.7) {
            return 'rgba(0, 150, 0, 0.8)'; // Dark green
        } else if (correlation > 0.3) {
            return 'rgba(100, 200, 100, 0.6)'; // Light green
        } else if (correlation > -0.3) {
            return 'rgba(200, 200, 200, 0.4)'; // Gray
        } else if (correlation > -0.7) {
            return 'rgba(200, 100, 100, 0.6)'; // Light red
        } else {
            return 'rgba(150, 0, 0, 0.8)'; // Dark red
        }
    },

    /**
     * Create portfolio treemap
     * 
     * @param {string} canvasId Canvas element ID
     * @param {Object} data Treemap data from API
     * @returns {Chart} Chart.js instance
     */
    createPortfolioTreemap(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        return new Chart(ctx, {
            type: 'treemap',
            data: {
                datasets: [{
                    tree: data.children,
                    key: 'value',
                    groups: ['name'],
                    spacing: 0.5,
                    borderWidth: 2,
                    borderColor: 'white',
                    backgroundColor(context) {
                        if (context.type !== 'data') return 'transparent';
                        const data = context.raw._data;
                        return data.color || 'rgba(100, 100, 100, 0.3)';
                    },
                    labels: {
                        display: true,
                        formatter(context) {
                            if (context.type !== 'data') return '';
                            const data = context.raw._data;
                            const symbol = data.name;
                            const returnPct = data.return;
                            return [`${symbol}`, `${returnPct > 0 ? '+' : ''}${returnPct.toFixed(1)}%`];
                        },
                        color: 'white',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title(items) {
                                const data = items[0].raw._data;
                                return data.name;
                            },
                            label(context) {
                                const data = context.raw._data;
                                return [
                                    `Value: $${data.value.toLocaleString()}`,
                                    `Return: ${data.return > 0 ? '+' : ''}${data.return.toFixed(2)}%`,
                                    `Sector: ${context.raw.g || 'Unknown'}`
                                ];
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Portfolio Composition Treemap',
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
     * Create historical sector trends chart
     * 
     * @param {string} canvasId Canvas element ID
     * @param {Object} data Historical trend data from API
     * @returns {Chart} Chart.js instance
     */
    createHistoricalTrendsChart(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: data.datasets
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
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                return `${context.dataset.label}: ${context.parsed.y.toFixed(2)}%`;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Historical Sector Allocation Trends',
                        font: {
                            size: 18,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Allocation (%)'
                        },
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    },

    /**
     * Create concentration trend chart
     * 
     * @param {string} canvasId Canvas element ID
     * @param {Object} data Concentration trend data
     * @returns {Chart} Chart.js instance
     */
    createConcentrationTrendChart(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        const dates = Object.keys(data);
        const values = Object.values(data);

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'HHI (Concentration)',
                    data: values,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
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
                            label(context) {
                                const hhi = context.parsed.y;
                                let riskLevel = 'Highly Diversified';
                                if (hhi >= 2500) riskLevel = 'Highly Concentrated';
                                else if (hhi >= 1500) riskLevel = 'Moderately Concentrated';
                                
                                return [
                                    `HHI: ${hhi.toFixed(2)}`,
                                    `Risk: ${riskLevel}`
                                ];
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Portfolio Concentration Over Time',
                        font: {
                            size: 18,
                            weight: 'bold'
                        }
                    },
                    annotation: {
                        annotations: {
                            low: {
                                type: 'line',
                                yMin: 1500,
                                yMax: 1500,
                                borderColor: 'rgba(255, 206, 86, 0.5)',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                label: {
                                    content: 'Moderate Risk Threshold',
                                    enabled: true,
                                    position: 'end'
                                }
                            },
                            high: {
                                type: 'line',
                                yMin: 2500,
                                yMax: 2500,
                                borderColor: 'rgba(255, 99, 132, 0.5)',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                label: {
                                    content: 'High Risk Threshold',
                                    enabled: true,
                                    position: 'end'
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'HHI Index'
                        },
                        beginAtZero: true,
                        max: 10000
                    }
                }
            }
        });
    },

    /**
     * Fetch correlation heatmap data
     * 
     * @param {number} userId User ID
     * @param {string} period Time period for returns
     * @returns {Promise<Object>} Heatmap data
     */
    async fetchCorrelationHeatmap(userId, period = '1Y') {
        try {
            const response = await fetch(`/api/advanced-charts.php?action=correlation&user_id=${userId}&period=${period}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to fetch correlation data');
            }
            
            return data.data;
        } catch (error) {
            console.error('Error fetching correlation heatmap:', error);
            throw error;
        }
    },

    /**
     * Fetch treemap data
     * 
     * @param {number} userId User ID
     * @returns {Promise<Object>} Treemap data
     */
    async fetchTreemapData(userId) {
        try {
            const response = await fetch(`/api/advanced-charts.php?action=treemap&user_id=${userId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to fetch treemap data');
            }
            
            return data.data;
        } catch (error) {
            console.error('Error fetching treemap data:', error);
            throw error;
        }
    },

    /**
     * Fetch historical trends data
     * 
     * @param {number} userId User ID
     * @param {string} startDate Start date (YYYY-MM-DD)
     * @param {string} endDate End date (YYYY-MM-DD)
     * @returns {Promise<Object>} Historical trends data
     */
    async fetchHistoricalTrends(userId, startDate, endDate) {
        try {
            const response = await fetch(`/api/advanced-charts.php?action=trends&user_id=${userId}&start_date=${startDate}&end_date=${endDate}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to fetch historical trends');
            }
            
            return data.data;
        } catch (error) {
            console.error('Error fetching historical trends:', error);
            throw error;
        }
    },

    /**
     * Fetch rebalancing suggestions
     * 
     * @param {number} userId User ID
     * @returns {Promise<Object>} Rebalancing suggestions
     */
    async fetchRebalancingSuggestions(userId) {
        try {
            const response = await fetch(`/api/advanced-charts.php?action=rebalancing&user_id=${userId}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to fetch rebalancing suggestions');
            }
            
            return data.data;
        } catch (error) {
            console.error('Error fetching rebalancing suggestions:', error);
            throw error;
        }
    },

    /**
     * Display rebalancing suggestions in HTML
     * 
     * @param {string} containerId Container element ID
     * @param {Object} suggestions Rebalancing suggestions data
     */
    displayRebalancingSuggestions(containerId, suggestions) {
        const container = document.getElementById(containerId);
        
        if (!suggestions.rebalancing_required) {
            container.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Portfolio is well-balanced. No rebalancing needed.</div>';
            return;
        }

        let html = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Rebalancing recommended</div>';

        if (suggestions.overweight.length > 0) {
            html += '<h5>Overweight Sectors (Reduce)</h5>';
            html += '<table class="table table-sm table-striped">';
            html += '<thead><tr><th>Sector</th><th>Current</th><th>Target</th><th>Difference</th></tr></thead><tbody>';
            
            suggestions.overweight.forEach(item => {
                html += `<tr>
                    <td>${item.sector}</td>
                    <td>${item.current.toFixed(2)}%</td>
                    <td>${item.target.toFixed(2)}%</td>
                    <td class="text-danger">+${item.difference.toFixed(2)}%</td>
                </tr>`;
            });
            
            html += '</tbody></table>';
        }

        if (suggestions.underweight.length > 0) {
            html += '<h5>Underweight Sectors (Increase)</h5>';
            html += '<table class="table table-sm table-striped">';
            html += '<thead><tr><th>Sector</th><th>Current</th><th>Target</th><th>Difference</th></tr></thead><tbody>';
            
            suggestions.underweight.forEach(item => {
                html += `<tr>
                    <td>${item.sector}</td>
                    <td>${item.current.toFixed(2)}%</td>
                    <td>${item.target.toFixed(2)}%</td>
                    <td class="text-success">${item.difference.toFixed(2)}%</td>
                </tr>`;
            });
            
            html += '</tbody></table>';
        }

        container.innerHTML = html;
    },

    /**
     * Show loading spinner
     * 
     * @param {string} containerId Container element ID
     */
    showLoading(containerId) {
        const container = document.getElementById(containerId);
        container.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    },

    /**
     * Show error message
     * 
     * @param {string} containerId Container element ID
     * @param {string} message Error message
     */
    showError(containerId, message) {
        const container = document.getElementById(containerId);
        container.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> ${message}</div>`;
    }
};
