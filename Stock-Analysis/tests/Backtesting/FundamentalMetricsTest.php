<?php

declare(strict_types=1);

namespace Tests\Backtesting;

use PHPUnit\Framework\TestCase;
use App\Backtesting\FundamentalMetrics;

/**
 * Fundamental Metrics Tests
 *
 * Tests fundamental analysis calculations including dividend coverage,
 * earnings quality, and free cash flow analysis
 *
 * @package Tests\Backtesting
 */
class FundamentalMetricsTest extends TestCase
{
    private FundamentalMetrics $metrics;
    
    protected function setUp(): void
    {
        $this->metrics = new FundamentalMetrics();
    }
    
    public function testItCalculatesDividendCoverageRatio(): void
    {
        $earnings = 100000000;  // $100M
        $dividendPaid = 40000000;  // $40M
        
        $coverage = $this->metrics->calculateDividendCoverageRatio($earnings, $dividendPaid);
        
        $this->assertEquals(2.5, $coverage);  // 100M / 40M = 2.5x
    }
    
    public function testItCalculatesDividendCoverageByFreeCashFlow(): void
    {
        $freeCashFlow = 80000000;  // $80M
        $dividendPaid = 40000000;  // $40M
        
        $coverage = $this->metrics->calculateDividendCoverageByFCF($freeCashFlow, $dividendPaid);
        
        $this->assertEquals(2.0, $coverage);  // 80M / 40M = 2.0x
    }
    
    public function testItHandlesZeroDividend(): void
    {
        $earnings = 100000000;
        $dividendPaid = 0;
        
        $coverage = $this->metrics->calculateDividendCoverageRatio($earnings, $dividendPaid);
        
        $this->assertEquals(0.0, $coverage);
    }
    
    public function testItCalculatesEarningsQuality(): void
    {
        $netIncome = 100000000;
        $operatingCashFlow = 120000000;
        
        $quality = $this->metrics->calculateEarningsQuality($netIncome, $operatingCashFlow);
        
        $this->assertEquals(1.2, $quality);  // 120M / 100M = 1.2
    }
    
    public function testItIdentifiesHighQualityEarnings(): void
    {
        $netIncome = 100000000;
        $operatingCashFlow = 130000000;  // OCF > Net Income = good quality
        
        $quality = $this->metrics->calculateEarningsQuality($netIncome, $operatingCashFlow);
        
        $this->assertGreaterThan(1.0, $quality);
    }
    
    public function testItCalculatesFreeCashFlowYield(): void
    {
        $freeCashFlow = 500000000;  // $500M
        $marketCap = 10000000000;   // $10B
        
        $yield = $this->metrics->calculateFreeCashFlowYield($freeCashFlow, $marketCap);
        
        $this->assertEquals(5.0, $yield);  // (500M / 10B) * 100 = 5%
    }
    
    public function testItCalculatesFreeCashFlowMargin(): void
    {
        $freeCashFlow = 200000000;  // $200M
        $revenue = 1000000000;       // $1B
        
        $margin = $this->metrics->calculateFreeCashFlowMargin($freeCashFlow, $revenue);
        
        $this->assertEquals(20.0, $margin);  // (200M / 1B) * 100 = 20%
    }
    
    public function testItCalculatesPayoutRatio(): void
    {
        $dividendPaid = 40000000;   // $40M
        $netIncome = 100000000;      // $100M
        
        $payout = $this->metrics->calculatePayoutRatio($dividendPaid, $netIncome);
        
        $this->assertEquals(40.0, $payout);  // (40M / 100M) * 100 = 40%
    }
    
    public function testItIdentifiesSustainableDividend(): void
    {
        $dividendPaid = 40000000;
        $netIncome = 100000000;
        $freeCashFlow = 80000000;
        
        $isSustainable = $this->metrics->isDividendSustainable($dividendPaid, $netIncome, $freeCashFlow);
        
        $this->assertTrue($isSustainable);  // Payout < 60%, FCF covers dividend
    }
    
