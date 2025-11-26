<?php
// src/Services/Calculators/BatchTechnicalCalculationService.php
// Batch processor for technical indicator calculation and storage

namespace Services\Calculators;

use Ksfraser\Finance\Repositories\DatabaseRepository;

class BatchTechnicalCalculationService
{
    private $repo;

    public function __construct(DatabaseRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Process all symbols and store technical indicators in per-symbol tables
     *
     * @param array $symbols List of stock symbols
     * @param array $ohlcv   Array of OHLCV arrays keyed by symbol
     * @param array $indicators List of indicators to calculate
     * @param array $params Indicator parameters
     */
    public function processAll(array $symbols, array $ohlcv, array $indicators, array $params = [])
    {
        foreach ($symbols as $symbol) {
            $this->repo->createSymbolTechnicalTable($symbol);
            $data = $ohlcv[$symbol] ?? [];
            if (empty($data)) continue;
            $results = TALibCalculators::calculateIndicators($data, $indicators, $params);
            $dates = array_column($data, 'Date');
            foreach ($dates as $i => $date) {
                $row = ['date' => $date];
                // Map results to columns
                $row['rsi_14'] = $results['rsi'][$date] ?? null;
                $row['sma_20'] = $results['sma'][$date] ?? null;
                $row['ema_20'] = $results['ema'][$date] ?? null;
                $row['macd'] = $results['macd']['macd'][$date] ?? null;
                $row['macd_signal'] = $results['macd']['signal'][$date] ?? null;
                $row['macd_hist'] = $results['macd']['hist'][$date] ?? null;
                $row['bbands_upper'] = $results['bbands']['upper'][$date] ?? null;
                $row['bbands_middle'] = $results['bbands']['middle'][$date] ?? null;
                $row['bbands_lower'] = $results['bbands']['lower'][$date] ?? null;
                $this->repo->saveSymbolTechnicalValues($symbol, $row);
            }
        }
    }
}
