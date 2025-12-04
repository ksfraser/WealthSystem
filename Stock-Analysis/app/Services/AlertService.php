<?php

namespace App\Services;

use App\DAO\SectorAnalysisDAO;
use DateTime;

/**
 * Alert Service
 * 
 * Monitors portfolio metrics and generates alerts for:
 * - Concentration risk (HHI thresholds)
 * - Rebalancing needs (sector deviation from target)
 * - Performance warnings (underperformance periods)
 * 
 * @package App\Services
 */
class AlertService
{
    private SectorAnalysisDAO $dao;
    private array $config;
    
    // Alert thresholds
    private const HHI_MODERATE_THRESHOLD = 1500;
    private const HHI_HIGH_THRESHOLD = 2500;
    private const REBALANCING_THRESHOLD = 5.0; // 5% deviation
    private const UNDERPERFORMANCE_THRESHOLD = -3.0; // -3% relative return
    private const UNDERPERFORMANCE_PERIODS = 3; // 3 consecutive periods
    
    /**
     * Constructor
     * 
     * @param SectorAnalysisDAO $dao Data access object
     * @param array $config Optional configuration overrides
     */
    public function __construct(SectorAnalysisDAO $dao, array $config = [])
    {
        $this->dao = $dao;
        $this->config = array_merge([
            'hhi_moderate' => self::HHI_MODERATE_THRESHOLD,
            'hhi_high' => self::HHI_HIGH_THRESHOLD,
            'rebalancing_threshold' => self::REBALANCING_THRESHOLD,
            'underperformance_threshold' => self::UNDERPERFORMANCE_THRESHOLD,
            'underperformance_periods' => self::UNDERPERFORMANCE_PERIODS,
        ], $config);
    }
    
    /**
     * Generate all alerts for a user
     * 
     * @param int $userId User ID
     * @return array Array of alerts
     */
    public function generateAlerts(int $userId): array
    {
        $alerts = [];
        
        // Check concentration risk
        $concentrationAlerts = $this->checkConcentrationRisk($userId);
        $alerts = array_merge($alerts, $concentrationAlerts);
        
        // Check rebalancing needs
        $rebalancingAlerts = $this->checkRebalancingNeeds($userId);
        $alerts = array_merge($alerts, $rebalancingAlerts);
        
        // Check performance warnings
        $performanceAlerts = $this->checkPerformanceWarnings($userId);
        $alerts = array_merge($alerts, $performanceAlerts);
        
        // Sort by severity
        usort($alerts, function($a, $b) {
            $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
            return $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']];
        });
        
        return $alerts;
    }
    
