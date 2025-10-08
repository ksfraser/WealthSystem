# CSV Handling Architecture Update

## Overview
All CSV file operations in production code now use the robust `CsvParser` class, which provides:
- Consistent error handling and logging
- Header/data validation
- Compatibility with future PHP versions

## Key Changes
- `UserPortfolioManager.php` now uses `CsvParser` for all CSV read/write operations.
- All direct `fgetcsv`/`fputcsv` usage is being refactored out of production code.
- Logging for CSV operations is written to `logs/user_portfolio_manager.log`.

## UML
@startuml
class UserPortfolioManager {
  +readUserPortfolio($userId = null)
  +writeUserPortfolio($rows, $userId = null)
  +deleteUserPortfolio($userId)
  #readUserPortfolioCsv($userId)
  #writeUserPortfolioCsv($rows, $userId)
  #getUserTableName($userId)
  #getUserCsvPath($userId)
}
UserPortfolioManager --> CsvParser
UserPortfolioManager --> FileLogger
@enduml

## Requirements
- All CSV operations must use `CsvParser` or `CsvHandler`.
- All CSV operations must be covered by unit tests.
- All public methods must have PHPDoc blocks.
- All major classes must have UML diagrams in documentation.

## Test Coverage
- See `tests/Unit/UserPortfolioManagerTest.php` for CSV operation tests.

---
Last updated: 2025-10-08
