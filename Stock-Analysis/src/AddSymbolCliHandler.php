<?php
require_once __DIR__ . '/AddSymbolAction.php';
require_once __DIR__ . '/IStockTableManager.php';

/**
 * CLI Handler for adding individual stock symbols.
 * Follows SRP by focusing solely on CLI argument parsing and delegation.
 */
class AddSymbolCliHandler
{
    private $addSymbolAction;

    /**
     * Constructor with dependency injection.
     *
     * @param AddSymbolAction $addSymbolAction The action to handle symbol addition
     */
    public function __construct(AddSymbolAction $addSymbolAction)
    {
        $this->addSymbolAction = $addSymbolAction;
    }

    /**
     * Run the CLI command for adding a symbol.
     *
     * @param array $argv Command line arguments
     * @return int Exit code (0 for success, 1 for failure)
     */
    public function run($argv)
    {
        try {
            $params = $this->parseArguments($argv);
            $result = $this->addSymbolAction->execute($params['symbol'], $params['options']);
            $this->displayResult($result, $params['symbol']);
            return 0;
        } catch (InvalidArgumentException $e) {
            echo "Error: " . $e->getMessage() . "\n";
            $this->showUsage();
            return 1;
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Parse command line arguments.
     *
     * @param array $argv Command line arguments
     * @return array Parsed parameters
     * @throws InvalidArgumentException If arguments are invalid
     */
    private function parseArguments($argv)
    {
        if (count($argv) < 2) {
            throw new InvalidArgumentException("Symbol required");
        }

        $symbol = strtoupper(trim($argv[1]));
        $options = [
            'company_name' => $this->getOptionValue($argv, '--company', ''),
            'sector' => $this->getOptionValue($argv, '--sector', ''),
            'industry' => $this->getOptionValue($argv, '--industry', ''),
            'market_cap' => $this->getOptionValue($argv, '--market-cap', 'micro'),
            'active' => !in_array('--inactive', $argv)
        ];

        return [
            'symbol' => $symbol,
            'options' => $options
        ];
    }

    /**
     * Get option value from command line arguments.
     *
     * @param array $argv Command line arguments
     * @param string $option Option name
     * @param string $default Default value
     * @return string Option value
     */
    private function getOptionValue($argv, $option, $default = '')
    {
        foreach ($argv as $arg) {
            if (strpos($arg, $option . '=') === 0) {
                return substr($arg, strlen($option) + 1);
            }
        }
        return $default;
    }

    /**
     * Display the result of the symbol addition.
     *
     * @param array $result Result from AddSymbolAction
     * @param string $symbol The symbol that was processed
     */
    private function displayResult($result, $symbol)
    {
        if ($result['status'] === 'created') {
            echo "✓ Symbol {$symbol} registered and tables created.\n";
        } else {
            echo "→ Symbol {$symbol} already exists. Tables checked.\n";
        }

        if (!empty($result['tables_created'])) {
            echo "Tables created: " . implode(', ', $result['tables_created']) . "\n";
        }
    }

    /**
     * Show usage information.
     */
    private function showUsage()
    {
        echo "Add Symbol Tool\n\n";
        echo "Usage:\n";
        echo "  php AddNewSymbol.php SYMBOL [OPTIONS]\n\n";
        echo "Options:\n";
        echo "  --company=NAME       Company name\n";
        echo "  --sector=SECTOR      Business sector\n";
        echo "  --industry=INDUSTRY  Industry classification\n";
        echo "  --market-cap=SIZE    Market cap size (micro, small, mid, large)\n";
        echo "  --inactive           Create symbol as inactive\n\n";
        echo "Examples:\n";
        echo "  php AddNewSymbol.php IBM\n";
        echo "  php AddNewSymbol.php AAPL --company=\"Apple Inc.\" --sector=\"Technology\"\n";
    }
}
