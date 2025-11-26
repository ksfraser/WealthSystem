<?php
namespace Ksfraser\Finance\MarketFactors\Entities;

/**
 * Economic Indicator Entity
 * 
 * Tracks key economic indicators that influence market performance
 */
class EconomicIndicator extends MarketFactor
{
    private string $country;
    private string $frequency; // daily, weekly, monthly, quarterly, annual
    private string $unit;
    private float $previousValue;
    private float $forecast;
    private string $importance; // high, medium, low
    private \DateTime $releaseDate;
    private string $source;

    public function __construct(
        string $indicatorCode,
        string $indicatorName,
        string $country,
        float $currentValue,
        float $previousValue = 0.0,
        float $forecast = 0.0,
        string $frequency = 'monthly',
        string $unit = '',
        string $importance = 'medium',
        string $source = '',
        ?\DateTime $releaseDate = null,
        ?\DateTime $timestamp = null
    ) {
        $change = $currentValue - $previousValue;
        $changePercent = $previousValue != 0 ? ($change / $previousValue) * 100 : 0;
        
        parent::__construct(
            $indicatorCode,
            $indicatorName,
            'economic',
            $currentValue,
            $change,
            $changePercent,
            $timestamp,
            [
                'country' => $country,
                'frequency' => $frequency,
                'unit' => $unit,
                'previous_value' => $previousValue,
                'forecast' => $forecast,
                'importance' => $importance,
                'source' => $source,
                'release_date' => $releaseDate ? $releaseDate->format('Y-m-d H:i:s') : null
            ]
        );
        
        $this->country = $country;
        $this->frequency = $frequency;
        $this->unit = $unit;
        $this->previousValue = $previousValue;
        $this->forecast = $forecast;
        $this->importance = $importance;
        $this->releaseDate = $releaseDate ?? new \DateTime();
        $this->source = $source;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function getPreviousValue(): float
    {
        return $this->previousValue;
    }

    public function getForecast(): float
    {
        return $this->forecast;
    }

    public function getImportance(): string
    {
        return $this->importance;
    }

    public function getReleaseDate(): \DateTime
    {
        return $this->releaseDate;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Check if actual value beat forecast
     */
    public function beatForecast(): bool
    {
        if ($this->forecast == 0) return false;
        return $this->getValue() > $this->forecast;
    }

    /**
     * Check if actual value missed forecast
     */
    public function missedForecast(): bool
    {
        if ($this->forecast == 0) return false;
        return $this->getValue() < $this->forecast;
    }

    /**
     * Get surprise factor (actual vs forecast)
     */
    public function getSurpriseFactor(): float
    {
        if ($this->forecast == 0) return 0;
        return (($this->getValue() - $this->forecast) / $this->forecast) * 100;
    }

    /**
     * Check if indicator is improving
     */
    public function isImproving(): bool
    {
        // Different indicators have different "good" directions
        $improvingIndicators = [
            'GDP', 'EMPLOYMENT', 'RETAIL_SALES', 'INDUSTRIAL_PRODUCTION',
            'CONSUMER_CONFIDENCE', 'PMI', 'HOUSING_STARTS', 'WAGES'
        ];
        
        $decliningIndicators = [
            'UNEMPLOYMENT', 'INFLATION', 'CPI', 'PPI', 'JOBLESS_CLAIMS',
            'TRADE_DEFICIT', 'GOVERNMENT_DEBT'
        ];
        
        if (in_array($this->getSymbol(), $improvingIndicators)) {
            return $this->getChangePercent() > 0;
        } elseif (in_array($this->getSymbol(), $decliningIndicators)) {
            return $this->getChangePercent() < 0;
        }
        
        return $this->getChangePercent() > 0; // Default to positive change = improvement
    }

    /**
     * Get market impact weight based on importance
     */
    public function getMarketImpactWeight(): float
    {
        switch (strtolower($this->importance)) {
            case 'high':
                return 1.0;
            case 'medium':
                return 0.6;
            case 'low':
                return 0.3;
            default:
                return 0.5;
        }
    }

    /**
     * Get key economic indicators
     */
    public static function getKeyIndicators(): array
    {
        return [
            // US Indicators
            'US_GDP' => ['name' => 'GDP Growth Rate', 'country' => 'US', 'importance' => 'high'],
            'US_UNEMPLOYMENT' => ['name' => 'Unemployment Rate', 'country' => 'US', 'importance' => 'high'],
            'US_CPI' => ['name' => 'Consumer Price Index', 'country' => 'US', 'importance' => 'high'],
            'US_PPI' => ['name' => 'Producer Price Index', 'country' => 'US', 'importance' => 'medium'],
            'US_NFP' => ['name' => 'Non-Farm Payrolls', 'country' => 'US', 'importance' => 'high'],
            'US_RETAIL_SALES' => ['name' => 'Retail Sales', 'country' => 'US', 'importance' => 'medium'],
            'US_PMI' => ['name' => 'Manufacturing PMI', 'country' => 'US', 'importance' => 'medium'],
            'US_CONSUMER_CONFIDENCE' => ['name' => 'Consumer Confidence', 'country' => 'US', 'importance' => 'medium'],
            
            // Canadian Indicators
            'CA_GDP' => ['name' => 'GDP Growth Rate', 'country' => 'CA', 'importance' => 'high'],
            'CA_UNEMPLOYMENT' => ['name' => 'Unemployment Rate', 'country' => 'CA', 'importance' => 'high'],
            'CA_CPI' => ['name' => 'Consumer Price Index', 'country' => 'CA', 'importance' => 'high'],
            'CA_EMPLOYMENT' => ['name' => 'Employment Change', 'country' => 'CA', 'importance' => 'high'],
            'CA_HOUSING_STARTS' => ['name' => 'Housing Starts', 'country' => 'CA', 'importance' => 'medium'],
            'CA_TRADE_BALANCE' => ['name' => 'Trade Balance', 'country' => 'CA', 'importance' => 'medium'],
            
            // Global Indicators
            'GLOBAL_OIL_PRICE' => ['name' => 'Crude Oil Price', 'country' => 'Global', 'importance' => 'high'],
            'GLOBAL_GOLD_PRICE' => ['name' => 'Gold Price', 'country' => 'Global', 'importance' => 'medium'],
            'VIX' => ['name' => 'Volatility Index', 'country' => 'US', 'importance' => 'high'],
            'DXY' => ['name' => 'US Dollar Index', 'country' => 'US', 'importance' => 'high']
        ];
    }

    /**
     * Get leading vs lagging classification
     */
    public function getIndicatorType(): string
    {
        $leadingIndicators = [
            'PMI', 'CONSUMER_CONFIDENCE', 'HOUSING_STARTS', 'JOBLESS_CLAIMS', 
            'VIX', 'YIELD_CURVE', 'STOCK_PRICES'
        ];
        
        $laggingIndicators = [
            'GDP', 'UNEMPLOYMENT', 'CPI', 'PPI', 'EMPLOYMENT'
        ];
        
        if (in_array($this->getSymbol(), $leadingIndicators)) {
            return 'Leading';
        } elseif (in_array($this->getSymbol(), $laggingIndicators)) {
            return 'Lagging';
        }
        
        return 'Coincident';
    }
}
