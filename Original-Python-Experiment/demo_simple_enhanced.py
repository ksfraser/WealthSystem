"""
Simple Demo of Enhanced Trading Scripts

This simplified demo shows the enhanced trading functionality without dependencies
on the original simple_automation.py file. It demonstrates:
- CSV + Database integration
- Multi market cap support
- Risk management features
"""

import sys
from pathlib import Path
import pandas as pd

# Add the current directory to Python path
sys.path.append(str(Path(__file__).parent))

# Test if dependencies are available
try:
    from enhanced_trading_script import (
        create_micro_cap_engine, 
        create_blue_chip_engine,
        create_small_cap_engine,
        DatabaseManager
    )
    ENHANCED_AVAILABLE = True
except ImportError as e:
    print(f"Enhanced trading scripts not available: {e}")
    ENHANCED_AVAILABLE = False

def demo_basic_functionality():
    """Demonstrate basic enhanced trading functionality."""
    if not ENHANCED_AVAILABLE:
        print("Enhanced trading scripts are not available. Please install dependencies.")
        return
        
    print("=" * 60)
    print("ENHANCED TRADING SCRIPT DEMO")
    print("=" * 60)
    print()
    
    # Demo 1: Create engines for different market cap categories
    print("1. Creating trading engines for different market categories:")
    print("-" * 50)
    
    try:
        # Create micro-cap engine
        micro_engine = create_micro_cap_engine()
        print(f"✓ Micro-cap engine created")
        print(f"  - Database enabled: {micro_engine.db_connected}")
        print(f"  - Data directory: {micro_engine.data_dir}")
        print(f"  - Portfolio CSV: {micro_engine.portfolio_csv}")
        print()
        
        # Create blue-chip engine
        blue_engine = create_blue_chip_engine()
        print(f"✓ Blue-chip engine created")
        print(f"  - Database enabled: {blue_engine.db_connected}")
        print(f"  - Data directory: {blue_engine.data_dir}")
        print(f"  - Portfolio CSV: {blue_engine.portfolio_csv}")
        print()
        
        # Create small-cap engine
        small_engine = create_small_cap_engine()
        print(f"✓ Small-cap engine created")
        print(f"  - Database enabled: {small_engine.db_connected}")
        print(f"  - Data directory: {small_engine.data_dir}")
        print(f"  - Portfolio CSV: {small_engine.portfolio_csv}")
        print()
        
    except Exception as e:
        print(f"✗ Error creating engines: {e}")
        return
    
    # Demo 2: Load portfolio states for each category
    print("2. Loading portfolio states:")
    print("-" * 50)
    
    engines = [
        ("Micro-cap", micro_engine),
        ("Blue-chip", blue_engine),
        ("Small-cap", small_engine)
    ]
    
    for name, engine in engines:
        try:
            portfolio, cash = engine.load_portfolio_state()
            total_equity = cash + (portfolio['cost_basis'].sum() if not portfolio.empty else 0)
            
            print(f"{name} Portfolio:")
            print(f"  - Positions: {len(portfolio)}")
            print(f"  - Cash: ${cash:,.2f}")
            print(f"  - Total Equity: ${total_equity:,.2f}")
            
            if not portfolio.empty:
                print(f"  - Holdings:")
                for _, row in portfolio.head(3).iterrows():  # Show first 3 holdings
                    print(f"    * {row['ticker']}: {row['shares']:.2f} shares @ ${row['buy_price']:.2f}")
            else:
                print(f"  - No current holdings")
            print()
            
        except Exception as e:
            print(f"✗ Error loading {name} portfolio: {e}")
            print()
    
    # Demo 3: Database connection test
    print("3. Database connection status:")
    print("-" * 50)
    
    try:
        db_manager = DatabaseManager()
        if db_manager.config:
            print("✓ Database configuration found")
            print(f"  Configuration: {db_manager.config.get('database', {}).get('host', 'Not configured')}")
        else:
            print("⚠ Database configuration not found (CSV-only mode)")
            print("  To enable database features, create db_config.yml with your database settings")
        
        connected = db_manager.connect() if db_manager.config else False
        if connected:
            print("✓ Database connection successful")
        else:
            print("⚠ Database connection failed or not configured")
            print("  Enhanced scripts will work in CSV-only mode")
        print()
        
    except Exception as e:
        print(f"⚠ Database test error: {e}")
        print("  This is normal if database is not set up yet")
        print()
    
    # Demo 4: Show data directories created
    print("4. Data directories created:")
    print("-" * 50)
    
    data_dirs = [
        Path("data_micro_cap"),
        Path("data_blue_chip"),
        Path("data_small_cap")
    ]
    
    for data_dir in data_dirs:
        if data_dir.exists():
            csv_files = list(data_dir.glob("*.csv"))
            print(f"✓ {data_dir}/")
            if csv_files:
                for csv_file in csv_files:
                    size = csv_file.stat().st_size if csv_file.exists() else 0
                    print(f"  - {csv_file.name} ({size} bytes)")
            else:
                print(f"  - No CSV files yet (will be created on first use)")
        else:
            print(f"⚠ {data_dir}/ (will be created on first use)")
    print()
    
    # Demo 5: Show market data functionality
    print("5. Market data functionality test:")
    print("-" * 50)
    
    try:
        # Test with a small sample of tickers
        sample_tickers = ['AAPL']  # Just one ticker for demo
        print(f"Testing market data fetch for: {sample_tickers}")
        
        market_data = micro_engine.get_market_data(sample_tickers, days=5)
        
        for ticker, data in market_data.items():
            if not data.empty:
                latest_price = data['Close'].iloc[-1]
                print(f"✓ {ticker}: Latest price ${latest_price:.2f}")
            else:
                print(f"⚠ {ticker}: No data retrieved")
                
    except Exception as e:
        print(f"⚠ Market data test failed: {e}")
        print("  This is normal if internet connection is limited")
    print()

