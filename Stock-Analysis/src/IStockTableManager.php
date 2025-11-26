<?php
interface IStockTableManager
{
    public function registerSymbol($symbol, $companyData = []);
    public function createTablesForSymbol($symbol);
    public function getTableName($symbol, $tableType);
    public function tablesExistForSymbol($symbol);
    public function getAllSymbols($activeOnly = true);
    public function removeTablesForSymbol($symbol, $confirm = false);
    public function deactivateSymbol($symbol);
    public function getSymbolTableStats($symbol);
}
