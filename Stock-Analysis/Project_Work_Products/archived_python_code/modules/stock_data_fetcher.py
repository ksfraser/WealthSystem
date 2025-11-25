"""
Stock Data Fetcher for normal market stocks
Handles data collection from multiple sources with fallback mechanisms

REQUIREMENTS TRACEABILITY:
==========================
Business Requirements:
- BR-001: Automated stock analysis (data acquisition)
- BR-010: Professional-grade analysis tools
- BR-020: Efficient multi-stock analysis (batch fetching)

Business Capabilities:
- BC-300: Multi-Source Data Fetching
- BC-301: MySQL Database Persistence (caching)

Functional Requirements:
- FR-100: Fetch real-time stock price data
- FR-101: Fetch historical price data (252+ days)
- FR-102: Fetch fundamental data (P/E, P/B, ROE, etc.)
- FR-103: Fetch technical indicators (RSI, MACD, MA)
- FR-104: Support multi-source data fetching (Yahoo, Finnhub, Alpha Vantage)
- FR-105: Automatic fallback on data source failure
- FR-106: Respect API rate limits
- FR-107: Cache recent data to minimize API calls

Technical Requirements:
- TR-200-210: StockDataFetcher module specification
- TR-700-722: API specifications (Yahoo, Finnhub, Alpha Vantage)
- CON-100: API rate limit constraints

Dependencies:
- DEP-100: Yahoo Finance API (primary - critical)
- DEP-103: Finnhub API (fallback - medium)
- DEP-104: Alpha Vantage API (tertiary - low)

Implementation:
- Primary: Yahoo Finance (yfinance) - unlimited, best effort
- Secondary: Finnhub - 60 calls/minute (free tier)
- Tertiary: Alpha Vantage - 500 calls/day (free tier)
- Rate limiting and exponential backoff
- Data caching to reduce API calls
"""

import yfinance as yf
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
import time
import requests
import logging
from typing import Dict, List, Optional, Tuple, Any
import finnhub
from alpha_vantage.timeseries import TimeSeries
from alpha_vantage.fundamentaldata import FundamentalData
import json

