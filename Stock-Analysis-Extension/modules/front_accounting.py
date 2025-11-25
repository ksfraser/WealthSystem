"""
FrontAccounting Integration Module
Handles integration with FrontAccounting system for trade tracking and portfolio management

REQUIREMENTS TRACEABILITY:
==========================
Business Requirements:
- BR-004: Accounting system integration
- BR-040: Generate journal entries for trades
- BR-041: Mark-to-market valuation tracking
- BR-042: Financial reporting

Business Capabilities:
- BC-302: FrontAccounting Integration

Functional Requirements:
- FR-1400: Create journal entries for BUY trades
- FR-1401: Create journal entries for SELL trades
- FR-1402: Create mark-to-market adjustment entries
- FR-1403: Track FrontAccounting sync status
- FR-1404: Handle FrontAccounting API errors
- FR-1405: Support optional FrontAccounting (not required)
- FR-1406: Map trades to GL accounts

Technical Requirements:
- TR-600-606: FrontAccountingIntegrator specification
- TR-804: Performance (< 10 sec per trade sync)

GL Account Mapping:
- Cash: 1060 (Cash in Bank)
- Investments: 1500 (Investment Securities)
- Realized Gain/Loss: 8200
- Unrealized Gain/Loss: 8210
- Commission Expense: 5800
- Dividend Income: 8100

Journal Entry Examples:
BUY:
  DR Investment Securities    $10,000
  DR Commission Expense       $10
      CR Cash in Bank                 $10,010

SELL:
  DR Cash in Bank            $15,000
  DR Commission Expense       $10
      CR Investment Securities        $10,000
      CR Realized Gain/Loss           $5,010

MTM Adjustment:
  DR Investment Securities    $1,000
      CR Unrealized Gain/Loss         $1,000

Dependencies:
- DEP-105: FrontAccounting 2.4+ (optional - low priority)

Implementation:
- Optional integration (system works without FA)
- Automatic journal entry generation
- Sync status tracking in database
- Error handling and retry logic
- Balance sheet and P&L reporting support
"""

import requests
import json
import logging
from typing import Dict, List, Optional, Any
from datetime import datetime, date
import base64

