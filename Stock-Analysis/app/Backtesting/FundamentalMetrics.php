<?php

declare(strict_types=1);

namespace App\Backtesting;

/**
 * Fundamental Metrics Calculator
 * 
 * Calculates Buffett-style fundamental analysis metrics including:
 * - Dividend coverage (earnings and free cash flow)
 * - Earnings quality
 * - Free cash flow analysis
 * - Profitability ratios (ROE, ROA)
 * - Leverage analysis
 * - Dividend sustainability
 * 
 * @package App\Backtesting
 */
class FundamentalMetrics
{
    /**
     * Calculate dividend coverage ratio (earnings / dividend)
     *
     * @param float $netIncome Net income
     * @param float $dividendPaid Total dividend paid
     * @return float Coverage ratio (times)
     */
    public function calculateDividendCoverageRatio(float $netIncome, float $dividendPaid): float
    {
        if ($dividendPaid == 0 || $netIncome <= 0) {
            return 0.0;
        }
        
        return $netIncome / $dividendPaid;
    }
    
    /**
     * Calculate dividend coverage by free cash flow
     *
     * @param float $freeCashFlow Free cash flow
     * @param float $dividendPaid Total dividend paid
     * @return float Coverage ratio (times)
     */
    public function calculateDividendCoverageByFCF(float $freeCashFlow, float $dividendPaid): float
    {
        if ($dividendPaid == 0 || $freeCashFlow <= 0) {
            return 0.0;
        }
        
        return $freeCashFlow / $dividendPaid;
    }
    
    /**
     * Calculate earnings quality (operating cash flow / net income)
     * 
     * Ratio > 1.0 indicates high-quality earnings backed by cash
     *
     * @param float $netIncome Net income
     * @param float $operatingCashFlow Operating cash flow
     * @return float Earnings quality ratio
     */
    public function calculateEarningsQuality(float $netIncome, float $operatingCashFlow): float
    {
        if ($netIncome == 0) {
            return 0.0;
        }
        
        return $operatingCashFlow / $netIncome;
    }
    
    /**
     * Calculate free cash flow yield
     *
     * @param float $freeCashFlow Free cash flow
     * @param float $marketCap Market capitalization
     * @return float FCF yield percentage
     */
    public function calculateFreeCashFlowYield(float $freeCashFlow, float $marketCap): float
    {
        if ($marketCap == 0) {
            return 0.0;
        }
        
        return ($freeCashFlow / $marketCap) * 100;
    }
    
    /**
     * Calculate free cash flow margin
     *
     * @param float $freeCashFlow Free cash flow
     * @param float $revenue Total revenue
     * @return float FCF margin percentage
     */
    public function calculateFreeCashFlowMargin(float $freeCashFlow, float $revenue): float
    {
        if ($revenue == 0) {
            return 0.0;
        }
        
        return ($freeCashFlow / $revenue) * 100;
    }
    
    /**
     * Calculate payout ratio (dividend / net income)
     *
     * @param float $dividendPaid Total dividend paid
     * @param float $netIncome Net income
     * @return float Payout ratio percentage
     */
    public function calculatePayoutRatio(float $dividendPaid, float $netIncome): float
    {
        if ($netIncome == 0) {
            return 0.0;
        }
        
        return ($dividendPaid / $netIncome) * 100;
    }
    
    /**
     * Determine if dividend is sustainable
     * 
     * Sustainable if:
     * - Payout ratio < 60%
     * - Free cash flow covers dividend
     *
     * @param float $dividendPaid Total dividend paid
     * @param float $netIncome Net income
     * @param float $freeCashFlow Free cash flow
     * @return bool True if sustainable
     */
    public function isDividendSustainable(float $dividendPaid, float $netIncome, float $freeCashFlow): bool
    {
        $payoutRatio = $this->calculatePayoutRatio($dividendPaid, $netIncome);
        $fcfCoverage = $this->calculateDividendCoverageByFCF($freeCashFlow, $dividendPaid);
        
        return $payoutRatio < 60.0 && $fcfCoverage >= 1.0;
    }
    
    /**
     * Calculate Return on Equity (ROE)
     *
     * @param float $netIncome Net income
     * @param float $shareholderEquity Shareholder equity
     * @return float ROE percentage
     */
    public function calculateReturnOnEquity(float $netIncome, float $shareholderEquity): float
    {
        if ($shareholderEquity == 0) {
            return 0.0;
        }
        
        return ($netIncome / $shareholderEquity) * 100;
    }
    
    /**
     * Calculate Return on Assets (ROA)
     *
     * @param float $netIncome Net income
     * @param float $totalAssets Total assets
     * @return float ROA percentage
     */
    public function calculateReturnOnAssets(float $netIncome, float $totalAssets): float
    {
        if ($totalAssets == 0) {
            return 0.0;
        }
        
        return ($netIncome / $totalAssets) * 100;
    }
    
