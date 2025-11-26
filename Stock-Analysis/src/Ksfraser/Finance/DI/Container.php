<?php
/**
 * Dependency Injection Container
 * 
 * Simple DI container for managing service dependencies.
 * Follows the Dependency Inversion Principle by centralizing object creation.
 */

namespace Ksfraser\Finance\DI;

use Ksfraser\Finance\DataSources\AlphaVantageDataSource;
use Ksfraser\Finance\DataSources\YahooFinanceDataSource;
use Ksfraser\Finance\Repositories\DatabaseRepository;
use Ksfraser\Finance\LLM\OpenAIProvider;
use Ksfraser\Finance\Services\StockDataService;
use Ksfraser\Finance\Controllers\StockController;
use PDO;

class Container
{
    private $services = [];
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get a service instance (singleton pattern)
     * 
     * @param string $service Service identifier
     * @return mixed Service instance
     * @throws \InvalidArgumentException If service not found
     */
    public function get(string $service)
    {
        if (!isset($this->services[$service])) {
            $this->services[$service] = $this->create($service);
        }
        return $this->services[$service];
    }

    /**
     * Check if a service is registered
     * 
     * @param string $service Service identifier
     * @return bool True if service exists
     */
    public function has(string $service): bool
    {
        $registeredServices = [
            'database', 'repository', 'alphavantage_source', 'yahoo_source',
            'llm_provider', 'stock_service', 'stock_controller'
        ];
        
        return in_array($service, $registeredServices);
    }

    /**
     * Register a service instance
     * 
     * @param string $service Service identifier
     * @param mixed $instance Service instance
     */
    public function set(string $service, $instance): void
    {
        $this->services[$service] = $instance;
    }

    /**
     * Create service instances based on configuration
     * 
     * @param string $service Service identifier
     * @return mixed Service instance
     * @throws \InvalidArgumentException If service not found
     */
    private function create(string $service)
    {
        switch ($service) {
            case 'database':
                return $this->createDatabase();

            case 'repository':
                return new DatabaseRepository($this->get('database'));

            case 'alphavantage_source':
                return $this->createAlphaVantageSource();

            case 'yahoo_source':
                return new YahooFinanceDataSource();

            case 'llm_provider':
                return $this->createLLMProvider();

            case 'stock_service':
                return $this->createStockService();

            case 'stock_controller':
                return new StockController($this->get('stock_service'));

            default:
                throw new \InvalidArgumentException("Service '{$service}' not found in container");
        }
    }

    /**
     * Create database connection
     */
    private function createDatabase(): PDO
    {
        $dbConfig = $this->config['database'] ?? [];
        
        if (empty($dbConfig['dsn'])) {
            throw new \InvalidArgumentException('Database DSN not configured');
        }

        try {
            $pdo = new PDO(
                $dbConfig['dsn'],
                $dbConfig['username'] ?? '',
                $dbConfig['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            
            // Test connection
            $pdo->query('SELECT 1');
            
            return $pdo;
        } catch (\PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Create Alpha Vantage data source
     */
    private function createAlphaVantageSource(): AlphaVantageDataSource
    {
        $apiKey = $this->config['alphavantage']['api_key'] ?? '';
        
        if (empty($apiKey)) {
            error_log('Alpha Vantage API key not configured - source will be unavailable');
        }
        
        return new AlphaVantageDataSource($apiKey);
    }

    /**
     * Create LLM provider
     */
    private function createLLMProvider(): ?OpenAIProvider
    {
        $apiKey = $this->config['openai']['api_key'] ?? '';
        
        if (empty($apiKey)) {
            error_log('OpenAI API key not configured - LLM analysis will be unavailable');
            return null;
        }
        
        return new OpenAIProvider($apiKey);
    }

    /**
     * Create stock data service with all dependencies
     */
    private function createStockService(): StockDataService
    {
        $dataSources = [];
        
        // Add Yahoo Finance (free, always available)
        $dataSources[] = $this->get('yahoo_source');
        
        // Add Alpha Vantage if configured
        $alphaVantage = $this->get('alphavantage_source');
        if ($alphaVantage->isAvailable()) {
            $dataSources[] = $alphaVantage;
        }

        $serviceConfig = [
            'rate_limit_delay' => $this->config['rate_limiting']['delay_between_requests'] ?? 500000,
            'max_retries' => $this->config['general']['max_retries'] ?? 3,
            'timeout' => $this->config['general']['timeout'] ?? 30
        ];

        return new StockDataService(
            $dataSources,
            $this->get('repository'),
            $this->get('llm_provider'),
            $serviceConfig
        );
    }

    /**
     * Get configuration value
     * 
     * @param string $key Dot-notation key (e.g., 'database.dsn')
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public function getConfig(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    /**
     * Get all registered services (useful for debugging)
     * 
     * @return array List of service names
     */
    public function getRegisteredServices(): array
    {
        return array_keys($this->services);
    }
}
