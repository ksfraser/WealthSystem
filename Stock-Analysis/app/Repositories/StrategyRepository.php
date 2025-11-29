<?php

namespace App\Repositories;

/**
 * File-based Strategy Repository Implementation
 * 
 * Stores trading strategy data in JSON files organized by strategy and symbol.
 * Provides fast lookups and supports data retention policies.
 */
class StrategyRepository implements StrategyRepositoryInterface
{
    private string $storagePath;
    private string $executionsPath;
    private string $backtestsPath;
    private string $metricsPath;

    /**
     * Constructor
     * 
     * @param string $storagePath Base storage directory
     */
    public function __construct(string $storagePath)
    {
        $this->storagePath = rtrim($storagePath, '/\\');
        $this->executionsPath = $this->storagePath . '/executions';
        $this->backtestsPath = $this->storagePath . '/backtests';
        $this->metricsPath = $this->storagePath . '/metrics';
        
        $this->ensureDirectoriesExist();
    }

    /**
     * {@inheritdoc}
     */
    public function storeExecution(string $strategyName, string $symbol, array $signal, string $timestamp): string
    {
        $executionId = $this->generateExecutionId($strategyName, $symbol, $timestamp);
        
        $execution = [
            'id' => $executionId,
            'strategy' => $strategyName,
            'symbol' => $symbol,
            'timestamp' => $timestamp,
            'signal' => $signal,
            'stored_at' => date('Y-m-d H:i:s')
        ];

        // Store in strategy-specific file
        $strategyFile = $this->getStrategyExecutionsFile($strategyName);
        $this->appendToFile($strategyFile, $execution);

        // Store in symbol-specific file for faster lookups
        $symbolFile = $this->getSymbolExecutionsFile($symbol);
        $this->appendToFile($symbolFile, $execution);

        return $executionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutions(string $symbol, ?string $strategyName = null, int $limit = 100): array
    {
        $symbolFile = $this->getSymbolExecutionsFile($symbol);
        
        if (!file_exists($symbolFile)) {
            return [];
        }

        $executions = $this->readExecutionsFromFile($symbolFile);

        // Filter by strategy if specified
        if ($strategyName !== null) {
            $executions = array_filter($executions, function($exec) use ($strategyName) {
                return $exec['strategy'] === $strategyName;
            });
        }

        // Sort by timestamp descending
        usort($executions, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($executions, 0, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentExecutions(?string $strategyName = null, int $limit = 50): array
    {
        $allExecutions = [];

        if ($strategyName !== null) {
            // Read from specific strategy file
            $strategyFile = $this->getStrategyExecutionsFile($strategyName);
            if (file_exists($strategyFile)) {
                $allExecutions = $this->readExecutionsFromFile($strategyFile);
            }
        } else {
            // Read from all strategy files
            $files = glob($this->executionsPath . '/strategy_*.json');
            foreach ($files as $file) {
                $executions = $this->readExecutionsFromFile($file);
                $allExecutions = array_merge($allExecutions, $executions);
            }
        }

        // Sort by timestamp descending
        usort($allExecutions, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($allExecutions, 0, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function storeBacktest(string $strategyName, array $config, array $results, string $timestamp): string
    {
        $backtestId = $this->generateBacktestId($strategyName, $timestamp);
        
        $backtest = [
            'id' => $backtestId,
            'strategy' => $strategyName,
            'config' => $config,
            'results' => $results,
            'timestamp' => $timestamp,
            'stored_at' => date('Y-m-d H:i:s')
        ];

        $filename = $this->backtestsPath . '/' . $backtestId . '.json';
        file_put_contents($filename, json_encode($backtest, JSON_PRETTY_PRINT));

        // Update index
        $this->updateBacktestIndex($strategyName, $backtestId, $timestamp);

        return $backtestId;
    }

    /**
     * {@inheritdoc}
     */
    public function getBacktest(string $backtestId): ?array
    {
        $filename = $this->backtestsPath . '/' . $backtestId . '.json';
        
        if (!file_exists($filename)) {
            return null;
        }

        $content = file_get_contents($filename);
        return json_decode($content, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getBacktestsByStrategy(string $strategyName, int $limit = 20): array
    {
        $indexFile = $this->backtestsPath . '/index_' . $this->sanitizeFilename($strategyName) . '.json';
        
        if (!file_exists($indexFile)) {
            return [];
        }

        $index = json_decode(file_get_contents($indexFile), true);
        
        // Sort by timestamp descending
        usort($index, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        $backtests = [];
        $count = 0;
        
        foreach ($index as $entry) {
            if ($count >= $limit) {
                break;
            }
            
            $backtest = $this->getBacktest($entry['id']);
            if ($backtest !== null) {
                $backtests[] = $backtest;
                $count++;
            }
        }

        return $backtests;
    }

    /**
     * {@inheritdoc}
     */
    public function storePerformanceMetrics(string $strategyName, array $metrics, string $period): bool
    {
        $filename = $this->getMetricsFile($strategyName);
        
        $allMetrics = [];
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $allMetrics = json_decode($content, true) ?? [];
        }

        $allMetrics[$period] = [
            'period' => $period,
            'metrics' => $metrics,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return file_put_contents($filename, json_encode($allMetrics, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPerformanceMetrics(string $strategyName, ?string $period = null): array
    {
        $filename = $this->getMetricsFile($strategyName);
        
        if (!file_exists($filename)) {
            return [];
        }

        $allMetrics = json_decode(file_get_contents($filename), true);

        if ($period !== null) {
            return $allMetrics[$period] ?? [];
        }

        return $allMetrics;
    }

    /**
     * {@inheritdoc}
     */
    public function getStrategyStatistics(string $strategyName): array
    {
        $strategyFile = $this->getStrategyExecutionsFile($strategyName);
        
        if (!file_exists($strategyFile)) {
            return [
                'total_signals' => 0,
                'buy_signals' => 0,
                'sell_signals' => 0,
                'hold_signals' => 0,
                'avg_confidence' => 0.0
            ];
        }

        $executions = $this->readExecutionsFromFile($strategyFile);
        
        $stats = [
            'total_signals' => count($executions),
            'buy_signals' => 0,
            'sell_signals' => 0,
            'short_signals' => 0,
            'cover_signals' => 0,
            'hold_signals' => 0,
            'avg_confidence' => 0.0,
            'total_confidence' => 0.0
        ];

        foreach ($executions as $exec) {
            $signal = $exec['signal']['signal'] ?? 'HOLD';
            $confidence = $exec['signal']['confidence'] ?? 0.0;
            
            switch ($signal) {
                case 'BUY':
                    $stats['buy_signals']++;
                    break;
                case 'SELL':
                    $stats['sell_signals']++;
                    break;
                case 'SHORT':
                    $stats['short_signals']++;
                    break;
                case 'COVER':
                    $stats['cover_signals']++;
                    break;
                case 'HOLD':
                    $stats['hold_signals']++;
                    break;
            }
            
            $stats['total_confidence'] += $confidence;
        }

        if ($stats['total_signals'] > 0) {
            $stats['avg_confidence'] = $stats['total_confidence'] / $stats['total_signals'];
        }

        unset($stats['total_confidence']);

        return $stats;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOldExecutions(int $daysToKeep = 90): int
    {
        $cutoffTimestamp = strtotime("-{$daysToKeep} days");
        $deletedCount = 0;

        // Process strategy files
        $strategyFiles = glob($this->executionsPath . '/strategy_*.json');
        foreach ($strategyFiles as $file) {
            $deletedCount += $this->deleteOldExecutionsFromFile($file, $cutoffTimestamp);
        }

        // Process symbol files
        $symbolFiles = glob($this->executionsPath . '/symbol_*.json');
        foreach ($symbolFiles as $file) {
            $deletedCount += $this->deleteOldExecutionsFromFile($file, $cutoffTimestamp);
        }

        return $deletedCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableStrategies(): array
    {
        $files = glob($this->executionsPath . '/strategy_*.json');
        $strategies = [];

        foreach ($files as $file) {
            $basename = basename($file, '.json');
            $strategy = str_replace('strategy_', '', $basename);
            $strategies[] = $strategy;
        }

        return $strategies;
    }

    /**
     * Generate execution ID
     */
    private function generateExecutionId(string $strategyName, string $symbol, string $timestamp): string
    {
        return sprintf(
            '%s_%s_%s_%s',
            $strategyName,
            $symbol,
            date('YmdHis', strtotime($timestamp)),
            substr(md5(uniqid()), 0, 8)
        );
    }

    /**
     * Generate backtest ID
     */
    private function generateBacktestId(string $strategyName, string $timestamp): string
    {
        return sprintf(
            'bt_%s_%s_%s',
            $strategyName,
            date('YmdHis', strtotime($timestamp)),
            substr(md5(uniqid()), 0, 8)
        );
    }

    /**
     * Get strategy executions file path
     */
    private function getStrategyExecutionsFile(string $strategyName): string
    {
        return $this->executionsPath . '/strategy_' . $this->sanitizeFilename($strategyName) . '.json';
    }

    /**
     * Get symbol executions file path
     */
    private function getSymbolExecutionsFile(string $symbol): string
    {
        return $this->executionsPath . '/symbol_' . $this->sanitizeFilename($symbol) . '.json';
    }

    /**
     * Get metrics file path
     */
    private function getMetricsFile(string $strategyName): string
    {
        return $this->metricsPath . '/' . $this->sanitizeFilename($strategyName) . '.json';
    }

    /**
     * Append execution to file
     */
    private function appendToFile(string $filename, array $execution): void
    {
        $executions = [];
        
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $executions = json_decode($content, true) ?? [];
        }

        $executions[] = $execution;
        
        file_put_contents($filename, json_encode($executions, JSON_PRETTY_PRINT));
    }

    /**
     * Read executions from file
     */
    private function readExecutionsFromFile(string $filename): array
    {
        if (!file_exists($filename)) {
            return [];
        }

        $content = file_get_contents($filename);
        return json_decode($content, true) ?? [];
    }

    /**
     * Update backtest index
     */
    private function updateBacktestIndex(string $strategyName, string $backtestId, string $timestamp): void
    {
        $indexFile = $this->backtestsPath . '/index_' . $this->sanitizeFilename($strategyName) . '.json';
        
        $index = [];
        if (file_exists($indexFile)) {
            $index = json_decode(file_get_contents($indexFile), true) ?? [];
        }

        $index[] = [
            'id' => $backtestId,
            'timestamp' => $timestamp
        ];

        file_put_contents($indexFile, json_encode($index, JSON_PRETTY_PRINT));
    }

    /**
     * Delete old executions from a specific file
     */
    private function deleteOldExecutionsFromFile(string $filename, int $cutoffTimestamp): int
    {
        $executions = $this->readExecutionsFromFile($filename);
        $originalCount = count($executions);

        $executions = array_filter($executions, function($exec) use ($cutoffTimestamp) {
            $execTimestamp = strtotime($exec['timestamp']);
            return $execTimestamp >= $cutoffTimestamp;
        });

        $executions = array_values($executions); // Reindex
        
        file_put_contents($filename, json_encode($executions, JSON_PRETTY_PRINT));

        return $originalCount - count($executions);
    }

    /**
     * Sanitize filename
     */
    private function sanitizeFilename(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }

    /**
     * Ensure storage directories exist
     */
    private function ensureDirectoriesExist(): void
    {
        $directories = [
            $this->storagePath,
            $this->executionsPath,
            $this->backtestsPath,
            $this->metricsPath
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
}
