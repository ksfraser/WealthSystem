"""
Example Usage of Enhanced Trading Scripts

This script demonstrates how to use the enhanced trading scripts for different
market cap categories while maintaining CSV compatibility and adding database features.
"""

import sys
from pathlib import Path

# Add the current directory to Python path
sys.path.append(str(Path(__file__).parent))

from enhanced_trading_script import (
    create_micro_cap_engine, 
    create_blue_chip_engine,
    create_small_cap_engine
)
from enhanced_automation import (
    create_micro_cap_automation,
    create_blue_chip_automation,
    create_small_cap_automation
)

def demo_micro_cap_trading():
    """Demonstrate micro-cap trading with enhanced features."""
    print("=" * 60)
    print("MICRO-CAP TRADING DEMO")
    print("=" * 60)
    
    # Create micro-cap trading engine
    engine = create_micro_cap_engine()
    
    # Check database connection status
    print(f"Database enabled: {engine.db_connected}")
    print(f"Data directory: {engine.data_dir}")
    print(f"Portfolio CSV: {engine.portfolio_csv}")
    print()
    
    # Load current portfolio state
    portfolio, cash = engine.load_portfolio_state()
    total_equity = cash + (portfolio['cost_basis'].sum() if not portfolio.empty else 0)
    
    print(f"Current Portfolio:")
    print(f"- Positions: {len(portfolio)}")
    print(f"- Cash: ${cash:,.2f}")
    print(f"- Total Equity: ${total_equity:,.2f}")
    print()
    
    if not portfolio.empty:
        print("Current Holdings:")
        for _, row in portfolio.iterrows():
            print(f"- {row['ticker']}: {row['shares']:.2f} shares @ ${row['buy_price']:.2f}")
    else:
        print("No current holdings (starting fresh)")
    print()
    
    # Example: Save current portfolio state
    if engine.save_portfolio_data(portfolio, cash, total_equity):
        print("✓ Portfolio data saved successfully")
    else:
        print("✗ Failed to save portfolio data")
    
    # Example: Get market data with caching
    sample_tickers = ['AAPL', 'MSFT']  # Example tickers
    print(f"\nFetching market data for: {sample_tickers}")
    market_data = engine.get_market_data(sample_tickers, days=5)
    for ticker, data in market_data.items():
        if not data.empty:
            latest_price = data['Close'].iloc[-1]
            print(f"- {ticker}: ${latest_price:.2f}")
    
    return engine, portfolio, cash

def demo_blue_chip_trading():
    """Demonstrate blue-chip trading with conservative approach."""
    print("=" * 60)
    print("BLUE-CHIP TRADING DEMO")
    print("=" * 60)
    
    # Create blue-chip trading engine
    engine = create_blue_chip_engine()
    
    print(f"Database enabled: {engine.db_connected}")
    print(f"Data directory: {engine.data_dir}")
    print()
    
    # Load portfolio state
    portfolio, cash = engine.load_portfolio_state()
    total_equity = cash + (portfolio['cost_basis'].sum() if not portfolio.empty else 0)
    
    print(f"Blue-Chip Portfolio:")
    print(f"- Positions: {len(portfolio)}")
    print(f"- Cash: ${cash:,.2f}")
    print(f"- Total Equity: ${total_equity:,.2f}")
    print()
    
    return engine, portfolio, cash

def demo_automation_engine():
    """Demonstrate automated trading with enhanced features."""
    print("=" * 60)
    print("AUTOMATION ENGINE DEMO")
    print("=" * 60)
    
    # Create automation engine for micro-cap with moderate risk
    automation = create_micro_cap_automation(risk_tolerance='moderate')
    
    print(f"Market cap category: {automation.market_cap_category}")
    print(f"Risk tolerance: {automation.risk_tolerance}")
    print(f"Database enabled: {automation.trading_engine.db_connected}")
    print()
    
    # Get risk parameters
    print("Risk Management Parameters:")
    for key, value in automation.risk_params.items():
        if isinstance(value, float):
            print(f"- {key}: {value:.1%}")
        else:
            print(f"- {key}: {value}")
    print()
    
    # Load current portfolio for analysis
    portfolio, cash = automation.trading_engine.load_portfolio_state()
    
    # Perform enhanced portfolio analysis
    print("Performing enhanced portfolio analysis...")
    analysis = automation.enhanced_portfolio_analysis(portfolio, cash)
    
    print(f"Portfolio Analysis Results:")
    print(f"- Status: {analysis.get('status', 'active')}")
    print(f"- Total Equity: ${analysis.get('total_equity', 0):,.2f}")
    print(f"- Cash Percentage: {analysis.get('cash_percentage', 0):.1%}")
    print(f"- Position Count: {analysis.get('position_count', 0)}")
    
    if 'risk_metrics' in analysis:
        risk = analysis['risk_metrics']
        print(f"- Risk Level: {risk.get('position_risk', 'unknown')}")
        print(f"- Diversification: {risk.get('diversification', 'unknown')}")
    print()
    
    # Get performance metrics
    print("Performance Metrics (Last 30 days):")
    metrics = automation.get_performance_metrics(days=30)
    for key, value in metrics.items():
        if isinstance(value, float):
            if 'rate' in key:
                print(f"- {key}: {value:.1%}")
            else:
                print(f"- {key}: {value:.2f}")
        else:
            print(f"- {key}: {value}")
    print()
    
    # Get session history
    print("Recent Session History:")
    history = automation.get_session_history(limit=3)
    if history:
        for session in history[-3:]:
            print(f"- {session['session_id']}: {session['total_trades']} trades, "
                  f"${session.get('total_pnl', 0):.2f} P&L")
    else:
        print("- No previous sessions found")
    print()
    
    return automation

