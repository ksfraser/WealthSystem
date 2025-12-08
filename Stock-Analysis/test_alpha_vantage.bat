@echo off
REM Quick Test Script for Alpha Vantage API Integration
REM Tests both Fundamental Data (Sprint 14) and News Sentiment (Sprint 15)

echo ===================================================
echo Alpha Vantage API Integration - Quick Test
echo ===================================================
echo.

REM Check if API key is set
if "%ALPHA_VANTAGE_API_KEY%"=="" (
    echo [ERROR] ALPHA_VANTAGE_API_KEY not set!
    echo.
    echo Get your free API key:
    echo   1. Visit: https://www.alphavantage.co/support/#api-key
    echo   2. Enter your email and get your key instantly
    echo   3. Set in PowerShell: $env:ALPHA_VANTAGE_API_KEY='your_key_here'
    echo.
    echo Or set permanently in Windows:
    echo   setx ALPHA_VANTAGE_API_KEY "your_key_here"
    echo.
    pause
    exit /b 1
)

echo [OK] API key found: %ALPHA_VANTAGE_API_KEY:~0,8%...
echo.

echo ===================================================
echo Test 1: Fundamental Data (Sprint 14)
echo ===================================================
echo.
echo Testing AAPL fundamental data fetch...
php -r "require 'vendor/autoload.php'; use WealthSystem\StockAnalysis\Data\AlphaVantageFundamentalProvider; use WealthSystem\StockAnalysis\Data\FundamentalDataService; $provider = new AlphaVantageFundamentalProvider(getenv('ALPHA_VANTAGE_API_KEY')); $service = new FundamentalDataService([$provider]); $data = $service->getFundamentals('AAPL'); if ($data->isValid()) { echo 'SUCCESS: Got fundamentals for ' . $data->ticker . PHP_EOL; echo '  Company: ' . $data->companyName . PHP_EOL; echo '  Sector: ' . $data->sector . PHP_EOL; echo '  P/E Ratio: ' . ($data->ratios['pe_ratio'] ?? 'N/A') . PHP_EOL; echo '  ROE: ' . ($data->ratios['roe'] ?? 'N/A') . '%%' . PHP_EOL; } else { echo 'ERROR: ' . $data->error . PHP_EOL; }"
echo.

echo Waiting 13 seconds to respect rate limit (5 calls/min)...
timeout /t 13 /nobreak >nul
echo.

echo ===================================================
echo Test 2: News Sentiment (Sprint 15)
echo ===================================================
echo.
echo Testing AAPL news sentiment fetch...
php -r "require 'vendor/autoload.php'; use WealthSystem\StockAnalysis\Data\AlphaVantageNewsProvider; use WealthSystem\StockAnalysis\Data\NewsSentimentService; $provider = new AlphaVantageNewsProvider(getenv('ALPHA_VANTAGE_API_KEY')); $service = new NewsSentimentService([$provider]); $sentiment = $service->getSentiment('AAPL'); if ($sentiment->isValid()) { echo 'SUCCESS: Got sentiment for ' . $sentiment->ticker . PHP_EOL; echo '  Sentiment: ' . $sentiment->getSentimentClassification() . ' (' . $sentiment->getSentimentStrength() . ')' . PHP_EOL; echo '  Score: ' . number_format($sentiment->overallSentiment ?? 0, 3) . ' (-1.0=Bearish, +1.0=Bullish)' . PHP_EOL; echo '  Articles: ' . $sentiment->articleCount . PHP_EOL; if ($sentiment->articleCount > 0) { $recent = $sentiment->getRecentArticles(1); echo '  Latest: ' . $recent[0]['title'] . PHP_EOL; } } else { echo 'ERROR: ' . $sentiment->error . PHP_EOL; }"
echo.

echo ===================================================
echo Test Complete!
echo ===================================================
echo.
echo API calls used: 2 out of 25 daily limit
echo.
echo Next steps:
echo   1. Run full examples: php examples/fundamental_data_usage.php
echo   2. Run sentiment examples: php examples/news_sentiment_usage.php
echo   3. Read integration guides in docs/
echo.
pause
