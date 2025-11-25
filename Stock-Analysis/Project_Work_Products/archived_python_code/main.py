"""
Main Application for Stock Analysis Extension
Entry point for the stock analysis and portfolio management system

REQUIREMENTS TRACEABILITY:
==========================
Business Requirements:
- BR-001: Automated stock analysis
- BR-002: Data-driven recommendations  
- BR-003: Portfolio management with risk controls
- BR-010: Professional-grade analysis tools
- BR-011: Clear buy/sell/hold recommendations
- BR-012: Portfolio performance tracking
- BR-020: Efficient multi-stock analysis

Functional Requirements:
- FR-1500-1505: User Interface Functions (interactive menu, reports)
- FR-1600-1603: Reporting Functions (analysis reports, export)
- FR-1700-1703: Configuration Functions (load, validate config)
- FR-1800-1804: Logging and Error Handling
- FR-1900-1903: Automation Functions (daily analysis, updates)

Technical Requirements:
- TR-100-103: System Architecture (MVC pattern, modular design)
- TR-800: Performance requirement (< 30 sec analysis)
- TR-900-905: Security requirements (config management, validation)
- TR-1000-1005: Deployment requirements
- TR-1200-1204: Code quality requirements

Implementation Details:
- Application Controller: Coordinates all modules
- Interactive Menu: User-facing interface
- Configuration Management: Load and validate settings
- Logging Setup: Comprehensive error tracking
- Daily Analysis: Automated portfolio updates
"""

import sys
import os
import logging
from pathlib import Path
from datetime import datetime
import pandas as pd
from typing import Dict, List, Any, Optional

# Add the modules directory to the path
sys.path.append(str(Path(__file__).parent / "modules"))

# Import modules
from modules.database_manager import DatabaseManager
from modules.stock_data_fetcher import StockDataFetcher
from modules.stock_analyzer import StockAnalyzer
from modules.portfolio_manager import PortfolioManager
from modules.front_accounting import FrontAccountingIntegrator

