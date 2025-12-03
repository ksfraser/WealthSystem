"""
Database Configuration Manager for Python
Centralized configuration loader for both micro-cap and legacy systems
"""

import os
import yaml
import configparser
from typing import Dict, Any, Optional
from pathlib import Path


class DatabaseConfig:
    """Database configuration manager that supports YAML and INI files"""
    
    _config: Optional[Dict[str, Any]] = None
    _config_file: Optional[str] = None
    
    @classmethod
    def load(cls, config_file: Optional[str] = None) -> Dict[str, Any]:
        """Load configuration from file"""
        
        if cls._config is not None and config_file == cls._config_file:
            return cls._config
        
        # Default config file locations
        if config_file is None:
            possible_files = [
                'db_config.yml',
                'db_config.yaml', 
                'db_config.ini',
                '../db_config.yml',
                '../db_config.yaml',
                '../db_config.ini'
            ]
            
            for file_path in possible_files:
                if os.path.exists(file_path):
                    config_file = file_path
                    break
        
        if not config_file or not os.path.exists(config_file):
            raise FileNotFoundError(
                'Database configuration file not found. '
                'Please create db_config.yml from db_config.example.yml'
            )
        
        file_ext = Path(config_file).suffix.lower()
        
        if file_ext in ['.yml', '.yaml']:
            cls._config = cls._load_yaml(config_file)
        elif file_ext == '.ini':
            cls._config = cls._load_ini(config_file)
        else:
            raise ValueError('Unsupported configuration file format. Use .yml, .yaml, or .ini')
        
        cls._config_file = config_file
        return cls._config
    
    @classmethod
    def _load_yaml(cls, file_path: str) -> Dict[str, Any]:
        """Load YAML configuration"""
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                return yaml.safe_load(f) or {}
        except ImportError:
            raise ImportError(
                'PyYAML is required to read YAML config files. '
                'Install it with: pip install PyYAML'
            )
    
    @classmethod 
    def _load_ini(cls, file_path: str) -> Dict[str, Any]:
        """Load INI configuration"""
        config = configparser.ConfigParser()
        config.read(file_path, encoding='utf-8')
        
        # Convert ConfigParser to dictionary
        result = {}
        for section_name in config.sections():
            result[section_name] = dict(config[section_name])
        
        return result
    
    @classmethod
    def get_micro_cap_config(cls) -> Dict[str, Any]:
        """Get database configuration for micro-cap system"""
        config = cls.load()
        db_config = config.get('database', {})
        micro_cap_config = db_config.get('micro_cap', {})
        
        return {
            'host': db_config.get('host', 'localhost'),
            'port': int(db_config.get('port', 3306)),
            'database': micro_cap_config.get('database', 'micro_cap_trading'),
            'username': db_config.get('username', ''),
            'password': db_config.get('password', ''),
            'charset': db_config.get('charset', 'utf8mb4')
        }
    
    @classmethod
    def get_legacy_config(cls) -> Dict[str, Any]:
        """Get database configuration for legacy system"""
        config = cls.load()
        db_config = config.get('database', {})
        legacy_config = db_config.get('legacy', {})
        
        return {
            'host': db_config.get('host', 'localhost'),
            'port': int(db_config.get('port', 3306)),
            'database': legacy_config.get('database', 'stock_market_2'),
            'username': db_config.get('username', ''),
            'password': db_config.get('password', ''),
            'charset': db_config.get('charset', 'utf8mb4')
        }
    
    @classmethod
    def get_api_config(cls, provider: Optional[str] = None) -> Dict[str, Any]:
        """Get API configuration"""
        config = cls.load()
        api_config = config.get('apis', {})
        
        if provider:
            return api_config.get(provider, {})
        
        return api_config
    
    @classmethod
    def get_app_config(cls) -> Dict[str, Any]:
        """Get application configuration"""
        config = cls.load()
        
        return {
            'debug': config.get('app', {}).get('debug', False),
            'timezone': config.get('app', {}).get('timezone', 'UTC'),
            'cache_enabled': config.get('app', {}).get('cache_enabled', True)
        }
    
    @classmethod
    def get_logging_config(cls) -> Dict[str, Any]:
        """Get logging configuration"""
        config = cls.load()
        
        return {
            'level': config.get('logging', {}).get('level', 'INFO'),
            'file': config.get('logging', {}).get('file', 'logs/app.log')
        }
    
    @classmethod
    def create_mysql_connection_string(cls, system: str = 'micro_cap') -> str:
        """Create MySQL connection string for SQLAlchemy or similar"""
        if system == 'micro_cap':
            config = cls.get_micro_cap_config()
        elif system == 'legacy':
            config = cls.get_legacy_config()
        else:
            raise ValueError("System must be 'micro_cap' or 'legacy'")
        
        return (
            f"mysql+pymysql://{config['username']}:{config['password']}"
            f"@{config['host']}:{config['port']}/{config['database']}"
            f"?charset={config['charset']}"
        )


# Convenience functions for common use cases
def get_micro_cap_connection_string() -> str:
    """Get connection string for micro-cap database"""
    return DatabaseConfig.create_mysql_connection_string('micro_cap')


def get_legacy_connection_string() -> str:
    """Get connection string for legacy database"""
    return DatabaseConfig.create_mysql_connection_string('legacy')


def get_api_key(provider: str) -> Optional[str]:
    """Get API key for a specific provider"""
    api_config = DatabaseConfig.get_api_config(provider)
    return api_config.get('api_key')
