"""
Interactive Database Configuration Setup

This script helps you set up the db_config.yml file with your actual database credentials.
It will prompt you for the necessary information and create a properly configured file.
"""

import yaml
from pathlib import Path
import getpass
import sys

def get_database_info():
    """Interactively collect database configuration information."""
    print("=" * 60)
    print("DATABASE CONFIGURATION SETUP")
    print("=" * 60)
    print()
    
    print("This will help you set up the database configuration for the enhanced trading scripts.")
    print("We need to configure access to your MySQL databases:")
    print("- Legacy database: stock_market_2")
    print("- Micro-cap database: stock_market_micro_cap_trading")
    print()
    
    # Get database host
    default_host = "localhost"
    host = input(f"Database host [{default_host}]: ").strip()
    if not host:
        host = default_host
    
    # Get database port
    default_port = 3306
    port_input = input(f"Database port [{default_port}]: ").strip()
    if port_input:
        try:
            port = int(port_input)
        except ValueError:
            print(f"Invalid port, using default: {default_port}")
            port = default_port
    else:
        port = default_port
    
    # Get username
    username = input("Database username: ").strip()
    if not username:
        print("Username is required!")
        return None
    
    # Get password
    password = getpass.getpass("Database password: ")
    if not password:
        print("Password is required!")
        return None
    
    # Confirm database names
    print()
    print("Database names (from your earlier specification):")
    print("- Legacy database: stock_market_2")
    print("- Micro-cap database: stock_market_micro_cap_trading")
    
    legacy_db = input("Legacy database name [stock_market_2]: ").strip()
    if not legacy_db:
        legacy_db = "stock_market_2"
    
    micro_cap_db = input("Micro-cap database name [stock_market_micro_cap_trading]: ").strip()
    if not micro_cap_db:
        micro_cap_db = "stock_market_micro_cap_trading"
    
    blue_chip_db = input("Blue-chip database name [stock_market_blue_chip_trading]: ").strip()
    if not blue_chip_db:
        blue_chip_db = "stock_market_blue_chip_trading"
    
    return {
        'host': host,
        'port': port,
        'username': username,
        'password': password,
        'legacy_db': legacy_db,
        'micro_cap_db': micro_cap_db,
        'blue_chip_db': blue_chip_db
    }

def create_config(db_info):
    """Create the database configuration."""
    config = {
        'database': {
            'host': db_info['host'],
            'port': db_info['port'],
            'username': db_info['username'],
            'password': db_info['password'],
            'micro_cap': {
                'database': db_info['micro_cap_db'],
                'description': "Modern per-symbol table structure for micro-cap trading"
            },
            'legacy': {
                'database': db_info['legacy_db'],
                'description': "Legacy database with historical trading data"
            },
            'blue_chip': {
                'database': db_info['blue_chip_db'],
                'description': "Blue-chip focused trading database"
            },
            'pool': {
                'max_connections': 10,
                'pool_name': "trading_pool",
                'pool_reset_session': True
            }
        },
        'application': {
            'logging': {
                'level': 'INFO',
                'file': 'enhanced_trading.log',
                'max_size_mb': 100,
                'backup_count': 5
            },
            'risk': {
                'default_tolerance': 'moderate',
                'max_position_size': 0.10,
                'micro_cap': {
                    'conservative': {
                        'stop_loss': 0.15,
                        'position_limit': 0.05,
                        'max_stocks': 15
                    },
                    'moderate': {
                        'stop_loss': 0.20,
                        'position_limit': 0.08,
                        'max_stocks': 12
                    },
                    'aggressive': {
                        'stop_loss': 0.25,
                        'position_limit': 0.12,
                        'max_stocks': 8
                    }
                },
                'blue_chip': {
                    'conservative': {
                        'stop_loss': 0.10,
                        'position_limit': 0.15,
                        'max_stocks': 8
                    },
                    'moderate': {
                        'stop_loss': 0.12,
                        'position_limit': 0.20,
                        'max_stocks': 6
                    },
                    'aggressive': {
                        'stop_loss': 0.15,
                        'position_limit': 0.25,
                        'max_stocks': 5
                    }
                },
                'small_cap': {
                    'conservative': {
                        'stop_loss': 0.12,
                        'position_limit': 0.08,
                        'max_stocks': 12
                    },
                    'moderate': {
                        'stop_loss': 0.15,
                        'position_limit': 0.10,
                        'max_stocks': 10
                    },
                    'aggressive': {
                        'stop_loss': 0.18,
                        'position_limit': 0.15,
                        'max_stocks': 8
                    }
                }
            }
        },
        'data': {
            'base_dir': "data",
            'micro_cap_dir': "data_micro_cap",
            'blue_chip_dir': "data_blue_chip",
            'small_cap_dir': "data_small_cap",
            'automation_dir': "automation_data"
        },
        'api': {
            'rate_limits': {
                'llm_requests_per_minute': 10,
                'market_data_requests_per_minute': 60
            },
            'timeouts': {
                'llm_timeout_seconds': 30,
                'market_data_timeout_seconds': 10
            }
        },
        'backup': {
            'enabled': True,
            'frequency_hours': 24,
            'retention_days': 30,
            'include_csv': True,
            'include_database': True
        }
    }
    
    return config

