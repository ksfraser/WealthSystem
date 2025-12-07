<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Interface for fundamental data providers
 * 
 * Defines the contract for fetching company fundamental data from various sources
 * (Alpha Vantage, Financial Modeling Prep, Yahoo Finance, etc.)
 */
interface FundamentalDataProviderInterface
{
    /**
     * Get comprehensive fundamental data for a ticker
     * 
     * @param string $ticker Stock ticker symbol
     * @return FundamentalData
     * @throws \RuntimeException If data fetch fails
     */
    public function getFundamentals(string $ticker): FundamentalData;

    /**
     * Get batch fundamentals for multiple tickers
     * 
     * @param array<string> $tickers Array of ticker symbols
     * @return array<string, FundamentalData> Map of ticker => FundamentalData
     */
    public function getBatchFundamentals(array $tickers): array;

    /**
     * Check if this provider is available (has API key, etc.)
     * 
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Get provider name
     * 
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Get rate limit information
     * 
     * @return array{calls_per_day: int, calls_per_minute: int}
     */
    public function getRateLimits(): array;
}

/**
 * Fundamental data for a company
 */
class FundamentalData
{
    /**
     * @param string $ticker Stock ticker
     * @param string|null $companyName Company name
     * @param string|null $sector Industry sector
     * @param string|null $industry Specific industry
     * @param float|null $marketCap Market capitalization
     * @param array<string, mixed>|null $financials Financial statements (income, balance, cash flow)
     * @param array<string, mixed>|null $ratios Financial ratios (P/E, P/S, ROE, etc.)
     * @param array<string, mixed>|null $growth Growth metrics (revenue, earnings, margins)
     * @param array<string, mixed>|null $valuation Valuation metrics
     * @param string $provider Data provider name
     * @param \DateTimeImmutable $fetchedAt When data was fetched
     * @param string|null $error Error message if fetch failed
     */
    public function __construct(
        public readonly string $ticker,
        public readonly ?string $companyName = null,
        public readonly ?string $sector = null,
        public readonly ?string $industry = null,
        public readonly ?float $marketCap = null,
        public readonly ?array $financials = null,
        public readonly ?array $ratios = null,
        public readonly ?array $growth = null,
        public readonly ?array $valuation = null,
        public readonly string $provider = 'unknown',
        public readonly \DateTimeImmutable $fetchedAt = new \DateTimeImmutable(),
        public readonly ?string $error = null
    ) {
    }

    /**
     * Check if data fetch was successful
     */
    public function isValid(): bool
    {
        return $this->error === null && (
            $this->financials !== null ||
            $this->ratios !== null ||
            $this->growth !== null
        );
    }

    /**
     * Get specific financial metric
     */
    public function getMetric(string $category, string $metric): mixed
    {
        return match($category) {
            'financials' => $this->financials[$metric] ?? null,
            'ratios' => $this->ratios[$metric] ?? null,
            'growth' => $this->growth[$metric] ?? null,
            'valuation' => $this->valuation[$metric] ?? null,
            default => null,
        };
    }

    /**
     * Get age of data in seconds
     */
    public function getAge(): int
    {
        return time() - $this->fetchedAt->getTimestamp();
    }

    /**
     * Check if data is stale (older than threshold)
     */
    public function isStale(int $maxAgeSeconds = 3600): bool
    {
        return $this->getAge() > $maxAgeSeconds;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'ticker' => $this->ticker,
            'company_name' => $this->companyName,
            'sector' => $this->sector,
            'industry' => $this->industry,
            'market_cap' => $this->marketCap,
            'financials' => $this->financials,
            'ratios' => $this->ratios,
            'growth' => $this->growth,
            'valuation' => $this->valuation,
            'provider' => $this->provider,
            'fetched_at' => $this->fetchedAt->format('c'),
            'age_seconds' => $this->getAge(),
            'valid' => $this->isValid(),
            'error' => $this->error,
        ];
    }

    /**
     * Format for LLM prompt inclusion
     */
    public function toPromptString(): string
    {
        if (!$this->isValid()) {
            return "Data unavailable" . ($this->error ? ": {$this->error}" : "");
        }

        $parts = [];

        if ($this->companyName) {
            $parts[] = "Company: {$this->companyName}";
        }

        if ($this->sector) {
            $parts[] = "Sector: {$this->sector}";
        }

        if ($this->marketCap) {
            $parts[] = sprintf("Market Cap: $%.2fM", $this->marketCap / 1_000_000);
        }

        // Financials
        if ($this->financials) {
            $parts[] = "Financials:";
            foreach ($this->financials as $key => $value) {
                $parts[] = "  - {$key}: " . $this->formatValue($value);
            }
        }

        // Ratios
        if ($this->ratios) {
            $parts[] = "Ratios:";
            foreach ($this->ratios as $key => $value) {
                $parts[] = "  - {$key}: " . $this->formatValue($value);
            }
        }

        // Growth
        if ($this->growth) {
            $parts[] = "Growth:";
            foreach ($this->growth as $key => $value) {
                $parts[] = "  - {$key}: " . $this->formatValue($value);
            }
        }

        // Valuation
        if ($this->valuation) {
            $parts[] = "Valuation:";
            foreach ($this->valuation as $key => $value) {
                $parts[] = "  - {$key}: " . $this->formatValue($value);
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Format value for display
     */
    private function formatValue(mixed $value): string
    {
        if ($value === null) {
            return "N/A";
        }

        if (is_float($value)) {
            return number_format($value, 2);
        }

        if (is_bool($value)) {
            return $value ? "Yes" : "No";
        }

        return (string)$value;
    }
}