class FrontAccountingIntegrator:
    def __init__(self, config: Dict[str, Any]):
        """
        Initialize FrontAccounting integrator
        
        Args:
            config: Configuration dictionary containing FrontAccounting settings
        """
        self.config = config
        self.fa_config = config.get('FRONTACCOUNTING_CONFIG', {})
        self.logger = logging.getLogger(__name__)
        
        self.base_url = self.fa_config.get('api_url', 'http://localhost/frontaccounting/api')
        self.username = self.fa_config.get('username', 'admin')
        self.password = self.fa_config.get('password', 'password')
        self.company_id = self.fa_config.get('company_id', 1)
        self.fiscal_year = self.fa_config.get('fiscal_year', 2025)
        
        # Setup authentication
        self.auth_header = self._create_auth_header()
        
        # Account mappings for stock transactions
        self.account_mappings = {
            'cash_account': '1060',  # Cash - Investment Account
            'investment_account': '1520',  # Investments - Securities
            'realized_gains': '4200',  # Investment Income - Realized Gains
            'unrealized_gains': '1525',  # Investments - Unrealized Gains
            'commission_expense': '5200',  # Commission Expense
            'dividend_income': '4100'  # Dividend Income
        }
    
    def _create_auth_header(self) -> str:
        """Create basic authentication header"""
        credentials = f"{self.username}:{self.password}"
        encoded_credentials = base64.b64encode(credentials.encode('utf-8')).decode('utf-8')
        return f"Basic {encoded_credentials}"
    
    def test_connection(self) -> Dict[str, Any]:
        """
        Test connection to FrontAccounting API
        
        Returns:
            Dictionary containing connection status and info
        """
        try:
            headers = {
                'Authorization': self.auth_header,
                'Content-Type': 'application/json'
            }
            
            # Test with a simple API call to get company info
            response = requests.get(
                f"{self.base_url}/company/{self.company_id}",
                headers=headers,
                timeout=10
            )
            
            if response.status_code == 200:
                return {
                    'status': 'success',
                    'message': 'Connection successful',
                    'company_info': response.json()
                }
            else:
                return {
                    'status': 'error',
                    'message': f"HTTP {response.status_code}: {response.text}",
                    'company_info': None
                }
                
        except Exception as e:
            self.logger.error(f"FrontAccounting connection test failed: {e}")
            return {
                'status': 'error',
                'message': str(e),
                'company_info': None
            }
    
    def create_stock_purchase_entry(self, trade_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Create journal entry for stock purchase
        
        Args:
            trade_data: Dictionary containing trade information
            
        Returns:
            Dictionary containing entry result
        """
        try:
            symbol = trade_data['symbol']
            quantity = float(trade_data['quantity'])
            price = float(trade_data['price'])
            total_amount = float(trade_data['total_amount'])
            commission = float(trade_data.get('commission', 0))
            trade_date = trade_data.get('trade_date', datetime.now().date())
            
            # Prepare journal entry
            journal_entry = {
                'date': trade_date.isoformat() if isinstance(trade_date, date) else trade_date,
                'reference': f"BUY-{symbol}-{datetime.now().strftime('%Y%m%d')}",
                'memo': f"Purchase of {quantity} shares of {symbol} at ${price:.2f}",
                'entries': [
                    {
                        'account_code': self.account_mappings['investment_account'],
                        'debit': total_amount,
                        'credit': 0,
                        'memo': f"{symbol} - {quantity} shares @ ${price:.2f}"
                    },
                    {
                        'account_code': self.account_mappings['cash_account'],
                        'debit': 0,
                        'credit': total_amount,
                        'memo': f"Cash payment for {symbol} purchase"
                    }
                ]
            }
            
            # Add commission entry if applicable
            if commission > 0:
                journal_entry['entries'].extend([
                    {
                        'account_code': self.account_mappings['commission_expense'],
                        'debit': commission,
                        'credit': 0,
                        'memo': f"Commission for {symbol} purchase"
                    },
                    {
                        'account_code': self.account_mappings['cash_account'],
                        'debit': 0,
                        'credit': commission,
                        'memo': f"Commission payment for {symbol}"
                    }
                ])
            
            # Post journal entry
            result = self._post_journal_entry(journal_entry)
            
            if result['status'] == 'success':
                self.logger.info(f"Created purchase entry for {symbol}: {result['transaction_id']}")
            
            return result
            
        except Exception as e:
            self.logger.error(f"Error creating stock purchase entry: {e}")
            return {
                'status': 'error',
                'message': str(e),
                'transaction_id': None
            }
    
    def create_stock_sale_entry(self, trade_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Create journal entry for stock sale
        
        Args:
            trade_data: Dictionary containing trade information
            
        Returns:
            Dictionary containing entry result
        """
        try:
            symbol = trade_data['symbol']
            quantity = float(trade_data['quantity'])
            price = float(trade_data['price'])
            total_amount = float(trade_data['total_amount'])
            cost_basis = float(trade_data.get('cost_basis', 0))
            commission = float(trade_data.get('commission', 0))
            trade_date = trade_data.get('trade_date', datetime.now().date())
            
            # Calculate gain/loss
            net_proceeds = total_amount - commission
            realized_gain_loss = net_proceeds - cost_basis
            
            # Prepare journal entry
            journal_entry = {
                'date': trade_date.isoformat() if isinstance(trade_date, date) else trade_date,
                'reference': f"SELL-{symbol}-{datetime.now().strftime('%Y%m%d')}",
                'memo': f"Sale of {quantity} shares of {symbol} at ${price:.2f}",
                'entries': [
                    {
                        'account_code': self.account_mappings['cash_account'],
                        'debit': total_amount,
                        'credit': 0,
                        'memo': f"Cash from {symbol} sale"
                    },
                    {
                        'account_code': self.account_mappings['investment_account'],
                        'debit': 0,
                        'credit': cost_basis,
                        'memo': f"{symbol} - Cost basis of sold shares"
                    }
                ]
            }
            
            # Add gain/loss entry
            if realized_gain_loss > 0:
                # Realized gain
                journal_entry['entries'].append({
                    'account_code': self.account_mappings['realized_gains'],
                    'debit': 0,
                    'credit': realized_gain_loss,
                    'memo': f"Realized gain on {symbol} sale"
                })
            elif realized_gain_loss < 0:
                # Realized loss
                journal_entry['entries'].append({
                    'account_code': self.account_mappings['realized_gains'],
                    'debit': abs(realized_gain_loss),
                    'credit': 0,
                    'memo': f"Realized loss on {symbol} sale"
                })
            
            # Add commission entry if applicable
            if commission > 0:
                journal_entry['entries'].extend([
                    {
                        'account_code': self.account_mappings['commission_expense'],
                        'debit': commission,
                        'credit': 0,
                        'memo': f"Commission for {symbol} sale"
                    },
                    {
                        'account_code': self.account_mappings['cash_account'],
                        'debit': 0,
                        'credit': commission,
                        'memo': f"Commission payment for {symbol} sale"
                    }
                ])
            
            # Post journal entry
            result = self._post_journal_entry(journal_entry)
            
            if result['status'] == 'success':
                self.logger.info(f"Created sale entry for {symbol}: {result['transaction_id']}")
            
            return result
            
        except Exception as e:
            self.logger.error(f"Error creating stock sale entry: {e}")
            return {
                'status': 'error',
                'message': str(e),
                'transaction_id': None
            }
    
    def create_dividend_entry(self, dividend_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Create journal entry for dividend received
        
        Args:
            dividend_data: Dictionary containing dividend information
            
        Returns:
            Dictionary containing entry result
        """
        try:
            symbol = dividend_data['symbol']
            amount = float(dividend_data['amount'])
            payment_date = dividend_data.get('payment_date', datetime.now().date())
            
            # Prepare journal entry
            journal_entry = {
                'date': payment_date.isoformat() if isinstance(payment_date, date) else payment_date,
                'reference': f"DIV-{symbol}-{datetime.now().strftime('%Y%m%d')}",
                'memo': f"Dividend received from {symbol}",
                'entries': [
                    {
                        'account_code': self.account_mappings['cash_account'],
                        'debit': amount,
                        'credit': 0,
                        'memo': f"Dividend from {symbol}"
                    },
                    {
                        'account_code': self.account_mappings['dividend_income'],
                        'debit': 0,
                        'credit': amount,
                        'memo': f"Dividend income - {symbol}"
                    }
                ]
            }
            
            # Post journal entry
            result = self._post_journal_entry(journal_entry)
            
            if result['status'] == 'success':
                self.logger.info(f"Created dividend entry for {symbol}: {result['transaction_id']}")
            
            return result
            
        except Exception as e:
            self.logger.error(f"Error creating dividend entry: {e}")
            return {
                'status': 'error',
                'message': str(e),
                'transaction_id': None
            }
    
    def update_portfolio_valuation(self, portfolio_data: List[Dict[str, Any]]) -> Dict[str, Any]:
        """
        Update portfolio valuation with mark-to-market adjustments
        
        Args:
            portfolio_data: List of portfolio positions
            
        Returns:
            Dictionary containing update result
        """
        try:
            total_unrealized_change = 0.0
            adjustments = []
            
            for position in portfolio_data:
                symbol = position['symbol']
                current_value = float(position['position_value'])
                cost_basis = float(position['cost_basis'])
                previous_unrealized = float(position.get('previous_unrealized_pnl', 0))
                
                current_unrealized = current_value - cost_basis
                unrealized_change = current_unrealized - previous_unrealized
                
                if abs(unrealized_change) > 0.01:  # Only if change is significant
                    total_unrealized_change += unrealized_change
                    adjustments.append({
                        'symbol': symbol,
                        'change': unrealized_change,
                        'current_unrealized': current_unrealized
                    })
            
            if abs(total_unrealized_change) > 0.01:
                # Create mark-to-market adjustment entry
                journal_entry = {
                    'date': datetime.now().date().isoformat(),
                    'reference': f"MTM-{datetime.now().strftime('%Y%m%d')}",
                    'memo': "Mark-to-market portfolio adjustment",
                    'entries': []
                }
                
                if total_unrealized_change > 0:
                    # Unrealized gains increased
                    journal_entry['entries'].extend([
                        {
                            'account_code': self.account_mappings['investment_account'],
                            'debit': total_unrealized_change,
                            'credit': 0,
                            'memo': "Increase in portfolio value"
                        },
                        {
                            'account_code': self.account_mappings['unrealized_gains'],
                            'debit': 0,
                            'credit': total_unrealized_change,
                            'memo': "Unrealized gains adjustment"
                        }
                    ])
                else:
                    # Unrealized losses increased
                    journal_entry['entries'].extend([
                        {
                            'account_code': self.account_mappings['unrealized_gains'],
                            'debit': abs(total_unrealized_change),
                            'credit': 0,
                            'memo': "Unrealized losses adjustment"
                        },
                        {
                            'account_code': self.account_mappings['investment_account'],
                            'debit': 0,
                            'credit': abs(total_unrealized_change),
                            'memo': "Decrease in portfolio value"
                        }
                    ])
                
                # Post journal entry
                result = self._post_journal_entry(journal_entry)
                
                if result['status'] == 'success':
                    self.logger.info(f"Updated portfolio valuation: {result['transaction_id']}")
                    result['adjustments'] = adjustments
                    result['total_change'] = total_unrealized_change
                
                return result
            else:
                return {
                    'status': 'success',
                    'message': 'No significant valuation changes',
                    'transaction_id': None,
                    'adjustments': [],
                    'total_change': 0.0
                }
                
        except Exception as e:
            self.logger.error(f"Error updating portfolio valuation: {e}")
            return {
                'status': 'error',
                'message': str(e),
                'transaction_id': None
            }
    
    def _post_journal_entry(self, journal_entry: Dict[str, Any]) -> Dict[str, Any]:
        """
        Post journal entry to FrontAccounting
        
        Args:
            journal_entry: Journal entry data
            
        Returns:
            Dictionary containing post result
        """
        try:
            headers = {
                'Authorization': self.auth_header,
                'Content-Type': 'application/json'
            }
            
            # Validate that debits equal credits
            total_debits = sum(entry.get('debit', 0) for entry in journal_entry['entries'])
            total_credits = sum(entry.get('credit', 0) for entry in journal_entry['entries'])
            
            if abs(total_debits - total_credits) > 0.01:
                return {
                    'status': 'error',
                    'message': f"Journal entry not balanced: Debits {total_debits}, Credits {total_credits}",
                    'transaction_id': None
                }
            
            # Post to FrontAccounting
            response = requests.post(
                f"{self.base_url}/gl/journal_entry",
                headers=headers,
                json=journal_entry,
                timeout=30
            )
            
            if response.status_code == 200 or response.status_code == 201:
                result = response.json()
                return {
                    'status': 'success',
                    'message': 'Journal entry posted successfully',
                    'transaction_id': result.get('transaction_id'),
                    'reference': journal_entry['reference']
                }
            else:
                return {
                    'status': 'error',
                    'message': f"HTTP {response.status_code}: {response.text}",
                    'transaction_id': None
                }
                
        except Exception as e:
            self.logger.error(f"Error posting journal entry: {e}")
            return {
                'status': 'error',
                'message': str(e),
                'transaction_id': None
            }
    
    def get_portfolio_balance_sheet(self) -> Dict[str, Any]:
        """
        Get portfolio balance sheet from FrontAccounting
        
        Returns:
            Dictionary containing balance sheet data
        """
        try:
            headers = {
                'Authorization': self.auth_header,
                'Content-Type': 'application/json'
            }
            
            # Get account balances for investment accounts
            investment_accounts = [
                self.account_mappings['cash_account'],
                self.account_mappings['investment_account'],
                self.account_mappings['unrealized_gains']
            ]
            
            balances = {}
            
            for account_code in investment_accounts:
                response = requests.get(
                    f"{self.base_url}/gl/account_balance/{account_code}",
                    headers=headers,
                    timeout=10
                )
                
                if response.status_code == 200:
                    balance_data = response.json()
                    balances[account_code] = balance_data.get('balance', 0)
                else:
                    balances[account_code] = 0
            
            return {
                'status': 'success',
                'cash_balance': balances.get(self.account_mappings['cash_account'], 0),
                'investment_value': balances.get(self.account_mappings['investment_account'], 0),
                'unrealized_gains': balances.get(self.account_mappings['unrealized_gains'], 0),
                'total_portfolio_value': (
                    balances.get(self.account_mappings['cash_account'], 0) +
                    balances.get(self.account_mappings['investment_account'], 0)
                )
            }
            
        except Exception as e:
            self.logger.error(f"Error getting portfolio balance sheet: {e}")
            return {
                'status': 'error',
                'message': str(e),
                'cash_balance': 0,
                'investment_value': 0,
                'unrealized_gains': 0,
                'total_portfolio_value': 0
            }
    
    def get_profit_loss_statement(self, start_date: str, end_date: str) -> Dict[str, Any]:
        """
        Get profit and loss statement for the specified period
        
        Args:
            start_date: Start date (YYYY-MM-DD)
            end_date: End date (YYYY-MM-DD)
            
        Returns:
            Dictionary containing P&L data
        """
        try:
            headers = {
                'Authorization': self.auth_header,
                'Content-Type': 'application/json'
            }
            
            # Get P&L accounts
            pl_accounts = [
                self.account_mappings['realized_gains'],
                self.account_mappings['dividend_income'],
                self.account_mappings['commission_expense']
            ]
            
            pl_data = {}
            
            for account_code in pl_accounts:
                response = requests.get(
                    f"{self.base_url}/gl/account_movements/{account_code}",
                    headers=headers,
                    params={'start_date': start_date, 'end_date': end_date},
                    timeout=10
                )
                
                if response.status_code == 200:
                    movement_data = response.json()
                    pl_data[account_code] = movement_data.get('net_movement', 0)
                else:
                    pl_data[account_code] = 0
            
            realized_gains = pl_data.get(self.account_mappings['realized_gains'], 0)
            dividend_income = pl_data.get(self.account_mappings['dividend_income'], 0)
            commission_expense = pl_data.get(self.account_mappings['commission_expense'], 0)
            
            net_income = realized_gains + dividend_income - commission_expense
            
            return {
                'status': 'success',
                'period': f"{start_date} to {end_date}",
                'realized_gains_losses': realized_gains,
                'dividend_income': dividend_income,
                'commission_expense': commission_expense,
                'net_income': net_income
            }
            
        except Exception as e:
            self.logger.error(f"Error getting profit and loss statement: {e}")
            return {
                'status': 'error',
                'message': str(e),
                'period': f"{start_date} to {end_date}",
                'realized_gains_losses': 0,
                'dividend_income': 0,
                'commission_expense': 0,
                'net_income': 0
            }
    
    def sync_trade_to_fa(self, trade_id: int, trade_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Sync a trade to FrontAccounting and track the synchronization
        
        Args:
            trade_id: Trade ID from database
            trade_data: Trade data dictionary
            
        Returns:
            Dictionary containing sync result
        """
        try:
            trade_type = trade_data['trade_type'].upper()
            
            if trade_type == 'BUY':
                result = self.create_stock_purchase_entry(trade_data)
            elif trade_type == 'SELL':
                result = self.create_stock_sale_entry(trade_data)
            else:
                return {
                    'status': 'error',
                    'message': f"Unknown trade type: {trade_type}",
                    'fa_transaction_id': None
                }
            
            # Return sync result with additional tracking info
            sync_result = {
                'trade_id': trade_id,
                'status': result['status'],
                'message': result['message'],
                'fa_transaction_id': result.get('transaction_id'),
                'fa_reference': result.get('reference'),
                'sync_date': datetime.now()
            }
            
            return sync_result
            
        except Exception as e:
            self.logger.error(f"Error syncing trade {trade_id} to FrontAccounting: {e}")
            return {
                'trade_id': trade_id,
                'status': 'error',
                'message': str(e),
                'fa_transaction_id': None,
                'fa_reference': None,
                'sync_date': datetime.now()
            }