class StockAnalysisApp:
    def __init__(self, config_path: str = None):
        """
        Initialize the Stock Analysis Application
        
        Args:
            config_path: Path to configuration file
        """
        self.config = self._load_config(config_path)
        self.setup_logging()
        
        self.logger = logging.getLogger(__name__)
        self.portfolio_manager = PortfolioManager(self.config)
        
        # Components (will be initialized later)
        self.db_manager = None
        self.data_fetcher = None
        self.analyzer = None
        self.fa_integrator = None
        
    def _load_config(self, config_path: str = None) -> Dict[str, Any]:
        """Load configuration from file or use defaults"""
        try:
            if config_path and os.path.exists(config_path):
                # Import config from file
                import importlib.util
                spec = importlib.util.spec_from_file_location("config", config_path)
                config_module = importlib.util.module_from_spec(spec)
                spec.loader.exec_module(config_module)
                
                return {
                    'DATABASE_CONFIG': config_module.DATABASE_CONFIG,
                    'FRONTACCOUNTING_CONFIG': config_module.FRONTACCOUNTING_CONFIG,
                    'API_KEYS': config_module.API_KEYS,
                    'ANALYSIS_CONFIG': config_module.ANALYSIS_CONFIG,
                    'RISK_CONFIG': config_module.RISK_CONFIG,
                    'SCORING_WEIGHTS': config_module.SCORING_WEIGHTS
                }
            else:
                # Use default configuration
                return self._get_default_config()
                
        except Exception as e:
            print(f"Error loading config: {e}")
            return self._get_default_config()
    
    def _get_default_config(self) -> Dict[str, Any]:
        """Get default configuration"""
        return {
            'DATABASE_CONFIG': {
                'host': 'localhost',
                'port': 3306,
                'database': 'stock_analysis',
                'user': 'root',
                'password': 'password',
                'charset': 'utf8mb4',
                'autocommit': True
            },
            'FRONTACCOUNTING_CONFIG': {
                'api_url': 'http://localhost/frontaccounting/api',
                'username': 'admin',
                'password': 'password',
                'company_id': 1,
                'fiscal_year': 2025
            },
            'API_KEYS': {
                'finnhub': '',
                'alpha_vantage': '',
                'fmp': ''
            },
            'ANALYSIS_CONFIG': {
                'lookback_period_days': 252,
                'min_volume': 100000,
                'min_market_cap': 1000000000,
                'max_pe_ratio': 50,
                'min_dividend_yield': 0.0
            },
            'RISK_CONFIG': {
                'max_position_size': 0.05,
                'stop_loss_pct': 0.15,
                'take_profit_pct': 0.25,
                'max_correlation': 0.7,
                'max_sector_exposure': 0.25
            },
            'SCORING_WEIGHTS': {
                'fundamental': 0.40,
                'technical': 0.30,
                'momentum': 0.20,
                'sentiment': 0.10
            }
        }
    
    def setup_logging(self):
        """Setup logging configuration"""
        log_dir = Path(__file__).parent / "logs"
        log_dir.mkdir(exist_ok=True)
        
        log_file = log_dir / f"stock_analysis_{datetime.now().strftime('%Y%m%d')}.log"
        
        logging.basicConfig(
            level=logging.INFO,
            format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
            handlers=[
                logging.FileHandler(log_file),
                logging.StreamHandler(sys.stdout)
            ]
        )
    
    def initialize(self) -> bool:
        """
        Initialize all system components
        
        Returns:
            Boolean indicating successful initialization
        """
        try:
            self.logger.info("Initializing Stock Analysis System...")
            
            # Initialize portfolio manager and check system status
            init_results = self.portfolio_manager.initialize_system()
            
            if init_results['database_connection']:
                self.logger.info("✓ Database connection established")
            else:
                self.logger.error("✗ Database connection failed")
                return False
            
            if init_results['frontaccounting_connection']:
                self.logger.info("✓ FrontAccounting connection established")
            else:
                self.logger.warning("✗ FrontAccounting connection failed (will continue without FA integration)")
            
            if init_results['data_sources']:
                self.logger.info(f"✓ Data sources available: {', '.join(set(init_results['data_sources']))}")
            else:
                self.logger.warning("✗ No data sources available")
            
            # Initialize individual components for direct access
            self.db_manager = self.portfolio_manager.db_manager
            self.data_fetcher = self.portfolio_manager.data_fetcher
            self.analyzer = self.portfolio_manager.analyzer
            self.fa_integrator = self.portfolio_manager.fa_integrator
            
            self.logger.info("Stock Analysis System initialized successfully!")
            return True
            
        except Exception as e:
            self.logger.error(f"Failed to initialize system: {e}")
            return False
    
    def analyze_stock(self, symbol: str) -> Dict[str, Any]:
        """
        Analyze a single stock
        
        Args:
            symbol: Stock symbol to analyze
            
        Returns:
            Dictionary containing analysis results
        """
        try:
            self.logger.info(f"Analyzing stock: {symbol}")
            result = self.portfolio_manager.analyze_portfolio_stock(symbol)
            
            if result['error']:
                self.logger.error(f"Error analyzing {symbol}: {result['error']}")
            else:
                analysis = result['analysis']
                self.logger.info(f"{symbol} Analysis Complete - Score: {analysis['overall_score']:.1f}, Recommendation: {analysis['recommendation']}")
            
            return result
            
        except Exception as e:
            self.logger.error(f"Error in analyze_stock: {e}")
            return {'symbol': symbol, 'error': str(e), 'analysis': None}
    
    def get_recommendations(self, max_count: int = 10) -> List[Dict[str, Any]]:
        """
        Get stock recommendations
        
        Args:
            max_count: Maximum number of recommendations
            
        Returns:
            List of stock recommendations
        """
        try:
            self.logger.info(f"Getting top {max_count} stock recommendations...")
            recommendations = self.portfolio_manager.get_portfolio_recommendations(max_count)
            
            self.logger.info(f"Found {len(recommendations)} recommendations")
            return recommendations
            
        except Exception as e:
            self.logger.error(f"Error getting recommendations: {e}")
            return []
    
    def analyze_existing_portfolio(self, portfolio_symbols: List[str]) -> Dict[str, Any]:
        """
        Analyze an existing portfolio of stocks
        
        Args:
            portfolio_symbols: List of stock symbols in portfolio
            
        Returns:
            Dictionary containing portfolio analysis
        """
        try:
            self.logger.info(f"Analyzing existing portfolio with {len(portfolio_symbols)} stocks")
            
            portfolio_analysis = {
                'total_stocks': len(portfolio_symbols),
                'analysis_date': datetime.now().date(),
                'stocks': {},
                'summary': {
                    'avg_score': 0,
                    'buy_recommendations': 0,
                    'hold_recommendations': 0,
                    'sell_recommendations': 0,
                    'high_risk_stocks': 0,
                    'sectors': {}
                }
            }
            
            scores = []
            
            for symbol in portfolio_symbols:
                result = self.analyze_stock(symbol)
                portfolio_analysis['stocks'][symbol] = result
                
                if result['analysis'] and not result['error']:
                    analysis = result['analysis']
                    scores.append(analysis['overall_score'])
                    
                    # Count recommendations
                    if analysis['recommendation'] in ['BUY', 'STRONG_BUY']:
                        portfolio_analysis['summary']['buy_recommendations'] += 1
                    elif analysis['recommendation'] == 'HOLD':
                        portfolio_analysis['summary']['hold_recommendations'] += 1
                    else:
                        portfolio_analysis['summary']['sell_recommendations'] += 1
                    
                    # Count high risk stocks
                    if analysis['risk_rating'] in ['HIGH', 'VERY_HIGH']:
                        portfolio_analysis['summary']['high_risk_stocks'] += 1
                    
                    # Count sectors
                    if result['stock_data'] and result['stock_data']['fundamentals']:
                        sector = result['stock_data']['fundamentals'].get('sector', 'Unknown')
                        portfolio_analysis['summary']['sectors'][sector] = portfolio_analysis['summary']['sectors'].get(sector, 0) + 1
            
            # Calculate average score
            if scores:
                portfolio_analysis['summary']['avg_score'] = round(sum(scores) / len(scores), 2)
            
            self.logger.info(f"Portfolio analysis complete - Average score: {portfolio_analysis['summary']['avg_score']}")
            return portfolio_analysis
            
        except Exception as e:
            self.logger.error(f"Error analyzing existing portfolio: {e}")
            return {'error': str(e)}
    
    def execute_trade(self, symbol: str, trade_type: str, quantity: float, price: float, **kwargs) -> Dict[str, Any]:
        """
        Execute a trade
        
        Args:
            symbol: Stock symbol
            trade_type: 'BUY' or 'SELL'
            quantity: Number of shares
            price: Price per share
            **kwargs: Additional trade parameters
            
        Returns:
            Dictionary containing trade execution results
        """
        try:
            trade_data = {
                'symbol': symbol.upper(),
                'trade_type': trade_type.upper(),
                'quantity': quantity,
                'price': price,
                'trade_date': kwargs.get('trade_date', datetime.now()),
                'strategy': kwargs.get('strategy', 'Manual'),
                'notes': kwargs.get('notes', '')
            }
            
            self.logger.info(f"Executing trade: {trade_type} {quantity} shares of {symbol} at ${price:.2f}")
            
            result = self.portfolio_manager.execute_trade(trade_data)
            
            if result['status'] == 'success':
                self.logger.info(f"Trade executed successfully: {result['message']}")
            else:
                self.logger.error(f"Trade execution failed: {result['message']}")
            
            return result
            
        except Exception as e:
            self.logger.error(f"Error executing trade: {e}")
            return {
                'status': 'error',
                'message': str(e),
                'trade_id': None
            }
    
    def get_portfolio_summary(self) -> Dict[str, Any]:
        """
        Get portfolio summary
        
        Returns:
            Dictionary containing portfolio summary
        """
        try:
            return self.portfolio_manager.get_portfolio_summary()
            
        except Exception as e:
            self.logger.error(f"Error getting portfolio summary: {e}")
            return {'status': 'error', 'message': str(e)}
    
    def run_daily_analysis(self) -> Dict[str, Any]:
        """
        Run daily portfolio analysis
        
        Returns:
            Dictionary containing daily analysis results
        """
        try:
            self.logger.info("Running daily portfolio analysis...")
            return self.portfolio_manager.run_daily_analysis()
            
        except Exception as e:
            self.logger.error(f"Error in daily analysis: {e}")
            return {'date': datetime.now().date(), 'error': str(e)}
    
    def print_analysis_report(self, analysis_result: Dict[str, Any]):
        """
        Print a formatted analysis report
        
        Args:
            analysis_result: Analysis result from analyze_stock
        """
        try:
            if analysis_result['error']:
                print(f"\n❌ Error analyzing {analysis_result['symbol']}: {analysis_result['error']}")
                return
            
            analysis = analysis_result['analysis']
            stock_data = analysis_result['stock_data']
            
            print(f"\n{'='*60}")
            print(f"STOCK ANALYSIS REPORT: {analysis['symbol']}")
            print(f"{'='*60}")
            
            # Company info
            if stock_data['fundamentals']:
                company_name = stock_data['fundamentals'].get('company_name', analysis['symbol'])
                sector = stock_data['fundamentals'].get('sector', 'Unknown')
                print(f"Company: {company_name}")
                print(f"Sector: {sector}")
            
            # Current price
            if not stock_data['price_data'].empty:
                current_price = stock_data['price_data']['close'].iloc[-1]
                print(f"Current Price: ${current_price:.2f}")
            
            print(f"\nAnalysis Date: {analysis['analysis_date']}")
            print(f"Overall Score: {analysis['overall_score']:.1f}/100")
            print(f"Recommendation: {analysis['recommendation']}")
            print(f"Risk Rating: {analysis['risk_rating']}")
            print(f"Confidence Level: {analysis['confidence_level']:.1f}%")
            
            if analysis['target_price']:
                print(f"Target Price: ${analysis['target_price']:.2f}")
            
            print(f"\n📊 DETAILED SCORES:")
            print(f"  Fundamental: {analysis['fundamental_score']:.1f}/100")
            print(f"  Technical:   {analysis['technical_score']:.1f}/100")
            print(f"  Momentum:    {analysis['momentum_score']:.1f}/100")
            print(f"  Sentiment:   {analysis['sentiment_score']:.1f}/100")
            
            # Key insights
            if analysis['details']:
                details = analysis['details']
                
                print(f"\n💡 KEY INSIGHTS:")
                
                # Fundamental insights
                fund_analysis = details.get('fundamental', {})
                if fund_analysis.get('strength'):
                    print("  Strengths:")
                    for strength in fund_analysis['strength'][:3]:
                        print(f"    • {strength}")
                
                if fund_analysis.get('weakness'):
                    print("  Weaknesses:")
                    for weakness in fund_analysis['weakness'][:3]:
                        print(f"    • {weakness}")
                
                # Technical insights
                tech_analysis = details.get('technical', {})
                if tech_analysis.get('signals'):
                    print("  Technical Signals:")
                    for signal in tech_analysis['signals'][:3]:
                        print(f"    • {signal}")
            
            print(f"{'='*60}\n")
            
        except Exception as e:
            print(f"Error printing analysis report: {e}")

