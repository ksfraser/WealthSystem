<?php
namespace Ksfraser\Finance\MarketFactors\Controllers;

use Ksfraser\Finance\MarketFactors\Services\MarketFactorsService;
use Ksfraser\Finance\MarketFactors\Repository\MarketFactorsRepository;
use Ksfraser\Finance\MarketFactors\Entities\MarketFactor;
use Ksfraser\Finance\MarketFactors\Entities\IndexPerformance;
use Ksfraser\Finance\MarketFactors\Entities\ForexRate;
use Ksfraser\Finance\MarketFactors\Entities\EconomicIndicator;
use Ksfraser\Finance\MarketFactors\Entities\SectorPerformance;

/**
 * Market Factors Controller
 * 
 * REST API controller for market factors management
 */
class MarketFactorsController
{
    private MarketFactorsService $service;
    private MarketFactorsRepository $repository;

    public function __construct(\PDO $pdo)
    {
        $this->repository = new MarketFactorsRepository($pdo);
        $this->service = new MarketFactorsService($this->repository);
    }

    /**
     * Get all market factors
     * GET /api/market-factors
     */
    public function getAllFactors(): array
    {
        try {
            $factors = $this->service->getAllFactors();
            
            return [
                'success' => true,
                'data' => array_map(fn($factor) => $factor->toArray(), $factors),
                'count' => count($factors)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get factors by type
     * GET /api/market-factors/{type}
     */
    public function getFactorsByType(string $type): array
    {
        try {
            $factors = $this->service->getFactorsByType($type);
            
            return [
                'success' => true,
                'data' => array_map(fn($factor) => $factor->toArray(), $factors),
                'type' => $type,
                'count' => count($factors)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get a specific factor by symbol
     * GET /api/market-factors/symbol/{symbol}
     */
    public function getFactorBySymbol(string $symbol): array
    {
        try {
            $factor = $this->service->getFactor($symbol);
            
            if (!$factor) {
                return [
                    'success' => false,
                    'error' => "Factor with symbol '$symbol' not found"
                ];
            }
            
            return [
                'success' => true,
                'data' => $factor->toArray()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Search factors with filters
     * POST /api/market-factors/search
     */
    public function searchFactors(array $filters = []): array
    {
        try {
            $factors = $this->service->searchFactors($filters);
            
            return [
                'success' => true,
                'data' => array_map(fn($factor) => $factor->toArray(), $factors),
                'filters' => $filters,
                'count' => count($factors)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get market summary
     * GET /api/market-factors/summary
     */
    public function getMarketSummary(): array
    {
        try {
            $summary = $this->service->getMarketSummary();
            
            return [
                'success' => true,
                'data' => $summary
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get factor correlations
     * GET /api/market-factors/correlations
     */
    public function getCorrelations(): array
    {
        try {
            $correlations = $this->service->getCorrelationMatrix();
            
            return [
                'success' => true,
                'data' => $correlations
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get top performers
     * GET /api/market-factors/top-performers
     */
    public function getTopPerformers(int $limit = 10): array
    {
        try {
            $topPerformers = $this->service->getTopPerformers($limit);
            
            return [
                'success' => true,
                'data' => array_map(fn($factor) => $factor->toArray(), $topPerformers),
                'limit' => $limit
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get worst performers
     * GET /api/market-factors/worst-performers
     */
    public function getWorstPerformers(int $limit = 10): array
    {
        try {
            $worstPerformers = $this->service->getWorstPerformers($limit);
            
            return [
                'success' => true,
                'data' => array_map(fn($factor) => $factor->toArray(), $worstPerformers),
                'limit' => $limit
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create or update a market factor
     * POST /api/market-factors
     */
    public function createOrUpdateFactor(array $data): array
    {
        try {
            $this->validateFactorData($data);
            
            $factor = $this->createFactorFromData($data);
            $this->service->addFactor($factor);
            
            return [
                'success' => true,
                'data' => $factor->toArray(),
                'message' => 'Factor created/updated successfully'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get market factor statistics
     * GET /api/market-factors/stats
     */
    public function getStatistics(): array
    {
        try {
            $stats = [
                'total_factors' => count($this->service->getAllFactors()),
                'by_type' => [
                    'index' => count($this->service->getFactorsByType('index')),
                    'forex' => count($this->service->getFactorsByType('forex')),
                    'economic' => count($this->service->getFactorsByType('economic')),
                    'sector' => count($this->service->getFactorsByType('sector')),
                    'sentiment' => count($this->service->getFactorsByType('sentiment')),
                    'commodity' => count($this->service->getFactorsByType('commodity'))
                ],
                'sentiment' => $this->service->calculateMarketSentiment(),
                'last_updated' => date('Y-m-d H:i:s')
            ];
            
            return [
                'success' => true,
                'data' => $stats
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate factor data
     */
    private function validateFactorData(array $data): void
    {
        $required = ['symbol', 'name', 'type', 'value'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Required field '$field' is missing or empty");
            }
        }
        
        $validTypes = ['index', 'forex', 'economic', 'sector', 'sentiment', 'commodity'];
        if (!in_array($data['type'], $validTypes)) {
            throw new \InvalidArgumentException("Invalid factor type. Must be one of: " . implode(', ', $validTypes));
        }
        
        if (!is_numeric($data['value'])) {
            throw new \InvalidArgumentException("Factor value must be numeric");
        }
    }

    /**
     * Create factor entity from data array
     */
    private function createFactorFromData(array $data): MarketFactor
    {
        $change = $data['change'] ?? 0.0;
        $changePercent = $data['change_percent'] ?? 0.0;
        $timestamp = isset($data['timestamp']) ? new \DateTime($data['timestamp']) : new \DateTime();
        $metadata = $data['metadata'] ?? [];
        
        switch ($data['type']) {
            case 'index':
                return new IndexPerformance(
                    $data['symbol'],
                    $data['name'],
                    $data['country'] ?? 'US',
                    (float)$data['value'],
                    $change,
                    $changePercent,
                    $data['asset_class'] ?? 'equity',
                    $data['constituents'] ?? 0,
                    $data['market_cap'] ?? 0,
                    $data['currency'] ?? 'USD',
                    $timestamp
                );
                
            case 'forex':
                return new ForexRate(
                    $data['base_currency'] ?? substr($data['symbol'], 0, 3),
                    $data['quote_currency'] ?? substr($data['symbol'], 3, 3),
                    (float)$data['value'],
                    $change,
                    $changePercent,
                    $data['bid_rate'] ?? 0.0,
                    $data['ask_rate'] ?? 0.0,
                    $timestamp
                );
                
            case 'economic':
                return new EconomicIndicator(
                    $data['symbol'],
                    $data['name'],
                    $data['country'] ?? 'US',
                    (float)$data['value'],
                    $data['previous_value'] ?? 0.0,
                    $data['forecast'] ?? 0.0,
                    $data['frequency'] ?? 'monthly',
                    $data['unit'] ?? '',
                    $data['importance'] ?? 'medium',
                    $data['source'] ?? '',
                    isset($data['release_date']) ? new \DateTime($data['release_date']) : null,
                    $timestamp
                );
                
            case 'sector':
                return new SectorPerformance(
                    $data['symbol'],
                    $data['name'],
                    (float)$data['value'],
                    $change,
                    $changePercent,
                    $data['top_stocks'] ?? [],
                    $data['market_cap_weight'] ?? 0.0,
                    $data['classification'] ?? 'GICS',
                    $timestamp
                );
                
            default:
                return new MarketFactor(
                    $data['symbol'],
                    $data['name'],
                    $data['type'],
                    (float)$data['value'],
                    $change,
                    $changePercent,
                    $timestamp,
                    $metadata
                );
        }
    }

    /**
     * Handle HTTP requests
     */
    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        
        // Set JSON response header
        header('Content-Type: application/json');
        
        try {
            $response = null;
            
            if ($method === 'GET' && $pathParts[2] === 'market-factors' && !isset($pathParts[3])) {
                $response = $this->getAllFactors();
            } elseif ($method === 'GET' && $pathParts[2] === 'market-factors' && $pathParts[3] === 'summary') {
                $response = $this->getMarketSummary();
            } elseif ($method === 'GET' && $pathParts[2] === 'market-factors' && $pathParts[3] === 'correlations') {
                $response = $this->getCorrelations();
            } elseif ($method === 'GET' && $pathParts[2] === 'market-factors' && $pathParts[3] === 'top-performers') {
                $response = $this->getTopPerformers();
            } elseif ($method === 'GET' && $pathParts[2] === 'market-factors' && $pathParts[3] === 'worst-performers') {
                $response = $this->getWorstPerformers();
            } elseif ($method === 'GET' && $pathParts[2] === 'market-factors' && $pathParts[3] === 'stats') {
                $response = $this->getStatistics();
            } elseif ($method === 'GET' && $pathParts[2] === 'market-factors' && $pathParts[3] === 'symbol' && isset($pathParts[4])) {
                $response = $this->getFactorBySymbol($pathParts[4]);
            } elseif ($method === 'GET' && $pathParts[2] === 'market-factors' && isset($pathParts[3])) {
                $response = $this->getFactorsByType($pathParts[3]);
            } elseif ($method === 'POST' && $pathParts[2] === 'market-factors' && $pathParts[3] === 'search') {
                $response = $this->searchFactors(json_decode(file_get_contents('php://input'), true) ?? []);
            } elseif ($method === 'POST' && $pathParts[2] === 'market-factors') {
                $response = $this->createOrUpdateFactor(json_decode(file_get_contents('php://input'), true) ?? []);
            } else {
                $response = ['success' => false, 'error' => 'Invalid endpoint or method'];
            }
            
            echo json_encode($response, JSON_PRETTY_PRINT);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_PRETTY_PRINT);
        }
    }
}
