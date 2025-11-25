"""
Utility functions for the Stock Analysis Extension
"""

import pandas as pd
import numpy as np
import os
from typing import List, Dict, Any
import matplotlib.pyplot as plt
import seaborn as sns
from datetime import datetime, timedelta
import json

def create_sample_config():
    """Create a sample configuration file"""
    config_content = '''
# Copy this template to config.py and update with your settings

import os
from pathlib import Path

# Database Configuration
DATABASE_CONFIG = {
    'host': 'localhost',
    'port': 3306,
    'database': 'stock_analysis',
    'user': 'your_username',
    'password': 'your_password',
    'charset': 'utf8mb4',
    'autocommit': True
}

# FrontAccounting Integration (Optional)
FRONTACCOUNTING_CONFIG = {
    'api_url': 'http://localhost/frontaccounting/api',
    'username': 'admin',
    'password': 'password',
    'company_id': 1,
    'fiscal_year': 2025
}

# API Keys (Optional but recommended)
API_KEYS = {
    'finnhub': '',  # Get free key at finnhub.io
    'alpha_vantage': '',  # Get free key at alphavantage.co
    'fmp': ''  # Financial Modeling Prep (optional)
}

# Analysis Settings
ANALYSIS_CONFIG = {
    'lookback_period_days': 252,  # 1 year
    'min_volume': 100000,
    'min_market_cap': 1000000000,  # $1B
    'max_pe_ratio': 50,
    'min_dividend_yield': 0.0
}

# Risk Management
RISK_CONFIG = {
    'max_position_size': 0.05,     # 5% max per position
    'stop_loss_pct': 0.15,         # 15% stop loss
    'take_profit_pct': 0.25,       # 25% take profit
    'max_correlation': 0.7,        # Max correlation between positions
    'var_confidence': 0.05,        # 95% VaR confidence
    'max_sector_exposure': 0.25    # 25% max per sector
}

# Scoring Weights (must sum to 1.0)
SCORING_WEIGHTS = {
    'fundamental': 0.40,
    'technical': 0.30,
    'momentum': 0.20,
    'sentiment': 0.10
}
'''
    
    with open('config/config.py', 'w') as f:
        f.write(config_content)
    
    print("Sample configuration created at config/config.py")
    print("Please update with your actual settings before running the system.")

def export_analysis_to_excel(analysis_results: List[Dict], filename: str = None):
    """
    Export analysis results to Excel file
    
    Args:
        analysis_results: List of analysis result dictionaries
        filename: Output filename (optional)
    """
    if not filename:
        filename = f"stock_analysis_{datetime.now().strftime('%Y%m%d_%H%M%S')}.xlsx"
    
    # Prepare data for Excel
    data = []
    for result in analysis_results:
        if result.get('analysis') and not result.get('error'):
            analysis = result['analysis']
            stock_data = result.get('stock_data', {})
            fundamentals = stock_data.get('fundamentals', {})
            
            # Current price
            current_price = None
            if stock_data.get('price_data') is not None and not stock_data['price_data'].empty:
                current_price = float(stock_data['price_data']['close'].iloc[-1])
            
            row = {
                'Symbol': analysis['symbol'],
                'Company Name': fundamentals.get('company_name', ''),
                'Sector': fundamentals.get('sector', ''),
                'Current Price': current_price,
                'Overall Score': analysis['overall_score'],
                'Recommendation': analysis['recommendation'],
                'Target Price': analysis['target_price'],
                'Risk Rating': analysis['risk_rating'],
                'Confidence': analysis['confidence_level'],
                'Fundamental Score': analysis['fundamental_score'],
                'Technical Score': analysis['technical_score'],
                'Momentum Score': analysis['momentum_score'],
                'Sentiment Score': analysis['sentiment_score'],
                'PE Ratio': fundamentals.get('pe_ratio'),
                'PB Ratio': fundamentals.get('price_to_book'),
                'ROE': fundamentals.get('return_on_equity'),
                'Debt to Equity': fundamentals.get('debt_to_equity'),
                'Market Cap': fundamentals.get('market_cap'),
                'Analysis Date': analysis['analysis_date']
            }
            data.append(row)
    
    # Create DataFrame and save to Excel
    df = pd.DataFrame(data)
    
    with pd.ExcelWriter(filename, engine='xlsxwriter') as writer:
        # Main analysis sheet
        df.to_excel(writer, sheet_name='Analysis Results', index=False)
        
        # Summary statistics
        summary_data = {
            'Metric': ['Total Stocks', 'Average Score', 'Buy Recommendations', 
                      'Hold Recommendations', 'Sell Recommendations', 'High Risk Stocks'],
            'Value': [
                len(df),
                df['Overall Score'].mean(),
                len(df[df['Recommendation'].isin(['BUY', 'STRONG_BUY'])]),
                len(df[df['Recommendation'] == 'HOLD']),
                len(df[df['Recommendation'].isin(['SELL', 'STRONG_SELL'])]),
                len(df[df['Risk Rating'].isin(['HIGH', 'VERY_HIGH'])])
            ]
        }
        
        summary_df = pd.DataFrame(summary_data)
        summary_df.to_excel(writer, sheet_name='Summary', index=False)
        
        # Sector breakdown
        if 'Sector' in df.columns:
            sector_summary = df.groupby('Sector').agg({
                'Overall Score': 'mean',
                'Symbol': 'count'
            }).rename(columns={'Symbol': 'Count'}).reset_index()
            sector_summary.to_excel(writer, sheet_name='Sector Analysis', index=False)
    
    print(f"Analysis exported to {filename}")

