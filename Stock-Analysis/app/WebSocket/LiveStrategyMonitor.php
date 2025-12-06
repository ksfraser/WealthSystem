<?php

declare(strict_types=1);

namespace App\WebSocket;

use App\Logging\TradingLogger;

/**
 * Live Strategy Monitor
 * 
 * Monitors trading strategies in real-time using WebSocket price feeds.
 * 
 * @package App\WebSocket
 */
class LiveStrategyMonitor
{
    private PriceStreamClient $client;
    private array $strategies = [];
    private array $latestPrices = [];
    private ?TradingLogger $logger;
    
    public function __construct(
        PriceStreamClient $client,
        ?TradingLogger $logger = null
    ) {
        $this->client = $client;
        $this->logger = $logger;
    }
    
    /**
     * Add strategy to monitor
     */
    public function addStrategy(string $symbol, callable $strategyCallback): void
    {
        $this->strategies[$symbol] = $strategyCallback;
        
        // Subscribe to price updates
        $this->client->subscribe($symbol, function($priceData) use ($symbol) {
            $this->onPriceUpdate($symbol, $priceData);
        });
        
        if ($this->logger) {
            $this->logger->info('Strategy added to monitor', [
                'symbol' => $symbol
            ]);
        }
    }
    
    /**
     * Remove strategy from monitoring
     */
    public function removeStrategy(string $symbol): void
    {
        unset($this->strategies[$symbol]);
        $this->client->unsubscribe($symbol);
        
        if ($this->logger) {
            $this->logger->info('Strategy removed from monitor', [
                'symbol' => $symbol
            ]);
        }
    }
    
    /**
     * Handle price update
     */
    private function onPriceUpdate(string $symbol, array $priceData): void
    {
        $this->latestPrices[$symbol] = $priceData;
        
        if (isset($this->strategies[$symbol])) {
            $signal = call_user_func($this->strategies[$symbol], $priceData);
            
            if ($signal && $signal['action'] !== 'NONE') {
                $this->onSignalGenerated($symbol, $signal);
            }
        }
    }
    
    /**
     * Handle signal generation
     */
    private function onSignalGenerated(string $symbol, array $signal): void
    {
        if ($this->logger) {
            $this->logger->logStrategyExecution(
                'LiveMonitor',
                $symbol,
                $signal
            );
        }
        
        // Send signal to server
        $this->client->send([
            'type' => 'signal',
            'symbol' => $symbol,
            'signal' => $signal,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Get latest price for symbol
     */
    public function getLatestPrice(string $symbol): ?array
    {
        return $this->latestPrices[$symbol] ?? null;
    }
    
    /**
     * Get all monitored symbols
     */
    public function getMonitoredSymbols(): array
    {
        return array_keys($this->strategies);
    }
    
    /**
     * Get strategy count
     */
    public function getStrategyCount(): int
    {
        return count($this->strategies);
    }
}
