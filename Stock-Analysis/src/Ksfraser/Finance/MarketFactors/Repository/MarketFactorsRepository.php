<?php
namespace Ksfraser\Finance\MarketFactors\Repository;

use Ksfraser\Finance\MarketFactors\Entities\MarketFactor;
use Ksfraser\Finance\MarketFactors\Entities\SectorPerformance;
use Ksfraser\Finance\MarketFactors\Entities\IndexPerformance;
use Ksfraser\Finance\MarketFactors\Entities\ForexRate;
use Ksfraser\Finance\MarketFactors\Entities\EconomicIndicator;

/**
 * Market Factors Repository
 * 
 * Data access layer for market factors
 */
class MarketFactorsRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Save a market factor
     */
    public function saveFactor(MarketFactor $factor): bool
    {
        // For SQLite compatibility, use INSERT OR REPLACE instead of ON DUPLICATE KEY UPDATE
        $sql = "INSERT OR REPLACE INTO market_factors 
                (symbol, name, type, value, change_amount, change_percent, timestamp, metadata)
                VALUES (:symbol, :name, :type, :value, :change_amount, :change_percent, :timestamp, :metadata)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':symbol' => $factor->getSymbol(),
            ':name' => $factor->getName(),
            ':type' => $factor->getType(),
            ':value' => $factor->getValue(),
            ':change_amount' => $factor->getChange(),
            ':change_percent' => $factor->getChangePercent(),
            ':timestamp' => $factor->getTimestamp()->format('Y-m-d H:i:s'),
            ':metadata' => json_encode($factor->getMetadata())
        ]);
    }

    /**
     * Delete a factor by symbol
     */
    public function deleteFactor(string $symbol): bool
    {
        $sql = "DELETE FROM market_factors WHERE symbol = :symbol";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([':symbol' => $symbol]);
        
        return $result && $stmt->rowCount() > 0;
    }

    /**
     * Get a factor by symbol
     */
    public function getFactorBySymbol(string $symbol): ?MarketFactor
    {
        $sql = "SELECT * FROM market_factors WHERE symbol = :symbol ORDER BY timestamp DESC LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':symbol' => $symbol]);
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        
        return $this->createFactorFromRow($row);
    }

    /**
     * Get factors by type
     */
    public function getFactorsByType(string $type, int $limit = 100): array
    {
        $sql = "SELECT * FROM market_factors WHERE type = :type ORDER BY timestamp DESC LIMIT :limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        $factors = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $factors[] = $this->createFactorFromRow($row);
        }
        
        return $factors;
    }

    /**
     * Get all current factors
     */
    public function getCurrentFactors(): array
    {
        $sql = "SELECT * FROM market_factors ORDER BY timestamp DESC";
        $stmt = $this->pdo->query($sql);
        
        $factors = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $factors[] = $this->createFactorFromRow($row);
        }
        
        return $factors;
    }

    /**
     * Get all factors (alias for getCurrentFactors for service compatibility)
     */
    public function getAllFactors(): array
    {
        return $this->getCurrentFactors();
    }

    /**
     * Search factors with filters
     */
    public function searchFactors(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $conditions = [];
        $params = [];
        
        if (!empty($filters['type'])) {
            $conditions[] = "type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['symbol_like'])) {
            $conditions[] = "symbol LIKE :symbol_like";
            $params[':symbol_like'] = '%' . $filters['symbol_like'] . '%';
        }
        
        if (!empty($filters['name_like'])) {
            $conditions[] = "name LIKE :name_like";
            $params[':name_like'] = '%' . $filters['name_like'] . '%';
        }
        
        if (isset($filters['min_change_percent'])) {
            $conditions[] = "change_percent >= :min_change_percent";
            $params[':min_change_percent'] = $filters['min_change_percent'];
        }
        
        if (isset($filters['max_change_percent'])) {
            $conditions[] = "change_percent <= :max_change_percent";
            $params[':max_change_percent'] = $filters['max_change_percent'];
        }
        
        if (!empty($filters['since'])) {
            $conditions[] = "timestamp >= :since";
            $params[':since'] = $filters['since'];
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $orderBy = $filters['order_by'] ?? 'timestamp DESC';
        
        $sql = "SELECT * FROM market_factors {$whereClause} ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $factors = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $factors[] = $this->createFactorFromRow($row);
        }
        
        return $factors;
    }

    /**
     * Get historical data for a factor
     */
    public function getHistoricalData(string $symbol, int $days = 30): array
    {
        $sql = "SELECT * FROM market_factors 
                WHERE symbol = :symbol 
                AND timestamp >= datetime('now', '-{$days} days')
                ORDER BY timestamp ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':symbol' => $symbol]);
        
        $data = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = [
                'timestamp' => $row['timestamp'],
                'value' => (float)$row['value'],
                'change_percent' => (float)$row['change_percent']
            ];
        }
        
        return $data;
    }

    /**
     * Save sector performance
     */
    public function saveSectorPerformance(SectorPerformance $sector): bool
    {
        $sql = "INSERT OR REPLACE INTO sector_performance 
                (sector_code, sector_name, classification, performance_value, change_percent, 
                 market_cap_weight, timestamp, metadata)
                VALUES (:sector_code, :sector_name, :classification, :performance_value, 
                        :change_percent, :market_cap_weight, :timestamp, :metadata)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':sector_code' => $sector->getSymbol(),
            ':sector_name' => $sector->getSectorName(),
            ':classification' => $sector->getClassification(),
            ':performance_value' => $sector->getValue(),
            ':change_percent' => $sector->getChangePercent(),
            ':market_cap_weight' => $sector->getMarketCapWeight(),
            ':timestamp' => $sector->getTimestamp()->format('Y-m-d H:i:s'),
            ':metadata' => json_encode($sector->getMetadata())
        ]);
    }

    /**
     * Save index performance
     */
    public function saveIndexPerformance(IndexPerformance $index): bool
    {
        $sql = "INSERT OR REPLACE INTO index_performance 
                (index_symbol, index_name, region, asset_class, value, change_percent, 
                 constituents, market_cap, currency, timestamp, metadata)
                VALUES (:index_symbol, :index_name, :region, :asset_class, :value, 
                        :change_percent, :constituents, :market_cap, :currency, :timestamp, :metadata)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':index_symbol' => $index->getSymbol(),
            ':index_name' => $index->getIndexName(),
            ':region' => $index->getRegion(),
            ':asset_class' => $index->getAssetClass(),
            ':value' => $index->getValue(),
            ':change_percent' => $index->getChangePercent(),
            ':constituents' => $index->getConstituents(),
            ':market_cap' => $index->getMarketCap(),
            ':currency' => $index->getCurrency(),
            ':timestamp' => $index->getTimestamp()->format('Y-m-d H:i:s'),
            ':metadata' => json_encode($index->getMetadata())
        ]);
    }

    /**
     * Save forex rate
     */
    public function saveForexRate(ForexRate $forex): bool
    {
        $sql = "INSERT OR REPLACE INTO forex_rates 
                (base_currency, quote_currency, pair, rate, bid, ask, spread, 
                 change_percent, timestamp, metadata)
                VALUES (:base_currency, :quote_currency, :pair, :rate, :bid, :ask, 
                        :spread, :change_percent, :timestamp, :metadata)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':base_currency' => $forex->getBaseCurrency(),
            ':quote_currency' => $forex->getQuoteCurrency(),
            ':pair' => $forex->getPair(),
            ':rate' => $forex->getValue(),
            ':bid' => $forex->getBid(),
            ':ask' => $forex->getAsk(),
            ':spread' => $forex->getSpread(),
            ':change_percent' => $forex->getChangePercent(),
            ':timestamp' => $forex->getTimestamp()->format('Y-m-d H:i:s'),
            ':metadata' => json_encode($forex->getMetadata())
        ]);
    }

    /**
     * Save economic indicator
     */
    public function saveEconomicIndicator(EconomicIndicator $indicator): bool
    {
        $sql = "INSERT OR REPLACE INTO economic_indicators 
                (indicator_code, indicator_name, country, frequency, unit, current_value, 
                 previous_value, forecast_value, change_percent, importance, release_date, 
                 source, timestamp, metadata)
                VALUES (:indicator_code, :indicator_name, :country, :frequency, :unit, 
                        :current_value, :previous_value, :forecast_value, :change_percent, 
                        :importance, :release_date, :source, :timestamp, :metadata)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':indicator_code' => $indicator->getSymbol(),
            ':indicator_name' => $indicator->getName(),
            ':country' => $indicator->getCountry(),
            ':frequency' => $indicator->getFrequency(),
            ':unit' => $indicator->getUnit(),
            ':current_value' => $indicator->getValue(),
            ':previous_value' => $indicator->getPreviousValue(),
            ':forecast_value' => $indicator->getForecast(),
            ':change_percent' => $indicator->getChangePercent(),
            ':importance' => $indicator->getImportance(),
            ':release_date' => $indicator->getReleaseDate()->format('Y-m-d H:i:s'),
            ':source' => $indicator->getSource(),
            ':timestamp' => $indicator->getTimestamp()->format('Y-m-d H:i:s'),
            ':metadata' => json_encode($indicator->getMetadata())
        ]);
    }

    /**
     * Get correlation between factors
     */
    public function getCorrelation(string $symbol1, string $symbol2, int $periodDays = 30): ?float
    {
        $sql = "SELECT correlation_coefficient FROM factor_correlations 
                WHERE ((factor1_symbol = :symbol1 AND factor2_symbol = :symbol2) 
                    OR (factor1_symbol = :symbol2 AND factor2_symbol = :symbol1))
                AND period_days = :period_days
                ORDER BY calculation_date DESC LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':symbol1' => $symbol1,
            ':symbol2' => $symbol2,
            ':period_days' => $periodDays
        ]);
        
        $result = $stmt->fetchColumn();
        return $result !== false ? (float)$result : null;
    }

    /**
     * Save correlation
     */
    public function saveCorrelation(string $symbol1, string $symbol2, float $correlation, 
                                   int $periodDays = 30, ?float $pValue = null, ?int $sampleSize = null): bool
    {
        $sql = "INSERT OR REPLACE INTO factor_correlations 
                (factor1_symbol, factor2_symbol, correlation_coefficient, period_days, 
                 calculation_date, significance_level, sample_size)
                VALUES (:factor1_symbol, :factor2_symbol, :correlation_coefficient, 
                        :period_days, :calculation_date, :significance_level, :sample_size)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            ':factor1_symbol' => $symbol1,
            ':factor2_symbol' => $symbol2,
            ':correlation_coefficient' => $correlation,
            ':period_days' => $periodDays,
            ':calculation_date' => date('Y-m-d'),
            ':significance_level' => $pValue,
            ':sample_size' => $sampleSize
        ]);
    }

    /**
     * Get performance summary
     */
    public function getPerformanceSummary(string $type = null, int $days = 1): array
    {
        $typeCondition = $type ? "AND type = :type" : "";
        
        $sql = "SELECT 
                    type,
                    COUNT(*) as factor_count,
                    AVG(change_percent) as avg_change_percent,
                    MAX(change_percent) as max_change_percent,
                    MIN(change_percent) as min_change_percent,
                    SUM(CASE WHEN change_percent > 0 THEN 1 ELSE 0 END) as positive_count,
                    SUM(CASE WHEN change_percent < 0 THEN 1 ELSE 0 END) as negative_count
                FROM current_market_factors 
                WHERE timestamp >= datetime('now', '-{$days} days') {$typeCondition}
                GROUP BY type";
        
        $stmt = $this->pdo->prepare($sql);
        if ($type) {
            $stmt->bindValue(':type', $type);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Delete old data
     */
    public function cleanupOldData(int $daysToKeep = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        $tables = [
            'market_factors',
            'sector_performance', 
            'index_performance',
            'forex_rates',
            'economic_indicators'
        ];
        
        $totalDeleted = 0;
        
        foreach ($tables as $table) {
            $sql = "DELETE FROM {$table} WHERE timestamp < :cutoff_date";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':cutoff_date' => $cutoffDate]);
            $totalDeleted += $stmt->rowCount();
        }
        
        return $totalDeleted;
    }

    /**
     * Create factor object from database row
     */
    private function createFactorFromRow(array $row): MarketFactor
    {
        $metadata = !empty($row['metadata']) ? json_decode($row['metadata'], true) : [];
        
        return new MarketFactor(
            $row['symbol'],
            $row['name'],
            $row['type'],
            (float)$row['value'],
            (float)($row['change_amount'] ?? 0),
            (float)($row['change_percent'] ?? 0),
            new \DateTime($row['timestamp']),
            $metadata
        );
    }

    /**
     * Get database statistics
     */
    public function getDatabaseStats(): array
    {
        $stats = [];
        
        // Count records in each table
        $tables = [
            'market_factors',
            'sector_performance',
            'index_performance', 
            'forex_rates',
            'economic_indicators',
            'factor_correlations'
        ];
        
        foreach ($tables as $table) {
            $sql = "SELECT COUNT(*) as count, MAX(timestamp) as latest_update FROM {$table}";
            $stmt = $this->pdo->query($sql);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stats[$table] = $result;
        }
        
        // Get current factors count
        $sql = "SELECT COUNT(*) as current_count FROM current_market_factors";
        $stmt = $this->pdo->query($sql);
        $stats['current_factors'] = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $stats;
    }
}