def create_analysis_charts(analysis_results: List[Dict], save_path: str = "charts"):
    """
    Create visualization charts for analysis results
    
    Args:
        analysis_results: List of analysis result dictionaries
        save_path: Directory to save charts
    """
    import os
    os.makedirs(save_path, exist_ok=True)
    
    # Prepare data
    data = []
    for result in analysis_results:
        if result.get('analysis') and not result.get('error'):
            analysis = result['analysis']
            stock_data = result.get('stock_data', {})
            fundamentals = stock_data.get('fundamentals', {})
            
            data.append({
                'symbol': analysis['symbol'],
                'overall_score': analysis['overall_score'],
                'recommendation': analysis['recommendation'],
                'risk_rating': analysis['risk_rating'],
                'fundamental_score': analysis['fundamental_score'],
                'technical_score': analysis['technical_score'],
                'momentum_score': analysis['momentum_score'],
                'sentiment_score': analysis['sentiment_score'],
                'sector': fundamentals.get('sector', 'Unknown')
            })
    
    if not data:
        print("No data available for charting")
        return
    
    df = pd.DataFrame(data)
    
    # Set style
    plt.style.use('seaborn-v0_8')
    
    # 1. Score Distribution
    plt.figure(figsize=(12, 8))
    plt.subplot(2, 2, 1)
    plt.hist(df['overall_score'], bins=20, alpha=0.7, color='skyblue')
    plt.title('Overall Score Distribution')
    plt.xlabel('Score')
    plt.ylabel('Frequency')
    
    # 2. Recommendation Distribution
    plt.subplot(2, 2, 2)
    rec_counts = df['recommendation'].value_counts()
    plt.pie(rec_counts.values, labels=rec_counts.index, autopct='%1.1f%%')
    plt.title('Recommendation Distribution')
    
    # 3. Score Components
    plt.subplot(2, 2, 3)
    score_cols = ['fundamental_score', 'technical_score', 'momentum_score', 'sentiment_score']
    df[score_cols].boxplot()
    plt.title('Score Component Distribution')
    plt.xticks(rotation=45)
    
    # 4. Risk vs Score
    plt.subplot(2, 2, 4)
    risk_order = ['LOW', 'MEDIUM', 'HIGH', 'VERY_HIGH']
    for risk in risk_order:
        if risk in df['risk_rating'].values:
            risk_data = df[df['risk_rating'] == risk]['overall_score']
            plt.scatter([risk] * len(risk_data), risk_data, alpha=0.6, label=risk)
    plt.title('Risk Rating vs Overall Score')
    plt.xlabel('Risk Rating')
    plt.ylabel('Overall Score')
    
    plt.tight_layout()
    plt.savefig(f"{save_path}/analysis_overview.png", dpi=300, bbox_inches='tight')
    plt.close()
    
    # Sector Analysis
    if len(df['sector'].unique()) > 1:
        plt.figure(figsize=(14, 8))
        
        plt.subplot(2, 1, 1)
        sector_scores = df.groupby('sector')['overall_score'].mean().sort_values(ascending=False)
        sector_scores.plot(kind='bar')
        plt.title('Average Score by Sector')
        plt.xlabel('Sector')
        plt.ylabel('Average Score')
        plt.xticks(rotation=45)
        
        plt.subplot(2, 1, 2)
        sector_counts = df['sector'].value_counts()
        sector_counts.plot(kind='bar')
        plt.title('Number of Stocks by Sector')
        plt.xlabel('Sector')
        plt.ylabel('Count')
        plt.xticks(rotation=45)
        
        plt.tight_layout()
        plt.savefig(f"{save_path}/sector_analysis.png", dpi=300, bbox_inches='tight')
        plt.close()
    
    print(f"Charts saved to {save_path}/ directory")

