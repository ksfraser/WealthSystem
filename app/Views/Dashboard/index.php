<div class="row">
    <div class="col-md-8">
        <!-- Portfolio Summary Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Portfolio Overview</h5>
                <button class="btn btn-sm btn-outline-primary" onclick="refreshPortfolio()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="card-body">
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-warning">
                        <h5>Data Loading Issue</h5>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                        <?php if (isset($debug_info)): ?>
                            <hr>
                            <small class="text-muted">
                                <strong>Debug Info:</strong><br>
                                File: <?php echo htmlspecialchars($debug_info['file']); ?><br>
                                Line: <?php echo htmlspecialchars($debug_info['line']); ?><br>
                                Error: <?php echo htmlspecialchars($debug_info['error']); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($portfolioData) && is_array($portfolioData)): ?>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-primary">$<?php echo number_format($portfolioData['total_value'] ?? 0, 2); ?></h4>
                                <small class="text-muted">Total Portfolio Value</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="<?php echo ($portfolioData['daily_change'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($portfolioData['daily_change'] ?? 0) >= 0 ? '+' : ''; ?>$<?php echo number_format($portfolioData['daily_change'] ?? 0, 2); ?>
                                </h4>
                                <small class="text-muted">Today's Change</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="<?php echo ($portfolioData['total_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($portfolioData['total_return'] ?? 0) >= 0 ? '+' : ''; ?>$<?php echo number_format($portfolioData['total_return'] ?? 0, 2); ?>
                                </h4>
                                <small class="text-muted">Total Return</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h4 class="text-info"><?php echo number_format($portfolioData['stock_count'] ?? 0); ?></h4>
                                <small class="text-muted">Holdings</small>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No portfolio data available - checking data sources...</p>
                        <div class="alert alert-info">
                            <strong>Expected Data Sources:</strong>
                            <ul class="text-left mb-0">
                                <li>Scripts and CSV Files/chatgpt_portfolio_update.csv</li>
                                <li>DynamicStockDataAccess database tables</li>
                                <li>Python trading_script.py integration</li>
                            </ul>
                        </div>
                        <a href="/bank-import" class="btn btn-primary">Import Bank Data</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Holdings Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Current Holdings</h5>
            </div>
            <div class="card-body p-0">
                <?php if (isset($holdings) && is_array($holdings) && count($holdings) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Symbol</th>
                                    <th>Company</th>
                                    <th class="text-end">Shares</th>
                                    <th class="text-end">Current Price</th>
                                    <th class="text-end">Market Value</th>
                                    <th class="text-end">Day Change</th>
                                    <th class="text-end">Total Return</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($holdings as $holding): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($holding['symbol'] ?? ''); ?></strong></td>
                                        <td><?php echo htmlspecialchars($holding['company_name'] ?? ''); ?></td>
                                        <td class="text-end"><?php echo number_format($holding['shares'] ?? 0, 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($holding['current_price'] ?? 0, 2); ?></td>
                                        <td class="text-end">$<?php echo number_format($holding['market_value'] ?? 0, 2); ?></td>
                                        <td class="text-end <?php echo ($holding['day_change'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo ($holding['day_change'] ?? 0) >= 0 ? '+' : ''; ?>$<?php echo number_format($holding['day_change'] ?? 0, 2); ?>
                                        </td>
                                        <td class="text-end <?php echo ($holding['total_return'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            <?php echo ($holding['total_return'] ?? 0) >= 0 ? '+' : ''; ?>$<?php echo number_format($holding['total_return'] ?? 0, 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No holdings found</p>
                        <small class="text-muted">Import your bank statements to view your portfolio</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Market Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Market Summary</h5>
            </div>
            <div class="card-body">
                <?php if (isset($marketData) && is_array($marketData)): ?>
                    <?php foreach ($marketData as $index): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?php echo htmlspecialchars($index['name'] ?? ''); ?></span>
                            <span class="<?php echo ($index['change'] ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo htmlspecialchars($index['value'] ?? ''); ?>
                                (<?php echo ($index['change'] ?? 0) >= 0 ? '+' : ''; ?><?php echo htmlspecialchars($index['change'] ?? ''); ?>%)
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Market data unavailable</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php if (isset($recentActivity) && is_array($recentActivity) && count($recentActivity) > 0): ?>
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <div>
                                <strong><?php echo htmlspecialchars($activity['action'] ?? ''); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($activity['symbol'] ?? ''); ?></small>
                            </div>
                            <div class="text-end">
                                <div><?php echo htmlspecialchars($activity['amount'] ?? ''); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($activity['date'] ?? ''); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No recent activity</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function refreshPortfolio() {
    // Add refresh functionality
    location.reload();
}
</script>