def save_config(config, filename="db_config.yml"):
    """Save the configuration to a YAML file."""
    try:
        with open(filename, 'w') as file:
            yaml.dump(config, file, default_flow_style=False, indent=2)
        return True
    except Exception as e:
        print(f"Error saving configuration: {e}")
        return False

def test_connection(db_info):
    """Test the database connection."""
    print()
    print("Testing database connection...")
    
    try:
        import mysql.connector
        from mysql.connector import Error
        
        # Test connection to legacy database
        try:
            connection = mysql.connector.connect(
                host=db_info['host'],
                port=db_info['port'],
                database=db_info['legacy_db'],
                user=db_info['username'],
                password=db_info['password'],
                connection_timeout=10
            )
            
            if connection.is_connected():
                cursor = connection.cursor()
                cursor.execute("SELECT VERSION()")
                version = cursor.fetchone()[0]
                print(f"✓ Connected to legacy database ({db_info['legacy_db']})")
                print(f"  MySQL Version: {version}")
                cursor.close()
                connection.close()
                return True
                
        except Error as e:
            print(f"✗ Connection to legacy database failed: {e}")
            return False
            
    except ImportError:
        print("⚠ mysql-connector-python not installed. Install with:")
        print("  pip install mysql-connector-python")
        return False
    except Exception as e:
        print(f"✗ Connection test failed: {e}")
        return False

def main():
    """Main setup function."""
    try:
        print("Enhanced Trading Scripts - Database Setup")
        print()
        
        # Check if config file already exists
        config_file = Path("db_config.yml")
        if config_file.exists():
            overwrite = input("db_config.yml already exists. Overwrite? (y/N): ").strip().lower()
            if overwrite not in ['y', 'yes']:
                print("Setup cancelled.")
                return
        
        # Get database information
        db_info = get_database_info()
        if not db_info:
            print("Setup cancelled due to missing information.")
            return
        
        # Create configuration
        print()
        print("Creating configuration...")
        config = create_config(db_info)
        
        # Save configuration
        if save_config(config):
            print("✓ Configuration saved to db_config.yml")
        else:
            print("✗ Failed to save configuration")
            return
        
        # Test connection
        if test_connection(db_info):
            print("✓ Database connection successful!")
        else:
            print("⚠ Database connection failed, but configuration was saved.")
            print("  You can test the connection later with: python test_database_connection.py")
        
        print()
        print("=" * 60)
        print("SETUP COMPLETE")
        print("=" * 60)
        print()
        print("Next steps:")
        print("1. Test full connection: python test_database_connection.py")
        print("2. Run enhanced demo: python demo_simple_enhanced.py")
        print("3. Start using enhanced trading scripts!")
        print()
        print("Configuration saved with:")
        print(f"- Host: {db_info['host']}:{db_info['port']}")
        print(f"- Legacy DB: {db_info['legacy_db']}")
        print(f"- Micro-cap DB: {db_info['micro_cap_db']}")
        print(f"- Blue-chip DB: {db_info['blue_chip_db']}")
        
    except KeyboardInterrupt:
        print("\n\nSetup cancelled by user.")
    except Exception as e:
        print(f"\nSetup failed with error: {e}")

if __name__ == "__main__":
    main()