def generate_portfolio_report(portfolio_summary: Dict, analysis_results: List[Dict] = None):
    """
    Generate a comprehensive portfolio report
    
    Args:
        portfolio_summary: Portfolio summary from PortfolioManager
        analysis_results: Optional analysis results for additional insights
    """
    report = f"""
    
    📊 PORTFOLIO ANALYSIS REPORT
    {'='*50}
    Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}
    
    💼 PORTFOLIO OVERVIEW
    {'-'*30}
    Total Portfolio Value: ${portfolio_summary.get('total_portfolio_value', 0):,.2f}
    Cash Balance: ${portfolio_summary.get('cash_balance', 0):,.2f}
    Positions Value: ${portfolio_summary.get('total_positions_value', 0):,.2f}
    Unrealized P&L: ${portfolio_summary.get('unrealized_pnl', 0):,.2f}
    Number of Positions: {portfolio_summary.get('positions_count', 0)}
    
    """
    
    # Top Holdings
    if portfolio_summary.get('top_holdings'):
        report += "🏆 TOP HOLDINGS\n"
        report += "-" * 30 + "\n"
        for i, holding in enumerate(portfolio_summary['top_holdings'], 1):
            report += f"{i}. {holding['symbol']}: ${holding['position_value']:,.2f} "
            report += f"({holding['quantity']} shares)\n"
        report += "\n"
    
    # Sector Allocation
    if portfolio_summary.get('sector_allocation'):
        report += "🎯 SECTOR ALLOCATION\n"
        report += "-" * 30 + "\n"
        total_value = sum(portfolio_summary['sector_allocation'].values())
        for sector, value in sorted(portfolio_summary['sector_allocation'].items(), 
                                  key=lambda x: x[1], reverse=True):
            percentage = (value / total_value) * 100 if total_value > 0 else 0
            report += f"{sector}: ${value:,.2f} ({percentage:.1f}%)\n"
        report += "\n"
    
    # Analysis Insights
    if analysis_results:
        report += "🔍 ANALYSIS INSIGHTS\n"
        report += "-" * 30 + "\n"
        
        scores = [r['analysis']['overall_score'] for r in analysis_results 
                 if r.get('analysis') and not r.get('error')]
        
        if scores:
            report += f"Average Analysis Score: {np.mean(scores):.1f}/100\n"
            report += f"Highest Score: {max(scores):.1f}\n"
            report += f"Lowest Score: {min(scores):.1f}\n"
            
            recommendations = [r['analysis']['recommendation'] for r in analysis_results 
                             if r.get('analysis') and not r.get('error')]
            
            buy_count = sum(1 for r in recommendations if r in ['BUY', 'STRONG_BUY'])
            hold_count = sum(1 for r in recommendations if r == 'HOLD')
            sell_count = sum(1 for r in recommendations if r in ['SELL', 'STRONG_SELL'])
            
            report += f"Buy Recommendations: {buy_count}\n"
            report += f"Hold Recommendations: {hold_count}\n"
            report += f"Sell Recommendations: {sell_count}\n"
    
    report += "\n" + "="*50 + "\n"
    
    return report

