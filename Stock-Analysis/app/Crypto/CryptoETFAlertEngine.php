<?php

declare(strict_types=1);

namespace App\Crypto;

use DateTime;
use DateTimeZone;

/**
 * Crypto ETF Alert Engine
 * 
 * Monitors cryptocurrency price movements during extended hours
 * when ETF markets are closed (9:30 AM - 4:00 PM ET Mon-Fri).
 * 
 * Key features:
 * - Overnight move detection (4PM-9:30AM)
 * - Weekend monitoring (Fri 4PM - Mon 9:30AM)
 * - Pre-market gap predictions
 * - Risk level assessment
 * - Multi-ETF monitoring
 * 
 * @package App\Crypto
 */
class CryptoETFAlertEngine
{
    private CryptoDataService $cryptoService;
    private array $alertHistory = [];
    
    public function __construct(CryptoDataService $cryptoService)
    {
        $this->cryptoService = $cryptoService;
    }
    
    /**
     * Check for significant overnight crypto price movement
     * 
     * @param string $cryptoSymbol Crypto symbol
     * @param float $lastClosePrice Last ETF market close price
     * @param float $currentPrice Current crypto price
     * @param float $threshold Alert threshold percentage
     * @return array|null Alert data or null
     */
    public function checkOvernightMove(
        string $cryptoSymbol,
        float $lastClosePrice,
        float $currentPrice,
        float $threshold = 2.0
    ): ?array {
        $changePercent = (($currentPrice - $lastClosePrice) / $lastClosePrice) * 100;
        
        if (abs($changePercent) < $threshold) {
            return null;
        }
        
        $severity = $this->calculateSeverity(abs($changePercent));
        
        $alert = [
            'alert_type' => 'OVERNIGHT_MOVE',
            'crypto_symbol' => $cryptoSymbol,
            'last_close_price' => $lastClosePrice,
            'current_price' => $currentPrice,
            'change_amount' => $currentPrice - $lastClosePrice,
            'change_percent' => round($changePercent, 2),
            'severity' => $severity,
            'message' => sprintf(
                '%s moved %.2f%% overnight (%.2f -> %.2f)',
                $cryptoSymbol,
                $changePercent,
                $lastClosePrice,
                $currentPrice
            ),
            'timestamp' => time()
        ];
        
        $this->recordAlert($cryptoSymbol, $alert);
        
        return $alert;
    }
    