def demo_risk_management():
    """Demonstrate risk management features across different market caps."""
    print("=" * 60)
    print("RISK MANAGEMENT COMPARISON")
    print("=" * 60)
    
    categories = [
        ('micro', 'moderate'),
        ('blue-chip', 'conservative'), 
        ('small', 'moderate')
    ]
    
    for market_cap, risk_tolerance in categories:
        print(f"\n{market_cap.upper()} Cap - {risk_tolerance.title()} Risk:")
        
        if market_cap == 'micro':
            automation = create_micro_cap_automation(risk_tolerance=risk_tolerance)
        elif market_cap == 'blue-chip':
            automation = create_blue_chip_automation(risk_tolerance=risk_tolerance)
        else:
            automation = create_small_cap_automation(risk_tolerance=risk_tolerance)
        
        params = automation.risk_params
        print(f"  Stop Loss: {params['stop_loss']:.1%}")
        print(f"  Max Position: {params['position_limit']:.1%}")
        print(f"  Max Stocks: {params['max_stocks']}")

def demo_csv_database_compatibility():
    """Demonstrate CSV and database compatibility."""
    print("=" * 60)
    print("CSV + DATABASE COMPATIBILITY DEMO")
    print("=" * 60)
    
    # Create engine with database enabled
    engine_with_db = create_micro_cap_engine()
    print(f"Engine with database: {engine_with_db.db_connected}")
    
    # Create engine with database disabled
    from enhanced_trading_script import EnhancedTradingEngine
    engine_csv_only = EnhancedTradingEngine('micro', enable_database=False)
    print(f"CSV-only engine: {engine_csv_only.db_connected}")
    
    # Both engines can load the same CSV data
    portfolio_db, cash_db = engine_with_db.load_portfolio_state()
    portfolio_csv, cash_csv = engine_csv_only.load_portfolio_state()
    
    print(f"\nPortfolio loaded from DB-enabled engine: {len(portfolio_db)} positions")
    print(f"Portfolio loaded from CSV-only engine: {len(portfolio_csv)} positions")
    print(f"Cash amounts match: {cash_db == cash_csv}")
    
    # Demonstrate that CSV files are always created regardless of database setting
    print(f"\nCSV files created:")
    print(f"- Portfolio CSV exists: {engine_with_db.portfolio_csv.exists()}")
    print(f"- Trade log CSV exists: {engine_with_db.trade_log_csv.exists()}")

def main():
    """Run all demonstration examples."""
    print("Enhanced Trading Scripts - Demonstration")
    print("This demo shows the enhanced features while maintaining CSV compatibility")
    print()
    
    try:
        # Demo 1: Micro-cap trading
        micro_engine, micro_portfolio, micro_cash = demo_micro_cap_trading()
        
        # Demo 2: Blue-chip trading  
        blue_engine, blue_portfolio, blue_cash = demo_blue_chip_trading()
        
        # Demo 3: Automation engine
        automation = demo_automation_engine()
        
        # Demo 4: Risk management comparison
        demo_risk_management()
        
        # Demo 5: CSV/Database compatibility
        demo_csv_database_compatibility()
        
        print("=" * 60)
        print("DEMO COMPLETED SUCCESSFULLY")
        print("=" * 60)
        print()
        print("Key Points:")
        print("1. Enhanced scripts maintain full CSV compatibility")
        print("2. Database integration is optional and additive")
        print("3. Different market cap categories have tailored strategies")
        print("4. Risk management is built-in and configurable")
        print("5. Automation provides advanced analytics and session tracking")
        print()
        print("To run actual trading:")
        print("- Use engine.process_portfolio(portfolio, cash) for interactive trading")
        print("- Use automation.run_automated_trading_session() for automated trading")
        print("- Configure db_config.yml for database features")
        
    except Exception as e:
        print(f"Demo failed with error: {e}")
        print("\nThis is normal if you haven't set up the database yet.")
        print("The enhanced scripts will work in CSV-only mode.")
        print("To enable database features, configure db_config.yml and set up the database.")

if __name__ == "__main__":
    main()
