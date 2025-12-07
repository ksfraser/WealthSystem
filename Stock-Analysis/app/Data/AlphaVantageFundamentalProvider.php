<?php

declare(strict_types=1);

namespace App\Data;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Alpha Vantage fundamental data provider
 * 
 * Fetches company fundamentals using Alpha Vantage API
 * API Docs: https://www.alphavantage.co/documentation/
 * 
 * Rate Limits (Free tier):
 * - 25 requests per day
 * - 5 API requests per minute
 */
class AlphaVantageFundamentalProvider implements FundamentalDataProviderInterface
{
    private const API_URL = 'https://www.alphavantage.co/query';
    
    // API endpoints
    private const FUNCTION_OVERVIEW = 'OVERVIEW';
    private const FUNCTION_INCOME_STATEMENT = 'INCOME_STATEMENT';
    private const FUNCTION_BALANCE_SHEET = 'BALANCE_SHEET';
    private const FUNCTION_CASH_FLOW = 'CASH_FLOW';

    private LoggerInterface $logger;
    private Client $httpClient;

    public function __construct(
        private readonly string $apiKey,
        ?LoggerInterface $logger = null,
        ?Client $httpClient = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = $httpClient ?? new Client(['timeout' => 10]);
    }

    public function getFundamentals(string $ticker): FundamentalData
    {
        if (!$this->isAvailable()) {
            return new FundamentalData(
                ticker: $ticker,
                provider: 'alpha_vantage',
                error: 'Alpha Vantage API key not configured'
            );
        }

        $this->logger->info("Fetching fundamentals for {$ticker} from Alpha Vantage");

        try {
            // Fetch company overview (includes key ratios and metrics)
            $overview = $this->fetchOverview($ticker);
            
            if (isset($overview['Note']) || isset($overview['Information'])) {
                // Rate limit or other API message
                $error = $overview['Note'] ?? $overview['Information'] ?? 'API limit reached';
                $this->logger->warning("Alpha Vantage API message: {$error}");
                
                return new FundamentalData(
                    ticker: $ticker,
                    provider: 'alpha_vantage',
                    error: $error
                );
            }

            // Parse overview data
            $companyName = $overview['Name'] ?? null;
            $sector = $overview['Sector'] ?? null;
            $industry = $overview['Industry'] ?? null;
            $marketCap = isset($overview['MarketCapitalization']) 
                ? (float)$overview['MarketCapitalization'] 
                : null;

            // Extract financial ratios
            $ratios = $this->extractRatios($overview);

            // Extract valuation metrics
            $valuation = $this->extractValuation($overview);

            // Extract growth metrics (from overview data)
            $growth = $this->extractGrowth($overview);

            // Extract key financials (from overview data)
            $financials = $this->extractFinancials($overview);

            $this->logger->info("Successfully fetched fundamentals for {$ticker}");

            return new FundamentalData(
                ticker: $ticker,
                companyName: $companyName,
                sector: $sector,
                industry: $industry,
                marketCap: $marketCap,
                financials: $financials,
                ratios: $ratios,
                growth: $growth,
                valuation: $valuation,
                provider: 'alpha_vantage',
                fetchedAt: new \DateTimeImmutable()
            );

        } catch (\Exception $e) {
            $this->logger->error("Failed to fetch fundamentals for {$ticker}: " . $e->getMessage());
            
            return new FundamentalData(
                ticker: $ticker,
                provider: 'alpha_vantage',
                error: $e->getMessage()
            );
        }
    }