    /**
     * Calculate Debt-to-Equity ratio
     *
     * @param float $totalDebt Total debt
     * @param float $shareholderEquity Shareholder equity
     * @return float Debt-to-equity ratio
     */
    public function calculateDebtToEquity(float $totalDebt, float $shareholderEquity): float
    {
        if ($shareholderEquity == 0) {
            return 0.0;
        }
        
        return $totalDebt / $shareholderEquity;
    }
    
    /**
     * Calculate Interest Coverage Ratio (EBIT / Interest Expense)
     *
     * @param float $ebit Earnings before interest and taxes
     * @param float $interestExpense Interest expense
     * @return float Coverage ratio (times)
     */
    public function calculateInterestCoverageRatio(float $ebit, float $interestExpense): float
    {
        if ($interestExpense == 0) {
            return 0.0;
        }
        
        return $ebit / $interestExpense;
    }
    
    /**
     * Generate comprehensive fundamental score (0-100)
     *
     * @param array<string, float> $fundamentals Fundamental data
     * @return array<string, mixed> Scores and analysis
     */
    public function generateFundamentalScore(array $fundamentals): array
    {
        $scores = [
            'earnings_quality_score' => 0,
            'cash_flow_score' => 0,
            'dividend_score' => 0,
            'profitability_score' => 0,
            'leverage_score' => 0
        ];
        
        // Earnings Quality (0-20 points)
        $earningsQuality = $this->calculateEarningsQuality(
            $fundamentals['net_income'] ?? 0,
            $fundamentals['operating_cash_flow'] ?? 0
        );
        if ($earningsQuality >= 1.2) {
            $scores['earnings_quality_score'] = 20;
        } elseif ($earningsQuality >= 1.0) {
            $scores['earnings_quality_score'] = 15;
        } elseif ($earningsQuality >= 0.8) {
            $scores['earnings_quality_score'] = 10;
        } else {
            $scores['earnings_quality_score'] = 5;
        }
        
        // Free Cash Flow (0-20 points)
        $fcfMargin = $this->calculateFreeCashFlowMargin(
            $fundamentals['free_cash_flow'] ?? 0,
            $fundamentals['revenue'] ?? 1
        );
        if ($fcfMargin >= 20) {
            $scores['cash_flow_score'] = 20;
        } elseif ($fcfMargin >= 15) {
            $scores['cash_flow_score'] = 15;
        } elseif ($fcfMargin >= 10) {
            $scores['cash_flow_score'] = 10;
        } elseif ($fcfMargin >= 5) {
            $scores['cash_flow_score'] = 5;
        }
        
        // Dividend Sustainability (0-20 points)
        if (isset($fundamentals['dividend_paid']) && $fundamentals['dividend_paid'] > 0) {
            $isSustainable = $this->isDividendSustainable(
                $fundamentals['dividend_paid'],
                $fundamentals['net_income'] ?? 0,
                $fundamentals['free_cash_flow'] ?? 0
            );
            $scores['dividend_score'] = $isSustainable ? 20 : 10;
        } else {
            $scores['dividend_score'] = 15;  // No dividend = neutral
        }
        
        // Profitability (0-20 points)
        $roe = $this->calculateReturnOnEquity(
            $fundamentals['net_income'] ?? 0,
            $fundamentals['shareholder_equity'] ?? 1
        );
        if ($roe >= 20) {
            $scores['profitability_score'] = 20;
        } elseif ($roe >= 15) {
            $scores['profitability_score'] = 15;
        } elseif ($roe >= 10) {
            $scores['profitability_score'] = 10;
        } elseif ($roe >= 5) {
            $scores['profitability_score'] = 5;
        }
        
        // Leverage (0-20 points)
        $debtToEquity = $this->calculateDebtToEquity(
            $fundamentals['total_debt'] ?? 0,
            $fundamentals['shareholder_equity'] ?? 1
        );
        if ($debtToEquity <= 0.3) {
            $scores['leverage_score'] = 20;
        } elseif ($debtToEquity <= 0.5) {
            $scores['leverage_score'] = 15;
        } elseif ($debtToEquity <= 1.0) {
            $scores['leverage_score'] = 10;
        } elseif ($debtToEquity <= 2.0) {
            $scores['leverage_score'] = 5;
        }
        
        $scores['total_score'] = array_sum($scores);
        
        return $scores;
    }
    
