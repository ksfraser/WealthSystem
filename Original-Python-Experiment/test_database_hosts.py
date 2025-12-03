#!/usr/bin/env python3
"""
Database Host Testing Script
Tests various hostname formats to help resolve DNS issues
"""

import socket
import mysql.connector
from mysql.connector import Error
import yaml

def test_dns_resolution(hostname):
    """Test if a hostname resolves in DNS"""
    try:
        socket.gethostbyname(hostname)
        return True, "Resolves successfully"
    except socket.gaierror as e:
        return False, str(e)

def test_mysql_connection(host, port, user, password, database=None):
    """Test MySQL connection to a specific host"""
    try:
        config = {
            'host': host,
            'port': port,
            'user': user,
            'password': password,
            'connect_timeout': 10
        }
        if database:
            config['database'] = database
            
        connection = mysql.connector.connect(**config)
        if connection.is_connected():
            connection.close()
            return True, "Connection successful"
    except Error as e:
        return False, str(e)
    except Exception as e:
        return False, str(e)

def main():
    print("=" * 60)
    print("DATABASE HOST TESTING SCRIPT")
    print("=" * 60)
    
    # Load current config
    try:
        with open('db_config.yml', 'r') as file:
            config = yaml.safe_load(file)
        current_host = config['database']['host']
        username = config['database']['username']
        password = config['database']['password']
        port = config['database'].get('port', 3306)
    except Exception as e:
        print(f"Error loading config: {e}")
        return
    
    print(f"Current host in config: {current_host}")
    print(f"Username: {username}")
    print(f"Port: {port}")
    print()
    
    # Test various hostname formats
    test_hosts = [
        current_host,  # Current config
        'ksfraser.com',
        'db.ksfraser.com',
        'mysql.ksfraser.com',
        'localhost',
        '127.0.0.1',
        # Add IP if you know it
    ]
    
    print("DNS RESOLUTION TESTS:")
    print("-" * 40)
    for host in test_hosts:
        resolves, message = test_dns_resolution(host)
        status = "✓" if resolves else "✗"
        print(f"{status} {host:<25} {message}")
    
    print("\nMYSQL CONNECTION TESTS:")
    print("-" * 40)
    for host in test_hosts:
        resolves, _ = test_dns_resolution(host)
        if resolves:
            connects, message = test_mysql_connection(host, port, username, password)
            status = "✓" if connects else "✗"
            print(f"{status} {host:<25} {message}")
        else:
            print(f"- {host:<25} Skipped (DNS fails)")
    
    print("\n" + "=" * 60)
    print("RECOMMENDATIONS:")
    print("=" * 60)
    print("1. If none of the hosts work, check with your hosting provider")
    print("2. You might need to access from a specific network/VPN")
    print("3. The database server might be down or firewalled")
    print("4. Consider using an IP address if you have it")
    print("5. For now, your enhanced scripts work perfectly in CSV-only mode")
    
    print("\nTO UPDATE CONFIG:")
    print("Edit db_config.yml and change the 'host' value under 'database'")

if __name__ == "__main__":
    main()