    public function testItIdentifiesUnsustainableDividend(): void
    {
        $dividendPaid = 80000000;   // 80% payout
        $netIncome = 100000000;
        $freeCashFlow = 50000000;   // Doesn't cover dividend
        
        $isSustainable = $this->metrics->isDividendSustainable($dividendPaid, $netIncome, $freeCashFlow);
        
        $this->assertFalse($isSustainable);
    }
    
    public function testItCalculatesReturnOnEquity(): void
    {
        $netIncome = 100000000;      // $100M
        $shareholderEquity = 500000000;  // $500M
        
        $roe = $this->metrics->calculateReturnOnEquity($netIncome, $shareholderEquity);
        
        $this->assertEquals(20.0, $roe);  // (100M / 500M) * 100 = 20%
    }
    
    public function testItCalculatesReturnOnAssets(): void
    {
        $netIncome = 100000000;   // $100M
        $totalAssets = 1000000000;  // $1B
        
        $roa = $this->metrics->calculateReturnOnAssets($netIncome, $totalAssets);
        
        $this->assertEquals(10.0, $roa);  // (100M / 1B) * 100 = 10%
    }
    
    public function testItCalculatesDebtToEquity(): void
    {
        $totalDebt = 300000000;       // $300M
        $shareholderEquity = 500000000;  // $500M
        
        $ratio = $this->metrics->calculateDebtToEquity($totalDebt, $shareholderEquity);
        
        $this->assertEquals(0.6, $ratio);  // 300M / 500M = 0.6
    }
    
    public function testItCalculatesInterestCoverageRatio(): void
    {
        $ebit = 200000000;          // $200M
        $interestExpense = 20000000;  // $20M
        
        $coverage = $this->metrics->calculateInterestCoverageRatio($ebit, $interestExpense);
        
        $this->assertEquals(10.0, $coverage);  // 200M / 20M = 10x
    }
    
    public function testItGeneratesFundamentalScore(): void
    {
        $fundamentals = [
            'net_income' => 100000000,
            'operating_cash_flow' => 120000000,
            'free_cash_flow' => 80000000,
            'revenue' => 1000000000,
            'market_cap' => 10000000000,
            'shareholder_equity' => 500000000,
            'total_assets' => 1000000000,
            'total_debt' => 300000000,
            'dividend_paid' => 40000000,
            'ebit' => 150000000,
            'interest_expense' => 15000000
        ];
        
        $score = $this->metrics->generateFundamentalScore($fundamentals);
        
        $this->assertIsArray($score);
        $this->assertArrayHasKey('total_score', $score);
        $this->assertArrayHasKey('earnings_quality_score', $score);
        $this->assertArrayHasKey('cash_flow_score', $score);
        $this->assertArrayHasKey('dividend_score', $score);
        $this->assertArrayHasKey('profitability_score', $score);
        $this->assertArrayHasKey('leverage_score', $score);
    }
    
    public function testItGeneratesBuffettStyleReport(): void
    {
        $fundamentals = [
            'net_income' => 100000000,
            'operating_cash_flow' => 120000000,
            'free_cash_flow' => 80000000,
            'revenue' => 1000000000,
            'market_cap' => 10000000000,
            'shareholder_equity' => 500000000,
            'total_assets' => 1000000000,
            'total_debt' => 300000000,
            'dividend_paid' => 40000000,
            'ebit' => 150000000,
            'interest_expense' => 15000000
        ];
        
        $report = $this->metrics->generateBuffettStyleReport('AAPL', $fundamentals);
        
        $this->assertIsString($report);
        $this->assertStringContainsString('BUFFETT-STYLE FUNDAMENTAL ANALYSIS', $report);
        $this->assertStringContainsString('Earnings Quality', $report);
        $this->assertStringContainsString('Dividend Sustainability', $report);
        $this->assertStringContainsString('Return on Equity', $report);
    }
    
    public function testItHandlesZeroValues(): void
    {
        $coverage = $this->metrics->calculateDividendCoverageRatio(0, 10000);
        $this->assertEquals(0.0, $coverage);
        
        $quality = $this->metrics->calculateEarningsQuality(0, 10000);
        $this->assertEquals(0.0, $quality);
    }
}