    public function getBatchFundamentals(array $tickers): array
    {
        $results = [];
        
        foreach ($tickers as $ticker) {
            $results[$ticker] = $this->getFundamentals($ticker);
            
            // Respect rate limits (5 per minute)
            // Add 12 second delay between requests
            if (count($tickers) > 1) {
                sleep(12);
            }
        }
        
        return $results;
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getProviderName(): string
    {
        return 'Alpha Vantage';
    }

    public function getRateLimits(): array
    {
        return [
            'calls_per_day' => 25,
            'calls_per_minute' => 5,
        ];
    }

    /**
     * Fetch company overview from Alpha Vantage
     */
    private function fetchOverview(string $ticker): array
    {
        $response = $this->httpClient->get(self::API_URL, [
            'query' => [
                'function' => self::FUNCTION_OVERVIEW,
                'symbol' => $ticker,
                'apikey' => $this->apiKey,
            ],
        ]);

        $body = json_decode((string)$response->getBody(), true);
        
        if (!is_array($body)) {
            throw new \RuntimeException("Invalid response from Alpha Vantage");
        }

        return $body;
    }

    /**
     * Extract financial ratios from overview
     */
    private function extractRatios(array $overview): array
    {
        $ratios = [];

        // Profitability ratios
        if (isset($overview['ProfitMargin'])) {
            $ratios['profit_margin'] = (float)$overview['ProfitMargin'] * 100;
        }
        if (isset($overview['OperatingMarginTTM'])) {
            $ratios['operating_margin'] = (float)$overview['OperatingMarginTTM'] * 100;
        }
        if (isset($overview['ReturnOnAssetsTTM'])) {
            $ratios['roa'] = (float)$overview['ReturnOnAssetsTTM'] * 100;
        }
        if (isset($overview['ReturnOnEquityTTM'])) {
            $ratios['roe'] = (float)$overview['ReturnOnEquityTTM'] * 100;
        }

        // Valuation ratios
        if (isset($overview['PERatio'])) {
            $ratios['pe_ratio'] = (float)$overview['PERatio'];
        }
        if (isset($overview['PEGRatio'])) {
            $ratios['peg_ratio'] = (float)$overview['PEGRatio'];
        }
        if (isset($overview['PriceToBookRatio'])) {
            $ratios['pb_ratio'] = (float)$overview['PriceToBookRatio'];
        }
        if (isset($overview['PriceToSalesRatioTTM'])) {
            $ratios['ps_ratio'] = (float)$overview['PriceToSalesRatioTTM'];
        }

        // Efficiency ratios
        if (isset($overview['AssetTurnover'])) {
            $ratios['asset_turnover'] = (float)$overview['AssetTurnover'];
        }

        // Liquidity ratios
        if (isset($overview['CurrentRatio'])) {
            $ratios['current_ratio'] = (float)$overview['CurrentRatio'];
        }
        if (isset($overview['QuickRatio'])) {
            $ratios['quick_ratio'] = (float)$overview['QuickRatio'];
        }

        // Leverage ratios
        if (isset($overview['DebtToEquity'])) {
            $ratios['debt_to_equity'] = (float)$overview['DebtToEquity'];
        }

        return $ratios;
    }

    /**
     * Extract valuation metrics
     */
    private function extractValuation(array $overview): array
    {
        $valuation = [];

        if (isset($overview['MarketCapitalization'])) {
            $valuation['market_cap'] = (float)$overview['MarketCapitalization'];
        }
        if (isset($overview['EBITDA'])) {
            $valuation['ebitda'] = (float)$overview['EBITDA'];
        }
        if (isset($overview['EVToRevenue'])) {
            $valuation['ev_to_revenue'] = (float)$overview['EVToRevenue'];
        }
        if (isset($overview['EVToEBITDA'])) {
            $valuation['ev_to_ebitda'] = (float)$overview['EVToEBITDA'];
        }
        if (isset($overview['BookValue'])) {
            $valuation['book_value'] = (float)$overview['BookValue'];
        }
        if (isset($overview['DividendYield'])) {
            $valuation['dividend_yield'] = (float)$overview['DividendYield'] * 100;
        }

        return $valuation;
    }

    /**
     * Extract growth metrics
     */
    private function extractGrowth(array $overview): array
    {
        $growth = [];

        if (isset($overview['QuarterlyRevenueGrowthYOY'])) {
            $growth['revenue_growth_yoy'] = (float)$overview['QuarterlyRevenueGrowthYOY'] * 100;
        }
        if (isset($overview['QuarterlyEarningsGrowthYOY'])) {
            $growth['earnings_growth_yoy'] = (float)$overview['QuarterlyEarningsGrowthYOY'] * 100;
        }
        if (isset($overview['RevenuePerShareTTM'])) {
            $growth['revenue_per_share'] = (float)$overview['RevenuePerShareTTM'];
        }
        if (isset($overview['EPSGrowth'])) {
            $growth['eps_growth'] = (float)$overview['EPSGrowth'] * 100;
        }

        return $growth;
    }

    /**
     * Extract key financials
     */
    private function extractFinancials(array $overview): array
    {
        $financials = [];

        // Income statement items
        if (isset($overview['RevenueTTM'])) {
            $financials['revenue_ttm'] = (float)$overview['RevenueTTM'];
        }
        if (isset($overview['GrossProfitTTM'])) {
            $financials['gross_profit_ttm'] = (float)$overview['GrossProfitTTM'];
        }
        if (isset($overview['OperatingIncomeTTM'])) {
            $financials['operating_income_ttm'] = (float)$overview['OperatingIncomeTTM'];
        }
        if (isset($overview['NetIncomeTTM'])) {
            $financials['net_income_ttm'] = (float)$overview['NetIncomeTTM'];
        }

        // Per share metrics
        if (isset($overview['EPS'])) {
            $financials['eps'] = (float)$overview['EPS'];
        }
        if (isset($overview['DilutedEPSTTM'])) {
            $financials['diluted_eps_ttm'] = (float)$overview['DilutedEPSTTM'];
        }

        // Share metrics
        if (isset($overview['SharesOutstanding'])) {
            $financials['shares_outstanding'] = (float)$overview['SharesOutstanding'];
        }

        return $financials;
    }
}
