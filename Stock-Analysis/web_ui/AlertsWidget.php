<?php
/**
 * Alerts Widget
 * 
 * Displays active alerts for the current user
 * Can be included in any page
 */
?>
<div class="card border-warning mb-3" id="alertsWidget">
    <div class="card-header bg-warning text-dark">
        <strong>⚠️ Active Alerts</strong>
        <span class="badge bg-dark float-end" id="alertCount">0</span>
    </div>
    <div class="card-body" id="alertsBody">
        <div class="text-center text-muted">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            Loading alerts...
        </div>
    </div>
</div>

<style>
    .alert-item {
        border-left: 4px solid #ffc107;
        padding: 10px;
        margin-bottom: 10px;
        background: #fff8e1;
    }
    .alert-item.critical {
        border-left-color: #dc3545;
        background: #f8d7da;
    }
    .alert-item.warning {
        border-left-color: #ffc107;
        background: #fff8e1;
    }
    .alert-item.info {
        border-left-color: #17a2b8;
        background: #d1ecf1;
    }
    .alert-item h6 {
        margin: 0 0 5px 0;
        font-weight: bold;
    }
    .alert-item p {
        margin: 0 0 10px 0;
        font-size: 0.9rem;
    }
    .alert-item small {
        color: #666;
    }
    .alert-actions {
        margin-top: 10px;
    }
    .alert-actions button {
        font-size: 0.8rem;
        padding: 2px 8px;
    }
</style>

<script>
/**
 * Alerts Widget JavaScript
 */
const AlertsWidget = {
    userId: 1, // TODO: Get from session
    
    /**
     * Initialize alerts widget
     */
    init() {
        this.loadAlerts();
        // Refresh alerts every 2 minutes
        setInterval(() => this.loadAlerts(), 120000);
    },
    
    /**
     * Load alerts from API
     */
    async loadAlerts() {
        try {
            const response = await fetch(`/api/alerts.php?action=generate&user_id=${this.userId}`);
            const result = await response.json();
            
            if (result.success) {
                this.displayAlerts(result.data);
            } else {
                this.showError(result.error);
            }
        } catch (error) {
            console.error('Failed to load alerts:', error);
            this.showError('Failed to load alerts');
        }
    },
    
    /**
     * Display alerts in widget
     */
    displayAlerts(alerts) {
        const alertsBody = document.getElementById('alertsBody');
        const alertCount = document.getElementById('alertCount');
        
        alertCount.textContent = alerts.length;
        
        if (alerts.length === 0) {
            alertsBody.innerHTML = '<p class="text-success mb-0"><i class="bi bi-check-circle"></i> No active alerts</p>';
            return;
        }
        
        let html = '';
        alerts.forEach((alert, index) => {
            html += this.renderAlert(alert, index);
        });
        
        alertsBody.innerHTML = html;
    },
    
    /**
     * Render single alert
     */
    renderAlert(alert, index) {
        const severityIcon = {
            'critical': '🔴',
            'warning': '⚠️',
            'info': 'ℹ️'
        }[alert.severity] || '⚠️';
        
        return `
            <div class="alert-item ${alert.severity}" id="alert-${index}">
                <h6>${severityIcon} ${alert.title}</h6>
                <p>${alert.message}</p>
                ${alert.recommendation ? `<small><strong>Recommendation:</strong> ${alert.recommendation}</small>` : ''}
                <div class="alert-actions">
                    <button class="btn btn-sm btn-outline-secondary" onclick="AlertsWidget.dismissAlert(${index})">
                        Dismiss
                    </button>
                    ${alert.action_required ? '<span class="badge bg-danger ms-2">Action Required</span>' : ''}
                </div>
            </div>
        `;
    },
    
    /**
     * Dismiss alert
     */
    async dismissAlert(index) {
        // Remove from DOM
        const alertElement = document.getElementById(`alert-${index}`);
        if (alertElement) {
            alertElement.remove();
        }
        
        // Update count
        const alertCount = document.getElementById('alertCount');
        const currentCount = parseInt(alertCount.textContent);
        alertCount.textContent = Math.max(0, currentCount - 1);
        
        // If no alerts left, show success message
        const alertsBody = document.getElementById('alertsBody');
        if (alertsBody.children.length === 0) {
            alertsBody.innerHTML = '<p class="text-success mb-0"><i class="bi bi-check-circle"></i> No active alerts</p>';
        }
        
        // TODO: Call API to dismiss alert permanently
        // await fetch(`/api/alerts.php?action=dismiss&alert_id=${alertId}`);
    },
    
    /**
     * Show error message
     */
    showError(message) {
        const alertsBody = document.getElementById('alertsBody');
        alertsBody.innerHTML = `<p class="text-danger mb-0"><i class="bi bi-exclamation-circle"></i> ${message}</p>`;
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    AlertsWidget.init();
});
</script>