def main():
    """Main function to run the application"""
    print("Stock Analysis Extension for ChatGPT Micro-Cap Experiment")
    print("=" * 60)
    
    # Initialize application
    app = StockAnalysisApp()
    
    if not app.initialize():
        print("Failed to initialize system. Please check configuration and try again.")
        return
    
    # Interactive menu
    while True:
        print("\nAvailable Commands:")
        print("1. Analyze a stock")
        print("2. Get stock recommendations")
        print("3. Analyze existing portfolio")
        print("4. Execute a trade")
        print("5. View portfolio summary")
        print("6. Run daily analysis")
        print("7. Exit")
        
        choice = input("\nEnter your choice (1-7): ").strip()
        
        try:
            if choice == '1':
                symbol = input("Enter stock symbol: ").strip().upper()
                result = app.analyze_stock(symbol)
                app.print_analysis_report(result)
                
            elif choice == '2':
                max_count = input("Maximum recommendations (default 10): ").strip()
                max_count = int(max_count) if max_count else 10
                
                recommendations = app.get_recommendations(max_count)
                
                if recommendations:
                    print(f"\n📈 TOP {len(recommendations)} STOCK RECOMMENDATIONS:")
                    print("-" * 80)
                    for i, rec in enumerate(recommendations, 1):
                        print(f"{i:2d}. {rec['symbol']:6} | Score: {rec['score']:5.1f} | "
                              f"Rec: {rec['recommendation']:10} | Current: ${rec['current_price']:7.2f} | "
                              f"Target: ${rec['target_price']:7.2f} | Sector: {rec['sector']}")
                else:
                    print("No recommendations found.")
                    
            elif choice == '3':
                symbols_input = input("Enter portfolio symbols (comma-separated): ").strip()
                portfolio_symbols = [s.strip().upper() for s in symbols_input.split(',') if s.strip()]
                
                if portfolio_symbols:
                    portfolio_analysis = app.analyze_existing_portfolio(portfolio_symbols)
                    
                    if 'error' not in portfolio_analysis:
                        summary = portfolio_analysis['summary']
                        print(f"\n📊 PORTFOLIO ANALYSIS SUMMARY:")
                        print(f"Total Stocks: {portfolio_analysis['total_stocks']}")
                        print(f"Average Score: {summary['avg_score']:.1f}/100")
                        print(f"Buy Recommendations: {summary['buy_recommendations']}")
                        print(f"Hold Recommendations: {summary['hold_recommendations']}")
                        print(f"Sell Recommendations: {summary['sell_recommendations']}")
                        print(f"High Risk Stocks: {summary['high_risk_stocks']}")
                        
                        if summary['sectors']:
                            print("\nSector Distribution:")
                            for sector, count in summary['sectors'].items():
                                print(f"  {sector}: {count} stocks")
                    else:
                        print(f"Error analyzing portfolio: {portfolio_analysis['error']}")
                else:
                    print("No symbols provided.")
                    
            elif choice == '4':
                symbol = input("Enter stock symbol: ").strip().upper()
                trade_type = input("Enter trade type (BUY/SELL): ").strip().upper()
                quantity = float(input("Enter quantity: "))
                price = float(input("Enter price per share: "))
                
                result = app.execute_trade(symbol, trade_type, quantity, price)
                
                if result['status'] == 'success':
                    print(f"✓ {result['message']}")
                    if result.get('fa_transaction_id'):
                        print(f"FrontAccounting Transaction ID: {result['fa_transaction_id']}")
                else:
                    print(f"✗ Trade failed: {result['message']}")
                    
            elif choice == '5':
                summary = app.get_portfolio_summary()
                
                if summary['status'] == 'success':
                    print(f"\n💼 PORTFOLIO SUMMARY:")
                    print(f"Total Portfolio Value: ${summary['total_portfolio_value']:,.2f}")
                    print(f"Cash Balance: ${summary['cash_balance']:,.2f}")
                    print(f"Positions Value: ${summary['total_positions_value']:,.2f}")
                    print(f"Unrealized P&L: ${summary['unrealized_pnl']:,.2f}")
                    print(f"Number of Positions: {summary['positions_count']}")
                    
                    if summary['top_holdings']:
                        print("\nTop Holdings:")
                        for holding in summary['top_holdings']:
                            print(f"  {holding['symbol']}: ${holding['position_value']:,.2f} "
                                  f"({holding['quantity']} shares)")
                else:
                    print(f"Error getting portfolio summary: {summary['message']}")
                    
            elif choice == '6':
                results = app.run_daily_analysis()
                
                print(f"\n📅 DAILY ANALYSIS RESULTS ({results['date']}):")
                print(f"Stocks Analyzed: {results['analysis_completed']}")
                print(f"New Recommendations: {len(results['recommendations'])}")
                
                if results['recommendations']:
                    print("\nTop 3 New Recommendations:")
                    for i, rec in enumerate(results['recommendations'][:3], 1):
                        print(f"  {i}. {rec['symbol']} - Score: {rec['score']:.1f}")
                
                if results['errors']:
                    print(f"\nErrors: {len(results['errors'])}")
                    for error in results['errors'][:3]:
                        print(f"  • {error}")
                        
            elif choice == '7':
                print("Goodbye!")
                break
                
            else:
                print("Invalid choice. Please try again.")
                
        except KeyboardInterrupt:
            print("\nOperation cancelled.")
            continue
        except Exception as e:
            print(f"Error: {e}")
            continue

if __name__ == "__main__":
    main()
