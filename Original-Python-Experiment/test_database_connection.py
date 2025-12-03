"""
Database Connection Test for Enhanced Trading Scripts

This script tests the database connections using your actual database names:
- Legacy database: stock_market_2
- Micro-cap database: stock_market_micro_cap_trading

Run this script to verify your database configuration before using the enhanced trading scripts.
"""

import sys
from pathlib import Path
import yaml

# Add the current directory to Python path
sys.path.append(str(Path(__file__).parent))

try:
    import mysql.connector
    from mysql.connector import Error
    HAS_DB_DEPS = True
except ImportError:
    HAS_DB_DEPS = False
    print("ERROR: Database dependencies not installed.")
    print("Please install: pip install mysql-connector-python PyYAML")
    sys.exit(1)

def load_config():
    """Load database configuration."""
    config_file = "db_config.yml"
    if not Path(config_file).exists():
        print(f"ERROR: Configuration file {config_file} not found.")
        print("Please create db_config.yml with your database settings.")
        return None
    
    with open(config_file, 'r') as file:
        return yaml.safe_load(file)

def test_database_connection(host, port, database, username, password, db_type):
    """Test a database connection."""
    print(f"\nTesting {db_type} database connection...")
    print(f"  Host: {host}:{port}")
    print(f"  Database: {database}")
    print(f"  Username: {username}")
    
    try:
        connection = mysql.connector.connect(
            host=host,
            port=port,
            database=database,
            user=username,
            password=password,
            charset='utf8mb4',
            connection_timeout=10
        )
        
        if connection.is_connected():
            cursor = connection.cursor()
            
            # Test basic query
            cursor.execute("SELECT VERSION()")
            version = cursor.fetchone()[0]
            print(f"  ✓ Connected successfully!")
            print(f"  MySQL Version: {version}")
            
            # Check for expected tables
            cursor.execute("SHOW TABLES")
            tables = [table[0] for table in cursor.fetchall()]
            print(f"  Tables found: {len(tables)}")
            
            # Check for specific trading tables
            expected_tables = ['portfolio_data', 'trade_log', 'historical_prices']
            found_tables = [table for table in expected_tables if table in tables]
            missing_tables = [table for table in expected_tables if table not in tables]
            
            if found_tables:
                print(f"  ✓ Trading tables found: {', '.join(found_tables)}")
            if missing_tables:
                print(f"  ⚠ Missing tables: {', '.join(missing_tables)}")
                print(f"    (These will be created automatically when needed)")
            
            cursor.close()
            connection.close()
            return True
            
    except Error as e:
        print(f"  ✗ Connection failed: {e}")
        return False
    except Exception as e:
        print(f"  ✗ Unexpected error: {e}")
        return False

def test_enhanced_trading_import():
    """Test importing the enhanced trading scripts."""
    print("\nTesting enhanced trading script imports...")
    
    try:
        from enhanced_trading_script import DatabaseManager, EnhancedTradingEngine
        print("  ✓ Enhanced trading script imported successfully")
        
        # Test database manager
        db_manager = DatabaseManager()
        print("  ✓ DatabaseManager created successfully")
        
        # Test enhanced trading engine (CSV-only mode)
        engine = EnhancedTradingEngine('micro', enable_database=False)
        print("  ✓ EnhancedTradingEngine created successfully (CSV-only mode)")
        
        return True
        
    except ImportError as e:
        print(f"  ✗ Import failed: {e}")
        return False
    except Exception as e:
        print(f"  ✗ Unexpected error: {e}")
        return False

def main():
    """Run all database tests."""
    print("=" * 60)
    print("DATABASE CONNECTION TEST")
    print("=" * 60)
    
    # Test 1: Check dependencies
    if not HAS_DB_DEPS:
        return
    
    # Test 2: Load configuration
    config = load_config()
    if not config:
        return
    
    # Test 3: Test enhanced script imports
    if not test_enhanced_trading_import():
        return
    
    # Test 4: Test database connections
    db_config = config.get('database', {})
    host = db_config.get('host', 'localhost')
    port = db_config.get('port', 3306)
    username = db_config.get('username')
    password = db_config.get('password')
    
    if not username or not password:
        print("\nERROR: Username and password must be configured in db_config.yml")
        print("Please update the following section:")
        print("""
database:
  host: localhost
  port: 3306
  username: your_username
  password: your_password
""")
        return
    
    # Test legacy database
    legacy_config = db_config.get('legacy', {})
    legacy_db = legacy_config.get('database', 'stock_market_2')
    legacy_success = test_database_connection(host, port, legacy_db, username, password, "Legacy")
    
    # Test micro-cap database
    micro_config = db_config.get('micro_cap', {})
    micro_db = micro_config.get('database', 'stock_market_micro_cap_trading')
    micro_success = test_database_connection(host, port, micro_db, username, password, "Micro-cap")
    
    # Test blue-chip database (optional)
    blue_config = db_config.get('blue_chip', {})
    blue_db = blue_config.get('database', 'stock_market_blue_chip_trading')
    blue_success = test_database_connection(host, port, blue_db, username, password, "Blue-chip")
    
    # Summary
    print("\n" + "=" * 60)
    print("TEST SUMMARY")
    print("=" * 60)
    
    print(f"Legacy database (stock_market_2): {'✓ PASS' if legacy_success else '✗ FAIL'}")
    print(f"Micro-cap database (stock_market_micro_cap_trading): {'✓ PASS' if micro_success else '✗ FAIL'}")
    print(f"Blue-chip database (stock_market_blue_chip_trading): {'✓ PASS' if blue_success else '✗ FAIL (optional)'}")
    
    if legacy_success and micro_success:
        print("\n🎉 Database configuration is working correctly!")
        print("You can now use the enhanced trading scripts with database features.")
        
        # Test enhanced trading engine with database
        print("\nTesting enhanced trading engine with database...")
        try:
            from enhanced_trading_script import create_micro_cap_engine
            engine = create_micro_cap_engine()
            print(f"  ✓ Database-enabled engine created: {engine.db_connected}")
            
            if engine.db_connected:
                portfolio, cash = engine.load_portfolio_state()
                print(f"  ✓ Portfolio loaded: {len(portfolio)} positions, ${cash:,.2f} cash")
            
        except Exception as e:
            print(f"  ⚠ Enhanced engine test warning: {e}")
            
    else:
        print("\n⚠ Some database connections failed.")
        print("The enhanced trading scripts will work in CSV-only mode.")
        print("To enable database features:")
        print("1. Check your database names and credentials in db_config.yml")
        print("2. Ensure the databases exist")
        print("3. Verify user permissions")
    
    print("\nNext steps:")
    print("1. Run: python demo_enhanced_trading.py")
    print("2. Or use the enhanced scripts in your trading workflow")

if __name__ == "__main__":
    main()