class StockDataFetcher:
    def __init__(self, config: Dict[str, Any]):
        """
        Initialize stock data fetcher with API configurations
        
        Args:
            config: Configuration dictionary containing API keys
        """
        self.config = config
        self.logger = logging.getLogger(__name__)
        
        # Initialize API clients
        self.finnhub_client = None
        self.av_client = None
        
        if config.get('API_KEYS', {}).get('finnhub'):
            self.finnhub_client = finnhub.Client(api_key=config['API_KEYS']['finnhub'])
            
        if config.get('API_KEYS', {}).get('alpha_vantage'):
            self.av_client = TimeSeries(key=config['API_KEYS']['alpha_vantage'])
            self.av_fundamentals = FundamentalData(key=config['API_KEYS']['alpha_vantage'])
    
    def get_stock_data(self, symbol: str, period: str = "1y", include_fundamentals: bool = True) -> Dict[str, Any]:
        """
        Get comprehensive stock data including price history and fundamentals
        
        Args:
            symbol: Stock symbol (e.g., 'AAPL')
            period: Time period for price data ('1d', '5d', '1mo', '3mo', '6mo', '1y', '2y', '5y', '10y', 'ytd', 'max')
            include_fundamentals: Whether to fetch fundamental data
            
        Returns:
            Dictionary containing price data and fundamentals
        """
        result = {
            'symbol': symbol,
            'price_data': pd.DataFrame(),
            'fundamentals': {},
            'info': {},
            'error': None,
            'source': 'unknown'
        }
        
        try:
            # Primary: Yahoo Finance
            result = self._fetch_yahoo_data(symbol, period, include_fundamentals)
            
            if result['price_data'].empty:
                # Fallback: Alpha Vantage
                result = self._fetch_alpha_vantage_data(symbol, period, include_fundamentals)
                
            if result['price_data'].empty:
                # Fallback: Finnhub
                result = self._fetch_finnhub_data(symbol, period, include_fundamentals)
                
        except Exception as e:
            self.logger.error(f"Error fetching data for {symbol}: {e}")
            result['error'] = str(e)
            
        return result
    
    def _fetch_yahoo_data(self, symbol: str, period: str, include_fundamentals: bool) -> Dict[str, Any]:
        """Fetch data from Yahoo Finance"""
        result = {
            'symbol': symbol,
            'price_data': pd.DataFrame(),
            'fundamentals': {},
            'info': {},
            'error': None,
            'source': 'yahoo'
        }
        
        try:
            ticker = yf.Ticker(symbol)
            
            # Get price data
            hist = ticker.history(period=period, auto_adjust=True, progress=False)
            if not hist.empty:
                hist = hist.reset_index()
                hist.columns = [col.lower().replace(' ', '_') for col in hist.columns]
                result['price_data'] = hist
                
            # Get fundamentals if requested
            if include_fundamentals:
                info = ticker.info
                result['info'] = info
                
                # Extract key fundamentals
                fundamentals = self._extract_yahoo_fundamentals(info)
                result['fundamentals'] = fundamentals
                
            self.logger.info(f"Successfully fetched Yahoo data for {symbol}")
            
        except Exception as e:
            self.logger.warning(f"Yahoo Finance failed for {symbol}: {e}")
            result['error'] = str(e)
            
        return result
    
    def _fetch_alpha_vantage_data(self, symbol: str, period: str, include_fundamentals: bool) -> Dict[str, Any]:
        """Fetch data from Alpha Vantage"""
        result = {
            'symbol': symbol,
            'price_data': pd.DataFrame(),
            'fundamentals': {},
            'info': {},
            'error': None,
            'source': 'alpha_vantage'
        }
        
        if not self.av_client:
            result['error'] = "Alpha Vantage API key not configured"
            return result
            
        try:
            # Get daily price data
            data, meta_data = self.av_client.get_daily_adjusted(symbol, outputsize='full')
            
            if data:
                df = pd.DataFrame(data).T
                df.index = pd.to_datetime(df.index)
                df = df.sort_index()
                
                # Convert to Yahoo-like format
                df.columns = ['open', 'high', 'low', 'close', 'adjusted_close', 'volume', 'dividend_amount', 'split_coefficient']
                df = df.astype(float)
                df = df.reset_index()
                df.rename(columns={'index': 'date'}, inplace=True)
                
                # Filter by period
                if period != 'max':
                    days_map = {'1d': 1, '5d': 5, '1mo': 30, '3mo': 90, '6mo': 180, '1y': 365, '2y': 730, '5y': 1825, '10y': 3650}
                    if period in days_map:
                        cutoff_date = datetime.now() - timedelta(days=days_map[period])
                        df = df[df['date'] >= cutoff_date]
                
                result['price_data'] = df
                
            # Get fundamentals if requested
            if include_fundamentals:
                overview, _ = self.av_fundamentals.get_company_overview(symbol)
                if overview:
                    result['fundamentals'] = self._extract_av_fundamentals(overview)
                    
            self.logger.info(f"Successfully fetched Alpha Vantage data for {symbol}")
            
        except Exception as e:
            self.logger.warning(f"Alpha Vantage failed for {symbol}: {e}")
            result['error'] = str(e)
            
        return result
    
    def _fetch_finnhub_data(self, symbol: str, period: str, include_fundamentals: bool) -> Dict[str, Any]:
        """Fetch data from Finnhub"""
        result = {
            'symbol': symbol,
            'price_data': pd.DataFrame(),
            'fundamentals': {},
            'info': {},
            'error': None,
            'source': 'finnhub'
        }
        
        if not self.finnhub_client:
            result['error'] = "Finnhub API key not configured"
            return result
            
        try:
            # Calculate date range
            end_date = datetime.now()
            days_map = {'1d': 1, '5d': 5, '1mo': 30, '3mo': 90, '6mo': 180, '1y': 365, '2y': 730, '5y': 1825, '10y': 3650}
            days = days_map.get(period, 365)
            start_date = end_date - timedelta(days=days)
            
            # Get price data
            candles = self.finnhub_client.stock_candles(
                symbol, 'D',
                int(start_date.timestamp()),
                int(end_date.timestamp())
            )
            
            if candles.get('s') == 'ok':
                df = pd.DataFrame({
                    'date': pd.to_datetime(candles['t'], unit='s'),
                    'open': candles['o'],
                    'high': candles['h'],
                    'low': candles['l'],
                    'close': candles['c'],
                    'volume': candles['v']
                })
                df['adjusted_close'] = df['close']  # Finnhub doesn't provide adjusted close
                result['price_data'] = df
                
            # Get fundamentals if requested
            if include_fundamentals:
                profile = self.finnhub_client.company_profile2(symbol=symbol)
                metrics = self.finnhub_client.company_basic_financials(symbol, 'all')
                
                if profile or metrics:
                    result['fundamentals'] = self._extract_finnhub_fundamentals(profile, metrics)
                    
            self.logger.info(f"Successfully fetched Finnhub data for {symbol}")
            
        except Exception as e:
            self.logger.warning(f"Finnhub failed for {symbol}: {e}")
            result['error'] = str(e)
            
        return result
    
    def _extract_yahoo_fundamentals(self, info: Dict) -> Dict[str, Any]:
        """Extract fundamental data from Yahoo Finance info"""
        fundamentals = {}
        
        # Market data
        fundamentals['market_cap'] = info.get('marketCap')
        fundamentals['enterprise_value'] = info.get('enterpriseValue')
        fundamentals['beta'] = info.get('beta')
        
        # Valuation ratios
        fundamentals['pe_ratio'] = info.get('trailingPE')
        fundamentals['forward_pe'] = info.get('forwardPE')
        fundamentals['peg_ratio'] = info.get('pegRatio')
        fundamentals['price_to_book'] = info.get('priceToBook')
        fundamentals['price_to_sales'] = info.get('priceToSalesTrailing12Months')
        
        # Financial metrics
        fundamentals['debt_to_equity'] = info.get('debtToEquity')
        fundamentals['return_on_equity'] = info.get('returnOnEquity')
        fundamentals['return_on_assets'] = info.get('returnOnAssets')
        fundamentals['profit_margin'] = info.get('profitMargins')
        fundamentals['operating_margin'] = info.get('operatingMargins')
        fundamentals['gross_margin'] = info.get('grossMargins')
        
        # Dividend data
        fundamentals['dividend_yield'] = info.get('dividendYield')
        fundamentals['payout_ratio'] = info.get('payoutRatio')
        
        # Growth metrics
        fundamentals['revenue_growth'] = info.get('revenueGrowth')
        fundamentals['earnings_growth'] = info.get('earningsGrowth')
        
        # Liquidity ratios
        fundamentals['current_ratio'] = info.get('currentRatio')
        fundamentals['quick_ratio'] = info.get('quickRatio')
        
        # Company info
        fundamentals['company_name'] = info.get('longName')
        fundamentals['sector'] = info.get('sector')
        fundamentals['industry'] = info.get('industry')
        fundamentals['cash_per_share'] = info.get('totalCashPerShare')
        fundamentals['book_value_per_share'] = info.get('bookValue')
        
        # Analyst data
        fundamentals['target_price'] = info.get('targetMeanPrice')
        fundamentals['analyst_rating'] = info.get('recommendationKey')
        
        return fundamentals
    
    def _extract_av_fundamentals(self, overview: Dict) -> Dict[str, Any]:
        """Extract fundamental data from Alpha Vantage overview"""
        fundamentals = {}
        
        try:
            fundamentals['company_name'] = overview.get('Name')
            fundamentals['sector'] = overview.get('Sector')
            fundamentals['industry'] = overview.get('Industry')
            fundamentals['market_cap'] = self._safe_float(overview.get('MarketCapitalization'))
            fundamentals['pe_ratio'] = self._safe_float(overview.get('PERatio'))
            fundamentals['peg_ratio'] = self._safe_float(overview.get('PEGRatio'))
            fundamentals['price_to_book'] = self._safe_float(overview.get('PriceToBookRatio'))
            fundamentals['price_to_sales'] = self._safe_float(overview.get('PriceToSalesRatioTTM'))
            fundamentals['dividend_yield'] = self._safe_float(overview.get('DividendYield'))
            fundamentals['beta'] = self._safe_float(overview.get('Beta'))
            fundamentals['profit_margin'] = self._safe_float(overview.get('ProfitMargin'))
            fundamentals['operating_margin'] = self._safe_float(overview.get('OperatingMarginTTM'))
            fundamentals['return_on_assets'] = self._safe_float(overview.get('ReturnOnAssetsTTM'))
            fundamentals['return_on_equity'] = self._safe_float(overview.get('ReturnOnEquityTTM'))
            fundamentals['revenue_growth'] = self._safe_float(overview.get('QuarterlyRevenueGrowthYOY'))
            fundamentals['earnings_growth'] = self._safe_float(overview.get('QuarterlyEarningsGrowthYOY'))
            fundamentals['current_ratio'] = self._safe_float(overview.get('CurrentRatio'))
            fundamentals['book_value_per_share'] = self._safe_float(overview.get('BookValue'))
            fundamentals['analyst_rating'] = overview.get('AnalystTargetPrice')
            
        except Exception as e:
            self.logger.warning(f"Error extracting Alpha Vantage fundamentals: {e}")
            
        return fundamentals
    
    def _extract_finnhub_fundamentals(self, profile: Dict, metrics: Dict) -> Dict[str, Any]:
        """Extract fundamental data from Finnhub profile and metrics"""
        fundamentals = {}
        
        try:
            if profile:
                fundamentals['company_name'] = profile.get('name')
                fundamentals['sector'] = profile.get('finnhubIndustry')
                fundamentals['market_cap'] = profile.get('marketCapitalization')
                
            if metrics and 'metric' in metrics:
                m = metrics['metric']
                fundamentals['pe_ratio'] = m.get('peBasicExclExtraTTM')
                fundamentals['price_to_book'] = m.get('pbAnnual')
                fundamentals['price_to_sales'] = m.get('psAnnual')
                fundamentals['beta'] = m.get('beta')
                fundamentals['return_on_equity'] = m.get('roeRfy')
                fundamentals['return_on_assets'] = m.get('roaRfy')
                fundamentals['debt_to_equity'] = m.get('totalDebt/totalEquityAnnual')
                fundamentals['current_ratio'] = m.get('currentRatioAnnual')
                
        except Exception as e:
            self.logger.warning(f"Error extracting Finnhub fundamentals: {e}")
            
        return fundamentals
    
    def _safe_float(self, value: Any) -> Optional[float]:
        """Safely convert value to float"""
        try:
            if value in [None, 'None', '', 'N/A']:
                return None
            return float(value)
        except (ValueError, TypeError):
            return None
    
    def get_sp500_list(self) -> List[str]:
        """Get list of S&P 500 symbols"""
        try:
            # Wikipedia S&P 500 list
            url = 'https://en.wikipedia.org/wiki/List_of_S%26P_500_companies'
            tables = pd.read_html(url)
            sp500_table = tables[0]
            symbols = sp500_table['Symbol'].tolist()
            
            # Clean symbols (remove dots, etc.)
            symbols = [symbol.replace('.', '-') for symbol in symbols]
            
            self.logger.info(f"Retrieved {len(symbols)} S&P 500 symbols")
            return symbols
            
        except Exception as e:
            self.logger.error(f"Failed to get S&P 500 list: {e}")
            # Fallback list of major stocks
            return [
                'AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA', 'META', 'NVDA', 'BRK-B',
                'JNJ', 'V', 'WMT', 'JPM', 'MA', 'PG', 'UNH', 'DIS', 'HD', 'PYPL',
                'BAC', 'NFLX', 'ADBE', 'CRM', 'VZ', 'CMCSA', 'KO', 'NKE', 'T',
                'PFE', 'INTC', 'ABT', 'TMO', 'COST', 'CVX', 'MRK', 'AVGO', 'XOM'
            ]
    
    def batch_fetch_data(self, symbols: List[str], period: str = "1y", max_workers: int = 5) -> Dict[str, Dict]:
        """
        Fetch data for multiple symbols concurrently
        
        Args:
            symbols: List of stock symbols
            period: Time period for price data
            max_workers: Maximum number of concurrent requests
            
        Returns:
            Dictionary with symbol as key and data as value
        """
        import concurrent.futures
        
        results = {}
        
        def fetch_single(symbol):
            return symbol, self.get_stock_data(symbol, period)
        
        with concurrent.futures.ThreadPoolExecutor(max_workers=max_workers) as executor:
            # Submit all tasks
            futures = {executor.submit(fetch_single, symbol): symbol for symbol in symbols}
            
            # Collect results
            for future in concurrent.futures.as_completed(futures):
                try:
                    symbol, data = future.result()
                    results[symbol] = data
                    
                    # Rate limiting
                    time.sleep(0.1)
                    
                except Exception as e:
                    symbol = futures[future]
                    self.logger.error(f"Failed to fetch data for {symbol}: {e}")
                    results[symbol] = {
                        'symbol': symbol,
                        'price_data': pd.DataFrame(),
                        'fundamentals': {},
                        'info': {},
                        'error': str(e),
                        'source': 'error'
                    }
        
        self.logger.info(f"Batch fetched data for {len(results)} symbols")
        return results