def backup_database(db_manager, backup_path: str = None):
    """
    Create a backup of the analysis database
    
    Args:
        db_manager: DatabaseManager instance
        backup_path: Path for backup file
    """
    if not backup_path:
        backup_path = f"backup_stock_analysis_{datetime.now().strftime('%Y%m%d_%H%M%S')}.sql"
    
    try:
        # This would implement database backup logic
        # For now, export key tables to CSV
        
        tables_to_backup = [
            'analysis_results',
            'portfolio_positions', 
            'trade_log',
            'stock_fundamentals'
        ]
        
        backup_dir = f"backup_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        os.makedirs(backup_dir, exist_ok=True)
        
        for table in tables_to_backup:
            try:
                query = f"SELECT * FROM {table}"
                df = pd.read_sql(query, con=db_manager.engine)
                df.to_csv(f"{backup_dir}/{table}.csv", index=False)
                print(f"Backed up {table}: {len(df)} records")
            except Exception as e:
                print(f"Error backing up {table}: {e}")
        
        print(f"Database backup completed to {backup_dir}/")
        
    except Exception as e:
        print(f"Error creating database backup: {e}")

def performance_metrics(trade_log_df: pd.DataFrame) -> Dict[str, Any]:
    """
    Calculate performance metrics from trade log
    
    Args:
        trade_log_df: DataFrame containing trade log data
        
    Returns:
        Dictionary with performance metrics
    """
    try:
        # Calculate basic metrics
        total_trades = len(trade_log_df)
        buy_trades = len(trade_log_df[trade_log_df['trade_type'] == 'BUY'])
        sell_trades = len(trade_log_df[trade_log_df['trade_type'] == 'SELL'])
        
        # Calculate P&L if data is available
        total_pnl = 0
        win_rate = 0
        avg_win = 0
        avg_loss = 0
        
        if 'pnl' in trade_log_df.columns:
            trades_with_pnl = trade_log_df[trade_log_df['pnl'].notna()]
            
            if not trades_with_pnl.empty:
                total_pnl = trades_with_pnl['pnl'].sum()
                winning_trades = trades_with_pnl[trades_with_pnl['pnl'] > 0]
                losing_trades = trades_with_pnl[trades_with_pnl['pnl'] < 0]
                
                win_rate = len(winning_trades) / len(trades_with_pnl) * 100
                avg_win = winning_trades['pnl'].mean() if not winning_trades.empty else 0
                avg_loss = losing_trades['pnl'].mean() if not losing_trades.empty else 0
        
        return {
            'total_trades': total_trades,
            'buy_trades': buy_trades,
            'sell_trades': sell_trades,
            'total_pnl': total_pnl,
            'win_rate': win_rate,
            'avg_win': avg_win,
            'avg_loss': avg_loss,
            'profit_factor': abs(avg_win / avg_loss) if avg_loss != 0 else 0
        }
        
    except Exception as e:
        print(f"Error calculating performance metrics: {e}")
        return {}

if __name__ == "__main__":
    print("Stock Analysis Extension Utilities")
    print("Available functions:")
    print("- create_sample_config(): Create sample configuration")
    print("- export_analysis_to_excel(): Export analysis to Excel")
    print("- create_analysis_charts(): Create visualization charts")
    print("- generate_portfolio_report(): Generate comprehensive report")
    print("- backup_database(): Backup database to CSV files")
    print("- performance_metrics(): Calculate trading performance metrics")