    /**
     * Calculate expected ETF opening price based on crypto movement
     * 
     * @param string $etfSymbol ETF ticker
     * @param float $lastETFClose Last ETF closing price
     * @param float $cryptoLastClose Crypto price at ETF close
     * @param float $cryptoCurrent Current crypto price
     * @return array Expected opening data
     */
    public function calculateExpectedETFOpen(
        string $etfSymbol,
        float $lastETFClose,
        float $cryptoLastClose,
        float $cryptoCurrent
    ): array {
        $cryptoChangePercent = (($cryptoCurrent - $cryptoLastClose) / $cryptoLastClose) * 100;
        
        // ETF should move proportionally to underlying crypto
        $expectedETFPrice = $lastETFClose * (1 + $cryptoChangePercent / 100);
        $expectedGapPercent = (($expectedETFPrice - $lastETFClose) / $lastETFClose) * 100;
        
        return [
            'etf_symbol' => $etfSymbol,
            'last_close' => $lastETFClose,
            'expected_open_price' => round($expectedETFPrice, 4),
            'expected_gap_percent' => round($expectedGapPercent, 2),
            'expected_gap_amount' => round($expectedETFPrice - $lastETFClose, 4),
            'crypto_change_percent' => round($cryptoChangePercent, 2),
            'calculation_time' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate pre-market alert for significant expected gap
     * 
     * @param string $etfSymbol ETF ticker
     * @param string $cryptoSymbol Underlying crypto
     * @param float $threshold Alert threshold
     * @return array|null Alert or null
     */
    public function generatePreMarketAlert(
        string $etfSymbol,
        string $cryptoSymbol,
        float $threshold = 3.0
    ): ?array {
        // Get last ETF close and crypto prices
        $cryptoPrice = $this->cryptoService->getCryptoPrice($cryptoSymbol);
        $overnightMove = $this->cryptoService->getOvernightMove($cryptoSymbol);
        
        if (abs($overnightMove['change_since_close']) < $threshold) {
            return null;
        }
        
        return [
            'alert_type' => 'PRE_MARKET_GAP',
            'etf_symbol' => $etfSymbol,
            'crypto_symbol' => $cryptoSymbol,
            'expected_gap' => round($overnightMove['change_since_close'], 2),
            'severity' => $this->calculateSeverity(abs($overnightMove['change_since_close'])),
            'message' => sprintf(
                '%s expected to gap %.2f%% at open due to %s overnight move',
                $etfSymbol,
                $overnightMove['change_since_close'],
                $cryptoSymbol
            ),
            'recommendation' => $this->generateRecommendation($overnightMove['change_since_close']),
            'timestamp' => time()
        ];
    }
    
    /**
     * Check weekend cryptocurrency movement
     * 
     * @param string $cryptoSymbol Crypto symbol
     * @param float $fridayClose Friday 4PM close price
     * @param float $mondayPremarket Monday pre-market price
     * @param float $threshold Alert threshold
     * @return array|null Alert or null
     */
    public function checkWeekendMove(
        string $cryptoSymbol,
        float $fridayClose,
        float $mondayPremarket,
        float $threshold = 5.0
    ): ?array {
        $changePercent = (($mondayPremarket - $fridayClose) / $fridayClose) * 100;
        
        if (abs($changePercent) < $threshold) {
            return null;
        }
        
        $alert = [
            'alert_type' => 'WEEKEND_MOVE',
            'crypto_symbol' => $cryptoSymbol,
            'friday_close' => $fridayClose,
            'monday_premarket' => $mondayPremarket,
            'change_percent' => round($changePercent, 2),
            'severity' => $this->calculateSeverity(abs($changePercent)),
            'message' => sprintf(
                '%s moved %.2f%% over the weekend',
                $cryptoSymbol,
                $changePercent
            ),
            'timestamp' => time()
        ];
        
        $this->recordAlert($cryptoSymbol, $alert);
        
        return $alert;
    }
    
    /**
     * Get current market status
     * 
     * @return array Market status information
     */
    public function getMarketStatus(): array
    {
        $now = new DateTime('now', new DateTimeZone('America/New_York'));
        $hour = (int) $now->format('G');
        $minute = (int) $now->format('i');
        $dayOfWeek = (int) $now->format('N'); // 1=Mon, 5=Fri, 6=Sat, 7=Sun
        
        $isMarketOpen = ($dayOfWeek <= 5) && 
                        (($hour > 9) || ($hour == 9 && $minute >= 30)) && 
                        ($hour < 16);
        
        $hoursUntilOpen = $isMarketOpen ? 0 : $this->calculateHoursUntilOpen($now);
        $hoursUntilClose = $isMarketOpen ? $this->calculateHoursUntilClose($now) : 0;
        
        return [
            'is_market_open' => $isMarketOpen,
            'current_time' => $now->format('Y-m-d H:i:s T'),
            'hours_until_open' => $hoursUntilOpen,
            'hours_until_close' => $hoursUntilClose,
            'extended_hours_active' => !$isMarketOpen
        ];
    }
    
    /**
     * Calculate gap risk for ETF at market open
     * 
     * @param string $cryptoSymbol Crypto symbol
     * @param string $etfSymbol ETF ticker
     * @param float $currentCryptoPrice Current crypto price
     * @param float $lastETFClose Last ETF close
     * @return array Risk assessment
     */
    public function calculateGapRisk(
        string $cryptoSymbol,
        string $etfSymbol,
        float $currentCryptoPrice,
        float $lastETFClose
    ): array {
        $overnightMove = $this->cryptoService->getOvernightMove($cryptoSymbol);
        $changePercent = abs($overnightMove['change_since_close']);
        
        $riskLevel = 'low';
        if ($changePercent > 10) {
            $riskLevel = 'high';
        } elseif ($changePercent > 5) {
            $riskLevel = 'medium';
        }
        
        return [
            'etf_symbol' => $etfSymbol,
            'crypto_symbol' => $cryptoSymbol,
            'risk_level' => $riskLevel,
            'expected_gap_percent' => round($overnightMove['change_since_close'], 2),
            'recommendation' => $this->generateRecommendation($overnightMove['change_since_close']),
            'calculated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Monitor multiple crypto ETFs simultaneously
     * 
     * @param array $etfs Array of ETF data
     * @param float $threshold Alert threshold
     * @return array Array of alerts
     */
    public function monitorMultipleETFs(array $etfs, float $threshold = 3.0): array
    {
        $alerts = [];
        
        foreach ($etfs as $etfSymbol => $data) {
            $cryptoSymbol = $data['crypto'];
            $lastClose = $data['last_close'];
            
            $alert = $this->generatePreMarketAlert($etfSymbol, $cryptoSymbol, $threshold);
            
            if ($alert !== null) {
                $alerts[] = $alert;
            }
        }
        
        return $alerts;
    }
    
    /**
     * Get time until market opens
     * 
     * @return array Time breakdown
     */
    public function getTimeUntilMarketOpen(): array
    {
        $now = new DateTime('now', new DateTimeZone('America/New_York'));
        $hoursUntilOpen = $this->calculateHoursUntilOpen($now);
        $minutes = ($hoursUntilOpen - floor($hoursUntilOpen)) * 60;
        
        $nextOpen = clone $now;
        $nextOpen->modify('+' . ceil($hoursUntilOpen) . ' hours');
        
        return [
            'hours' => floor($hoursUntilOpen),
            'minutes' => round($minutes),
            'total_hours' => round($hoursUntilOpen, 2),
            'next_open_time' => $nextOpen->format('Y-m-d H:i:s T')
        ];
    }
    
    /**
     * Get current extended hours period
     * 
     * @return string Period identifier
     */
    public function getExtendedHoursPeriod(): string
    {
        $status = $this->getMarketStatus();
        
        if ($status['is_market_open']) {
            return 'market_hours';
        }
        
        $now = new DateTime('now', new DateTimeZone('America/New_York'));
        $hour = (int) $now->format('G');
        $dayOfWeek = (int) $now->format('N');
        
        if ($dayOfWeek > 5) {
            return 'weekend';
        }
        
        if ($hour < 9 || ($hour == 9 && (int) $now->format('i') < 30)) {
            return 'pre_market';
        }
        
        return 'after_hours';
    }
    
    /**
     * Get alert history for a crypto
     * 
     * @param string $cryptoSymbol Crypto symbol
     * @param int $hours Number of hours of history
     * @return array Historical alerts
     */
    public function getAlertHistory(string $cryptoSymbol, int $hours = 24): array
    {
        if (!isset($this->alertHistory[$cryptoSymbol])) {
            return [];
        }
        
        $cutoff = time() - ($hours * 3600);
        
        return array_filter(
            $this->alertHistory[$cryptoSymbol],
            fn($alert) => $alert['timestamp'] >= $cutoff
        );
    }
    
    /**
     * Calculate average overnight volatility
     * 
     * @param string $cryptoSymbol Crypto symbol
     * @param int $days Number of days
     * @return float Average volatility
     */
    public function calculateOvernightVolatility(string $cryptoSymbol, int $days = 30): float
    {
        $history = $this->getAlertHistory($cryptoSymbol, $days * 24);
        
        if (empty($history)) {
            return 0.0;
        }
        
        $changes = array_column($history, 'change_percent');
        $absChanges = array_map('abs', $changes);
        
        return round(array_sum($absChanges) / count($absChanges), 2);
    }
    
    /**
     * Calculate alert severity based on percentage change
     * 
     * @param float $changePercent Absolute change percentage
     * @return string Severity level
     */
    private function calculateSeverity(float $changePercent): string
    {
        if ($changePercent >= 10) {
            return 'high';
        } elseif ($changePercent >= 3) {
            return 'medium';
        }
        return 'low';
    }
    
    /**
     * Generate trading recommendation based on movement
     * 
     * @param float $changePercent Change percentage
     * @return string Recommendation
     */
    private function generateRecommendation(float $changePercent): string
    {
        if ($changePercent > 5) {
            return 'WAIT for gap to fill - likely overextended';
        } elseif ($changePercent < -5) {
            return 'WATCH for buying opportunity at open';
        }
        return 'MONITOR - moderate movement';
    }
    
    /**
     * Record alert in history
     * 
     * @param string $cryptoSymbol Crypto symbol
     * @param array $alert Alert data
     */
    private function recordAlert(string $cryptoSymbol, array $alert): void
    {
        if (!isset($this->alertHistory[$cryptoSymbol])) {
            $this->alertHistory[$cryptoSymbol] = [];
        }
        
        $this->alertHistory[$cryptoSymbol][] = $alert;
    }
    
    /**
     * Calculate hours until market opens
     * 
     * @param DateTime $now Current time
     * @return float Hours until open
     */
    private function calculateHoursUntilOpen(DateTime $now): float
    {
        $dayOfWeek = (int) $now->format('N');
        $hour = (int) $now->format('G');
        $minute = (int) $now->format('i');
        
        // If weekend, calculate to Monday 9:30 AM
        if ($dayOfWeek > 5) {
            $daysUntilMonday = 8 - $dayOfWeek;
            $hoursFromNow = ($daysUntilMonday * 24) + (9 - $hour) + (30 - $minute) / 60;
            return $hoursFromNow;
        }
        
        // If before 9:30 AM on weekday
        if ($hour < 9 || ($hour == 9 && $minute < 30)) {
            return (9 - $hour) + (30 - $minute) / 60;
        }
        
        // After market close, next day
        return (24 - $hour + 9) + (30 - $minute) / 60;
    }
    
    /**
     * Calculate hours until market closes
     * 
     * @param DateTime $now Current time
     * @return float Hours until close
     */
    private function calculateHoursUntilClose(DateTime $now): float
    {
        $hour = (int) $now->format('G');
        $minute = (int) $now->format('i');
        
        return (16 - $hour) - ($minute / 60);
    }
}