def demo_csv_compatibility():
    """Demonstrate CSV backward compatibility."""
    print("6. CSV Backward Compatibility:")
    print("-" * 50)
    
    print("The enhanced scripts maintain full backward compatibility:")
    print("✓ Existing CSV files are read and processed normally")
    print("✓ Original workflows continue to work unchanged")
    print("✓ New features are additive and optional")
    print("✓ Database integration is optional")
    print()
    
    print("Migration path:")
    print("1. Install enhanced requirements: pip install mysql-connector-python PyYAML")
    print("2. Configure database (optional): Edit db_config.yml")
    print("3. Use enhanced engines: create_micro_cap_engine()")
    print("4. Existing CSV data continues to work seamlessly")
    print()

def demo_risk_management():
    """Demonstrate risk management features."""
    print("7. Risk Management Features:")
    print("-" * 50)
    
    if not ENHANCED_AVAILABLE:
        print("Enhanced scripts not available for risk management demo")
        return
    
    # Show risk parameters for different categories
    categories = [
        ('micro', 'moderate'),
        ('blue-chip', 'conservative'),
        ('small', 'moderate')
    ]
    
    print("Risk parameters by market cap category:")
    
    try:
        from enhanced_automation import EnhancedAutomationEngine
        
        for market_cap, risk_tolerance in categories:
            automation = EnhancedAutomationEngine(
                market_cap_category=market_cap,
                enable_database=False,  # Disable database for demo
                risk_tolerance=risk_tolerance
            )
            
            params = automation.risk_params
            print(f"\n{market_cap.upper()}-CAP ({risk_tolerance}):")
            print(f"  Stop Loss: {params['stop_loss']:.1%}")
            print(f"  Max Position: {params['position_limit']:.1%}")
            print(f"  Max Stocks: {params['max_stocks']}")
            
    except ImportError:
        print("Enhanced automation not available for risk demo")
    except Exception as e:
        print(f"Risk management demo error: {e}")

def main():
    """Run the enhanced trading demo."""
    print("Enhanced Trading Scripts - Simple Demo")
    print("This demonstrates the new database + CSV integration features")
    print()
    
    try:
        # Basic functionality demo
        demo_basic_functionality()
        
        # CSV compatibility demo
        demo_csv_compatibility()
        
        # Risk management demo
        demo_risk_management()
        
        print("=" * 60)
        print("DEMO COMPLETED")
        print("=" * 60)
        print()
        
        print("Key Benefits of Enhanced Scripts:")
        print("✓ Full backward compatibility with existing CSV files")
        print("✓ Optional database integration for advanced features")
        print("✓ Multi-market cap support (micro, blue-chip, small, etc.)")
        print("✓ Built-in risk management and position sizing")
        print("✓ Enhanced logging and session tracking")
        print("✓ Scalable architecture for future enhancements")
        print()
        
        print("Next Steps:")
        print("1. Configure database settings in db_config.yml (optional)")
        print("2. Test database connection: python test_database_connection.py")
        print("3. Use enhanced engines in your trading workflow")
        print("4. Explore automation features with enhanced_automation.py")
        
    except Exception as e:
        print(f"Demo failed with error: {e}")
        print()
        print("This may be due to missing dependencies or configuration.")
        print("The enhanced scripts are designed to work in CSV-only mode")
        print("even without database configuration.")

if __name__ == "__main__":
    main()
