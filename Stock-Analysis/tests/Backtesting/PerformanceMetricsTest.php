<?php

declare(strict_types=1);

namespace Tests\Backtesting;

use App\Backtesting\PerformanceMetrics;
use PHPUnit\Framework\TestCase;

/**
 * PerformanceMetrics Test Suite
 * 
 * Tests performance metrics calculation including:
 * - Returns (total, annualized)
 * - Sharpe ratio
 * - Sortino ratio
 * - Maximum drawdown
 * - Win rate
 * - Profit factor
 * - Average win/loss
 * 
 * @package Tests\Backtesting
 */
class PerformanceMetricsTest extends TestCase
{
    private PerformanceMetrics $metrics;
    
    protected function setUp(): void
    {
        $this->metrics = new PerformanceMetrics();
    }
    
    /**
     * @test
     */
    public function itCalculatesTotalReturn(): void
    {
        $initialValue = 10000.0;
        $finalValue = 12000.0;
        
        $return = $this->metrics->calculateTotalReturn($initialValue, $finalValue);
        
        $this->assertEquals(20.0, $return);
    }
    
    /**
     * @test
     */
    public function itCalculatesNegativeReturn(): void
    {
        $initialValue = 10000.0;
        $finalValue = 8000.0;
        
        $return = $this->metrics->calculateTotalReturn($initialValue, $finalValue);
        
        $this->assertEquals(-20.0, $return);
    }
    
    /**
     * @test
     */
    public function itCalculatesAnnualizedReturn(): void
    {
        $totalReturn = 50.0; // 50% total return
        $days = 365;
        
        $annualized = $this->metrics->calculateAnnualizedReturn($totalReturn, $days);
        
        $this->assertEquals(50.0, $annualized, '', 0.1);
    }
    
    /**
     * @test
     */
    public function itCalculatesSharpeRatio(): void
    {
        $returns = [2.0, -1.0, 3.0, 1.0, -0.5];
        $riskFreeRate = 0.02; // 2% annual risk-free rate
        
        $sharpe = $this->metrics->calculateSharpeRatio($returns, $riskFreeRate);
        
        $this->assertIsFloat($sharpe);
        $this->assertGreaterThan(0, $sharpe);
    }
    
    /**
     * @test
     */
    public function itCalculatesSortinoRatio(): void
    {
        $returns = [2.0, -1.0, 3.0, 1.0, -0.5];
        $riskFreeRate = 0.02;
        
        $sortino = $this->metrics->calculateSortinoRatio($returns, $riskFreeRate);
        
        $this->assertIsFloat($sortino);
        $this->assertGreaterThan(0, $sortino);
    }
    
    /**
     * @test
     */
    public function itCalculatesMaxDrawdown(): void
    {
        $equityCurve = [10000, 11000, 10500, 9000, 9500, 12000];
        
        $maxDrawdown = $this->metrics->calculateMaxDrawdown($equityCurve);
        
        // Max drawdown: (11000 - 9000) / 11000 = 18.18%
        $this->assertEqualsWithDelta(-18.18, $maxDrawdown, 0.01);
    }
    
    /**
     * @test
     */
    public function itReturnsZeroDrawdownForIncreasingEquity(): void
    {
        $equityCurve = [10000, 11000, 12000, 13000];
        
        $maxDrawdown = $this->metrics->calculateMaxDrawdown($equityCurve);
        
        $this->assertEquals(0.0, $maxDrawdown);
    }
    
    /**
     * @test
     */
    public function itCalculatesWinRate(): void
    {
        $trades = [
            ['profit' => 100],
            ['profit' => -50],
            ['profit' => 200],
            ['profit' => 150],
            ['profit' => -30]
        ];
        
        $winRate = $this->metrics->calculateWinRate($trades);
        
        $this->assertEquals(60.0, $winRate); // 3 wins out of 5 trades
    }
    
    /**
     * @test
     */
    public function itReturnsZeroWinRateForNoTrades(): void
    {
        $trades = [];
        
        $winRate = $this->metrics->calculateWinRate($trades);
        
        $this->assertEquals(0.0, $winRate);
    }
    
    /**
     * @test
     */
    public function itCalculatesProfitFactor(): void
    {
        $trades = [
            ['profit' => 100],
            ['profit' => -50],
            ['profit' => 200],
            ['profit' => -30]
        ];
        
        $profitFactor = $this->metrics->calculateProfitFactor($trades);
        
        // Total profit: 300, Total loss: 80
        $this->assertEquals(3.75, $profitFactor);
    }
    
    /**
     * @test
     */
    public function itReturnsZeroProfitFactorForNoLosses(): void
    {
        $trades = [
            ['profit' => 100],
            ['profit' => 200]
        ];
        
        $profitFactor = $this->metrics->calculateProfitFactor($trades);
        
        $this->assertEquals(0.0, $profitFactor);
    }
    