    /**
     * Generate Buffett-style fundamental analysis report
     *
     * @param string $symbol Stock symbol
     * @param array<string, float> $fundamentals Fundamental data
     * @return string Formatted report
     */
    public function generateBuffettStyleReport(string $symbol, array $fundamentals): string
    {
        $report = str_repeat('=', 80) . "\n";
        $report .= sprintf("BUFFETT-STYLE FUNDAMENTAL ANALYSIS: %s\n", $symbol);
        $report .= str_repeat('=', 80) . "\n\n";
        
        // Earnings Quality
        $earningsQuality = $this->calculateEarningsQuality(
            $fundamentals['net_income'] ?? 0,
            $fundamentals['operating_cash_flow'] ?? 0
        );
        $report .= "Earnings Quality:\n";
        $report .= str_repeat('-', 80) . "\n";
        $report .= sprintf("Operating Cash Flow / Net Income: %.2fx\n", $earningsQuality);
        $report .= sprintf("Quality Rating: %s\n\n", 
            $earningsQuality >= 1.2 ? 'Excellent' : 
            ($earningsQuality >= 1.0 ? 'Good' : 
            ($earningsQuality >= 0.8 ? 'Fair' : 'Poor'))
        );
        
        // Free Cash Flow
        $fcfMargin = $this->calculateFreeCashFlowMargin(
            $fundamentals['free_cash_flow'] ?? 0,
            $fundamentals['revenue'] ?? 1
        );
        $fcfYield = $this->calculateFreeCashFlowYield(
            $fundamentals['free_cash_flow'] ?? 0,
            $fundamentals['market_cap'] ?? 1
        );
        $report .= "Free Cash Flow Analysis:\n";
        $report .= str_repeat('-', 80) . "\n";
        $report .= sprintf("FCF Margin: %.2f%%\n", $fcfMargin);
        $report .= sprintf("FCF Yield: %.2f%%\n\n", $fcfYield);
        
        // Dividend Analysis
        if (isset($fundamentals['dividend_paid']) && $fundamentals['dividend_paid'] > 0) {
            $payoutRatio = $this->calculatePayoutRatio(
                $fundamentals['dividend_paid'],
                $fundamentals['net_income'] ?? 1
            );
            $divCoverageEarnings = $this->calculateDividendCoverageRatio(
                $fundamentals['net_income'] ?? 0,
                $fundamentals['dividend_paid']
            );
            $divCoverageFCF = $this->calculateDividendCoverageByFCF(
                $fundamentals['free_cash_flow'] ?? 0,
                $fundamentals['dividend_paid']
            );
            $isSustainable = $this->isDividendSustainable(
                $fundamentals['dividend_paid'],
                $fundamentals['net_income'] ?? 0,
                $fundamentals['free_cash_flow'] ?? 0
            );
            
            $report .= "Dividend Sustainability:\n";
            $report .= str_repeat('-', 80) . "\n";
            $report .= sprintf("Payout Ratio: %.2f%%\n", $payoutRatio);
            $report .= sprintf("Coverage by Earnings: %.2fx\n", $divCoverageEarnings);
            $report .= sprintf("Coverage by FCF: %.2fx\n", $divCoverageFCF);
            $report .= sprintf("Sustainable: %s\n\n", $isSustainable ? 'YES' : 'NO');
        }
        
        // Profitability
        $roe = $this->calculateReturnOnEquity(
            $fundamentals['net_income'] ?? 0,
            $fundamentals['shareholder_equity'] ?? 1
        );
        $roa = $this->calculateReturnOnAssets(
            $fundamentals['net_income'] ?? 0,
            $fundamentals['total_assets'] ?? 1
        );
        $report .= "Profitability:\n";
        $report .= str_repeat('-', 80) . "\n";
        $report .= sprintf("Return on Equity (ROE): %.2f%%\n", $roe);
        $report .= sprintf("Return on Assets (ROA): %.2f%%\n", $roa);
        $report .= sprintf("ROE Rating: %s\n\n", 
            $roe >= 20 ? 'Excellent' : 
            ($roe >= 15 ? 'Good' : 
            ($roe >= 10 ? 'Fair' : 'Poor'))
        );
        
        // Leverage
        $debtToEquity = $this->calculateDebtToEquity(
            $fundamentals['total_debt'] ?? 0,
            $fundamentals['shareholder_equity'] ?? 1
        );
        $interestCoverage = $this->calculateInterestCoverageRatio(
            $fundamentals['ebit'] ?? 0,
            $fundamentals['interest_expense'] ?? 1
        );
        $report .= "Financial Leverage:\n";
        $report .= str_repeat('-', 80) . "\n";
        $report .= sprintf("Debt-to-Equity: %.2f\n", $debtToEquity);
        $report .= sprintf("Interest Coverage: %.2fx\n", $interestCoverage);
        $report .= sprintf("Leverage Rating: %s\n\n", 
            $debtToEquity <= 0.5 ? 'Conservative' : 
            ($debtToEquity <= 1.0 ? 'Moderate' : 'Aggressive')
        );
        
        // Overall Score
        $scores = $this->generateFundamentalScore($fundamentals);
        $report .= "Overall Fundamental Score:\n";
        $report .= str_repeat('-', 80) . "\n";
        $report .= sprintf("Total Score: %d/100\n", $scores['total_score']);
        $report .= sprintf("Rating: %s\n\n", 
            $scores['total_score'] >= 80 ? 'Excellent' : 
            ($scores['total_score'] >= 60 ? 'Good' : 
            ($scores['total_score'] >= 40 ? 'Fair' : 'Poor'))
        );
        
        $report .= str_repeat('=', 80) . "\n";
        
        return $report;
    }
}