    /**
     * Check concentration risk based on HHI
     * 
     * @param int $userId User ID
     * @return array Concentration risk alerts
     */
    public function checkConcentrationRisk(int $userId): array
    {
        $alerts = [];
        
        // Get current sector weights
        $sectorWeights = $this->getCurrentSectorWeights($userId);
        
        // Calculate HHI
        $hhi = $this->calculateHHI($sectorWeights);
        
        if ($hhi >= $this->config['hhi_high']) {
            $alerts[] = [
                'type' => 'concentration_risk',
                'severity' => 'critical',
                'title' => 'High Portfolio Concentration Risk',
                'message' => sprintf(
                    'Your portfolio HHI is %.0f, indicating high concentration risk. Consider diversifying across more sectors.',
                    $hhi
                ),
                'metric' => $hhi,
                'threshold' => $this->config['hhi_high'],
                'timestamp' => date('Y-m-d H:i:s'),
                'action_required' => true,
                'recommendation' => 'Review sector allocation and consider rebalancing to reduce concentration.',
            ];
        } elseif ($hhi >= $this->config['hhi_moderate']) {
            $alerts[] = [
                'type' => 'concentration_risk',
                'severity' => 'warning',
                'title' => 'Moderate Portfolio Concentration',
                'message' => sprintf(
                    'Your portfolio HHI is %.0f, indicating moderate concentration. Monitor closely.',
                    $hhi
                ),
                'metric' => $hhi,
                'threshold' => $this->config['hhi_moderate'],
                'timestamp' => date('Y-m-d H:i:s'),
                'action_required' => false,
                'recommendation' => 'Continue monitoring concentration levels.',
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Check if rebalancing is needed
     * 
     * @param int $userId User ID
     * @return array Rebalancing alerts
     */
    public function checkRebalancingNeeds(int $userId): array
    {
        $alerts = [];
        
        // Get current and target allocations
        $currentAllocation = $this->getCurrentSectorWeights($userId);
        $targetAllocation = $this->getTargetSectorAllocation($userId);
        
        $deviations = [];
        
        // Calculate deviations
        foreach ($targetAllocation as $sector => $targetWeight) {
            $currentWeight = $currentAllocation[$sector] ?? 0;
            $deviation = abs($currentWeight - $targetWeight);
            
            if ($deviation >= $this->config['rebalancing_threshold']) {
                $deviations[] = [
                    'sector' => $sector,
                    'current' => $currentWeight,
                    'target' => $targetWeight,
                    'deviation' => $deviation,
                ];
            }
        }
        
        if (!empty($deviations)) {
            $severity = count($deviations) > 3 ? 'critical' : 'warning';
            
            $sectorsText = implode(', ', array_column($deviations, 'sector'));
            
            $alerts[] = [
                'type' => 'rebalancing_needed',
                'severity' => $severity,
                'title' => 'Portfolio Rebalancing Recommended',
                'message' => sprintf(
                    '%d sector(s) have deviated more than %.1f%% from target allocation: %s',
                    count($deviations),
                    $this->config['rebalancing_threshold'],
                    $sectorsText
                ),
                'deviations' => $deviations,
                'threshold' => $this->config['rebalancing_threshold'],
                'timestamp' => date('Y-m-d H:i:s'),
                'action_required' => true,
                'recommendation' => 'Review rebalancing suggestions and execute trades to realign with target allocation.',
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Check for performance warnings
     * 
     * @param int $userId User ID
     * @return array Performance warning alerts
     */
    public function checkPerformanceWarnings(int $userId): array
    {
        $alerts = [];
        
        // Get recent performance data (last 6 months)
        $endDate = new DateTime();
        $startDate = (clone $endDate)->modify('-6 months');
        
        $performanceData = $this->getPerformanceHistory(
            $userId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
        
        // Check for consecutive underperformance
        $underperformingPeriods = 0;
        foreach ($performanceData as $period) {
            if ($period['relative_return'] < $this->config['underperformance_threshold']) {
                $underperformingPeriods++;
            } else {
                $underperformingPeriods = 0; // Reset counter
            }
            
            if ($underperformingPeriods >= $this->config['underperformance_periods']) {
                $alerts[] = [
                    'type' => 'underperformance_warning',
                    'severity' => 'warning',
                    'title' => 'Sustained Underperformance Detected',
                    'message' => sprintf(
                        'Your portfolio has underperformed the benchmark for %d consecutive periods.',
                        $underperformingPeriods
                    ),
                    'periods' => $underperformingPeriods,
                    'threshold' => $this->config['underperformance_threshold'],
                    'timestamp' => date('Y-m-d H:i:s'),
                    'action_required' => false,
                    'recommendation' => 'Review portfolio composition and consider strategy adjustments.',
                ];
                break;
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get current sector weights for user
     * 
     * @param int $userId User ID
     * @return array Sector weights
     */
    private function getCurrentSectorWeights(int $userId): array
    {
        // TODO: Implement actual data retrieval
        // This is a placeholder implementation
        return [
            'Technology' => 55.0,
            'Healthcare' => 20.0,
            'Finance' => 15.0,
            'Consumer' => 10.0,
        ];
    }
    
    /**
     * Get target sector allocation for user
     * 
     * @param int $userId User ID
     * @return array Target allocation
     */
    private function getTargetSectorAllocation(int $userId): array
    {
        // TODO: Implement actual target retrieval from user preferences
        // This is a placeholder implementation
        return [
            'Technology' => 40.0,
            'Healthcare' => 25.0,
            'Finance' => 20.0,
            'Consumer' => 15.0,
        ];
    }
    
    /**
     * Get performance history for user
     * 
     * @param int $userId User ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Performance data
     */
    private function getPerformanceHistory(int $userId, string $startDate, string $endDate): array
    {
        // TODO: Implement actual data retrieval
        // This is a placeholder implementation
        return [
            ['date' => '2024-11-01', 'portfolio_return' => -2.5, 'benchmark_return' => 1.2, 'relative_return' => -3.7],
            ['date' => '2024-10-01', 'portfolio_return' => -1.8, 'benchmark_return' => 2.1, 'relative_return' => -3.9],
            ['date' => '2024-09-01', 'portfolio_return' => -0.5, 'benchmark_return' => 2.5, 'relative_return' => -3.0],
            ['date' => '2024-08-01', 'portfolio_return' => 3.2, 'benchmark_return' => 2.8, 'relative_return' => 0.4],
            ['date' => '2024-07-01', 'portfolio_return' => 4.1, 'benchmark_return' => 3.5, 'relative_return' => 0.6],
        ];
    }
    
    /**
     * Calculate Herfindahl-Hirschman Index (HHI)
     * 
     * @param array $weights Sector weights
     * @return float HHI value
     */
    private function calculateHHI(array $weights): float
    {
        $hhi = 0.0;
        foreach ($weights as $weight) {
            $hhi += $weight * $weight;
        }
        return $hhi * 100; // Scale to 0-10,000 range
    }
    
    /**
     * Save alert to database
     * 
     * @param int $userId User ID
     * @param array $alert Alert data
     * @return bool Success status
     */
    public function saveAlert(int $userId, array $alert): bool
    {
        // TODO: Implement database storage
        // This would insert into an alerts table
        return true;
    }
    
    /**
     * Get active alerts for user
     * 
     * @param int $userId User ID
     * @param bool $unreadOnly Only return unread alerts
     * @return array Alerts
     */
    public function getActiveAlerts(int $userId, bool $unreadOnly = false): array
    {
        // TODO: Implement database retrieval
        // This would query the alerts table
        return [];
    }
    
    /**
     * Mark alert as read
     * 
     * @param int $alertId Alert ID
     * @return bool Success status
     */
    public function markAlertAsRead(int $alertId): bool
    {
        // TODO: Implement database update
        return true;
    }
    
    /**
     * Dismiss alert
     * 
     * @param int $alertId Alert ID
     * @return bool Success status
     */
    public function dismissAlert(int $alertId): bool
    {
        // TODO: Implement database update
        return true;
    }
}