    /**
     * @test
     */
    public function itCalculatesAverageWin(): void
    {
        $trades = [
            ['profit' => 100],
            ['profit' => -50],
            ['profit' => 200],
            ['profit' => -30]
        ];
        
        $avgWin = $this->metrics->calculateAverageWin($trades);
        
        $this->assertEquals(150.0, $avgWin); // (100 + 200) / 2
    }
    
    /**
     * @test
     */
    public function itCalculatesAverageLoss(): void
    {
        $trades = [
            ['profit' => 100],
            ['profit' => -50],
            ['profit' => 200],
            ['profit' => -30]
        ];
        
        $avgLoss = $this->metrics->calculateAverageLoss($trades);
        
        $this->assertEquals(-40.0, $avgLoss); // (-50 + -30) / 2
    }
    
    /**
     * @test
     */
    public function itCalculatesRewardRiskRatio(): void
    {
        $trades = [
            ['profit' => 100],
            ['profit' => -50],
            ['profit' => 200],
            ['profit' => -30]
        ];
        
        $ratio = $this->metrics->calculateRewardRiskRatio($trades);
        
        $this->assertEquals(3.75, $ratio); // 150 / 40
    }
    
    /**
     * @test
     */
    public function itCalculatesVolatility(): void
    {
        $returns = [2.0, -1.0, 3.0, 1.0, -0.5];
        
        $volatility = $this->metrics->calculateVolatility($returns);
        
        $this->assertIsFloat($volatility);
        $this->assertGreaterThan(0, $volatility);
    }
    
    /**
     * @test
     */
    public function itGeneratesPerformanceSummary(): void
    {
        $backtestResult = [
            'initial_capital' => 10000.0,
            'final_value' => 12000.0,
            'trades' => [
                ['profit' => 100, 'return' => 1.0],
                ['profit' => -50, 'return' => -0.5],
                ['profit' => 200, 'return' => 2.0]
            ],
            'equity_curve' => [10000, 10100, 10050, 10250],
            'days' => 90
        ];
        
        $summary = $this->metrics->generateSummary($backtestResult);
        
        $this->assertArrayHasKey('total_return', $summary);
        $this->assertArrayHasKey('annualized_return', $summary);
        $this->assertArrayHasKey('sharpe_ratio', $summary);
        $this->assertArrayHasKey('sortino_ratio', $summary);
        $this->assertArrayHasKey('max_drawdown', $summary);
        $this->assertArrayHasKey('win_rate', $summary);
        $this->assertArrayHasKey('profit_factor', $summary);
        $this->assertArrayHasKey('total_trades', $summary);
    }
    
    /**
     * @test
     */
    public function itCalculatesExpectancy(): void
    {
        $trades = [
            ['profit' => 100],
            ['profit' => -50],
            ['profit' => 200],
            ['profit' => 150],
            ['profit' => -30]
        ];
        
        $expectancy = $this->metrics->calculateExpectancy($trades);
        
        // (Win rate * Avg win) - (Loss rate * Avg loss)
        // (0.6 * 150) - (0.4 * 40) = 90 - 16 = 74
        $this->assertEquals(74.0, $expectancy);
    }
    
    /**
     * @test
     */
    public function itCalculatesTotalTrades(): void
    {
        $trades = [
            ['profit' => 100],
            ['profit' => -50],
            ['profit' => 200]
        ];
        
        $total = $this->metrics->calculateTotalTrades($trades);
        
        $this->assertEquals(3, $total);
    }
    
    /**
     * @test
     */
    public function itCalculatesWinningTrades(): void
    {
        $trades = [
            ['profit' => 100],
            ['profit' => -50],
            ['profit' => 200],
            ['profit' => 0]
        ];
        
        $winning = $this->metrics->calculateWinningTrades($trades);
        
        $this->assertEquals(2, $winning);
    }
    
    /**
     * @test
     */
    public function itCalculatesLosingTrades(): void
    {
        $trades = [
            ['profit' => 100],
            ['profit' => -50],
            ['profit' => 200],
            ['profit' => -30],
            ['profit' => 0]
        ];
        
        $losing = $this->metrics->calculateLosingTrades($trades);
        
        $this->assertEquals(2, $losing);
    }
    
    /**
     * @test
     */
    public function itHandlesEmptyTradeList(): void
    {
        $trades = [];
        
        $winRate = $this->metrics->calculateWinRate($trades);
        $profitFactor = $this->metrics->calculateProfitFactor($trades);
        $expectancy = $this->metrics->calculateExpectancy($trades);
        
        $this->assertEquals(0.0, $winRate);
        $this->assertEquals(0.0, $profitFactor);
        $this->assertEquals(0.0, $expectancy);
    }
}
