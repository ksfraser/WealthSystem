<?php
interface IStockDataAccess
{
    public function insertPriceData($symbol, $priceData);
    public function insertTechnicalIndicator($symbol, $indicatorData);
    public function insertCandlestickPattern($symbol, $patternData);
    public function getPriceData($symbol, $startDate = null, $endDate = null, $limit = null);
    public function getTechnicalIndicators($symbol, $indicatorName = null, $startDate = null, $endDate = null);
    public function getCandlestickPatterns($symbol, $patternName = null, $startDate = null, $endDate = null);
    public function getLatestPrice($symbol);
    public function getPriceDataForAnalysis($symbol, $days = 200);
    public function getMultiSymbolData($symbols, $dataType = 'historical_prices', $startDate = null, $endDate = null);
    public function exportSymbolData($symbol, $tableTypes = null);
    public function importSymbolData($symbol, $importData);
    public function cleanupOldData($symbol, $daysToKeep = 365);
}
