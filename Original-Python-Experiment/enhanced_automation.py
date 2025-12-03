"""Enhanced Database-Enabled Automation Script

This module extends the original simple_automation.py with database functionality while maintaining
CSV compatibility. It supports multiple market cap categories and provides enhanced logging,
tracking, and analysis capabilities.

Key Features:
- Backward compatible with original CSV format
- Database storage for scalability and advanced analytics
- Support for multiple market cap categories
- Enhanced LLM interaction logging and analysis
- Performance tracking and strategy optimization
- Risk management with position sizing controls

Usage:
    from enhanced_automation import EnhancedAutomationEngine
    
    engine = EnhancedAutomationEngine(market_cap_category='blue-chip')
    engine.run_automated_trading_session()
"""

from __future__ import annotations

import sys
import os
from pathlib import Path
from datetime import datetime, timedelta
from typing import Dict, List, Any, Optional, Tuple, Union
import pandas as pd
import numpy as np
import json
import logging
import time
import re
from dataclasses import dataclass, asdict

# Import the original automation functions
sys.path.append(str(Path(__file__).parent))
from simple_automation import (
    call_openai_api,
    generate_trading_prompt,
    parse_llm_response,
    execute_automated_trades
)

# Import our enhanced trading engine
from enhanced_trading_script import EnhancedTradingEngine, create_trading_engine

# Database imports
try:
    import mysql.connector
    from mysql.connector import Error
    import yaml
    HAS_DB_DEPS = True
except ImportError:
    HAS_DB_DEPS = False

# Configure enhanced logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('enhanced_automation.log'),
        logging.StreamHandler()
    ]
)
enhanced_logger = logging.getLogger(__name__)


@dataclass
class TradingSession:
    """Data class to track trading session information."""
    session_id: str
    market_cap_category: str
    start_time: datetime
    end_time: Optional[datetime] = None
    total_trades: int = 0
    successful_trades: int = 0
    total_pnl: float = 0.0
    starting_cash: float = 0.0
    ending_cash: float = 0.0
    starting_equity: float = 0.0
    ending_equity: float = 0.0
    llm_interactions: int = 0
    errors: List[str] = None
    
    def __post_init__(self):
        if self.errors is None:
            self.errors = []
    
    def to_dict(self) -> Dict[str, Any]:
        """Convert to dictionary for JSON serialization."""
        result = asdict(self)
        result['start_time'] = self.start_time.isoformat()
        result['end_time'] = self.end_time.isoformat() if self.end_time else None
        return result


@dataclass
class LLMInteraction:
    """Data class to track LLM interactions."""
    session_id: str
    interaction_id: str
    timestamp: datetime
    market_cap_category: str
    prompt_type: str  # 'analysis', 'buy_sell', 'research', 'risk_assessment'
    prompt: str
    response: str
    tokens_used: Optional[int] = None
    response_time: Optional[float] = None
    action_taken: Optional[str] = None
    ticker_analyzed: Optional[str] = None
    
    def to_dict(self) -> Dict[str, Any]:
        """Convert to dictionary for JSON serialization."""
        result = asdict(self)
        result['timestamp'] = self.timestamp.isoformat()
        return result


class EnhancedAutomationEngine:
    """Enhanced automation engine with database support and multi-market-cap capability."""
    
    def __init__(self,
                 market_cap_category: str = 'micro',
                 data_dir: Optional[Path] = None,
                 enable_database: bool = True,
                 config_file: Optional[str] = None,
                 max_position_size: float = 0.1,  # Max 10% of portfolio per position
                 risk_tolerance: str = 'moderate',  # 'conservative', 'moderate', 'aggressive'
                 api_key: Optional[str] = None):  # OpenAI API key for LLM functionality
        """
        Initialize the enhanced automation engine.
        
        Args:
            market_cap_category: Market cap category ('micro', 'blue-chip', 'small', 'mid', 'large')
            data_dir: Directory for CSV files
            enable_database: Whether to enable database functionality
            config_file: Path to database configuration file
            max_position_size: Maximum position size as fraction of portfolio
            risk_tolerance: Risk tolerance level
            api_key: OpenAI API key for LLM interactions
        """
        self.market_cap_category = market_cap_category.lower()
        self.enable_database = enable_database and HAS_DB_DEPS
        self.max_position_size = max_position_size
        self.risk_tolerance = risk_tolerance
        self.api_key = api_key
        
        # Initialize enhanced trading engine
        self.trading_engine = create_trading_engine(
            market_cap_category=market_cap_category,
            data_dir=data_dir,
            enable_database=enable_database
        )
        
        # Set up session tracking
        self.current_session: Optional[TradingSession] = None
        self.llm_interactions: List[LLMInteraction] = []
        
        # Set up data directories
        self.automation_dir = Path(f"automation_{self.market_cap_category}_cap")
        self.automation_dir.mkdir(exist_ok=True)
        
        self.sessions_file = self.automation_dir / "trading_sessions.json"
        self.interactions_file = self.automation_dir / "llm_interactions.json"
        
        # Risk management parameters based on market cap category
        self.risk_params = self._get_risk_parameters()
        
        enhanced_logger.info(f"Enhanced Automation Engine initialized for {market_cap_category} cap category")
        enhanced_logger.info(f"Database enabled: {self.trading_engine.db_connected}")
        enhanced_logger.info(f"Risk tolerance: {risk_tolerance}, Max position size: {max_position_size:.1%}")
    
    def _get_risk_parameters(self) -> Dict[str, Any]:
        """Get risk management parameters based on market cap category and risk tolerance."""
        base_params = {
            'micro': {
                'conservative': {'stop_loss': 0.15, 'position_limit': 0.05, 'max_stocks': 15},
                'moderate': {'stop_loss': 0.20, 'position_limit': 0.08, 'max_stocks': 12},
                'aggressive': {'stop_loss': 0.25, 'position_limit': 0.12, 'max_stocks': 8}
            },
            'blue-chip': {
                'conservative': {'stop_loss': 0.10, 'position_limit': 0.15, 'max_stocks': 8},
                'moderate': {'stop_loss': 0.12, 'position_limit': 0.20, 'max_stocks': 6},
                'aggressive': {'stop_loss': 0.15, 'position_limit': 0.25, 'max_stocks': 5}
            },
            'small': {
                'conservative': {'stop_loss': 0.12, 'position_limit': 0.08, 'max_stocks': 12},
                'moderate': {'stop_loss': 0.15, 'position_limit': 0.10, 'max_stocks': 10},
                'aggressive': {'stop_loss': 0.18, 'position_limit': 0.15, 'max_stocks': 8}
            }
        }
        
        category = self.market_cap_category if self.market_cap_category in base_params else 'micro'
        return base_params[category].get(self.risk_tolerance, base_params[category]['moderate'])
    
    def start_trading_session(self) -> str:
        """Start a new trading session."""
        session_id = f"{self.market_cap_category}_{datetime.now().strftime('%Y%m%d_%H%M%S')}"
        
        # Load current portfolio state
        portfolio, cash = self.trading_engine.load_portfolio_state()
        starting_equity = cash + (portfolio['cost_basis'].sum() if not portfolio.empty else 0)
        
        self.current_session = TradingSession(
            session_id=session_id,
            market_cap_category=self.market_cap_category,
            start_time=datetime.now(),
            starting_cash=cash,
            starting_equity=starting_equity
        )
        
        enhanced_logger.info(f"Started trading session: {session_id}")
        enhanced_logger.info(f"Starting portfolio: {len(portfolio)} positions, ${cash:,.2f} cash, ${starting_equity:,.2f} equity")
        
        return session_id
    
    def end_trading_session(self):
        """End the current trading session."""
        if not self.current_session:
            enhanced_logger.warning("No active trading session to end")
            return
        
        # Load final portfolio state
        portfolio, cash = self.trading_engine.load_portfolio_state()
        ending_equity = cash + (portfolio['cost_basis'].sum() if not portfolio.empty else 0)
        
        self.current_session.end_time = datetime.now()
        self.current_session.ending_cash = cash
        self.current_session.ending_equity = ending_equity
        self.current_session.total_pnl = ending_equity - self.current_session.starting_equity
        
        # Save session data
        self._save_session_data()
        
        # Save to database if enabled
        if self.trading_engine.db_connected:
            self._save_session_to_database()
        
        enhanced_logger.info(f"Ended trading session: {self.current_session.session_id}")
        enhanced_logger.info(f"Session P&L: ${self.current_session.total_pnl:,.2f}")
        enhanced_logger.info(f"Total trades: {self.current_session.total_trades}")
        enhanced_logger.info(f"LLM interactions: {self.current_session.llm_interactions}")
        
        self.current_session = None
    
    def enhanced_ask_gpt(self, prompt: str, prompt_type: str = 'analysis', ticker: Optional[str] = None) -> str:
        """Enhanced GPT interaction with logging and database storage."""
        start_time = time.time()
        interaction_id = f"{datetime.now().strftime('%Y%m%d_%H%M%S_%f')}"
        
        try:
            # Use original call_openai_api function - need API key
            if not hasattr(self, 'api_key') or not self.api_key:
                raise ValueError("API key not configured for LLM interactions")
            response = call_openai_api(prompt, self.api_key)
            response_time = time.time() - start_time
            
            # Create interaction record
            interaction = LLMInteraction(
                session_id=self.current_session.session_id if self.current_session else 'no_session',
                interaction_id=interaction_id,
                timestamp=datetime.now(),
                market_cap_category=self.market_cap_category,
                prompt_type=prompt_type,
                prompt=prompt,
                response=response,
                response_time=response_time,
                ticker_analyzed=ticker
            )
            
            self.llm_interactions.append(interaction)
            
            if self.current_session:
                self.current_session.llm_interactions += 1
            
            # Save to database if enabled
            if self.trading_engine.db_connected:
                self._save_interaction_to_database(interaction)
            
            enhanced_logger.info(f"LLM interaction completed: {prompt_type} in {response_time:.2f}s")
            return response
            
        except Exception as e:
            enhanced_logger.error(f"LLM interaction failed: {e}")
            if self.current_session:
                self.current_session.errors.append(f"LLM error: {str(e)}")
            return f"Error: {str(e)}"
    
    def enhanced_portfolio_analysis(self, portfolio: pd.DataFrame, cash: float) -> Dict[str, Any]:
        """Enhanced portfolio analysis with market cap specific insights."""
        analysis = {}
        
        if portfolio.empty:
            analysis['status'] = 'empty'
            analysis['recommendations'] = f"Portfolio is empty. Consider starting with {self.market_cap_category} cap stocks."
            return analysis
        
        # Basic portfolio metrics
        total_equity = cash + portfolio['cost_basis'].sum()
        analysis['total_equity'] = total_equity
        analysis['cash_percentage'] = cash / total_equity
        analysis['position_count'] = len(portfolio)
        
        # Market cap specific analysis
        if self.market_cap_category == 'micro':
            analysis['concentration_risk'] = self._analyze_micro_cap_concentration(portfolio)
            analysis['liquidity_risk'] = self._analyze_liquidity_risk(portfolio)
        elif self.market_cap_category == 'blue-chip':
            analysis['sector_diversification'] = self._analyze_sector_diversification(portfolio)
            analysis['dividend_yield'] = self._analyze_dividend_potential(portfolio)
        
        # Risk analysis
        analysis['risk_metrics'] = self._calculate_risk_metrics(portfolio, total_equity)
        
        # Generate LLM-based analysis
        llm_prompt = self._create_portfolio_analysis_prompt(portfolio, cash, analysis)
        llm_analysis = self.enhanced_ask_gpt(llm_prompt, 'portfolio_analysis')
        analysis['llm_insights'] = llm_analysis
        
        return analysis
    
    def _analyze_micro_cap_concentration(self, portfolio: pd.DataFrame) -> Dict[str, Any]:
        """Analyze concentration risk for micro-cap portfolio."""
        if portfolio.empty:
            return {'risk_level': 'low', 'largest_position': 0}
        
        portfolio = portfolio.copy()
        total_value = portfolio['cost_basis'].sum()
        portfolio['weight'] = portfolio['cost_basis'] / total_value
        
        largest_position = portfolio['weight'].max()
        top_3_concentration = portfolio['weight'].nlargest(3).sum()
        
        risk_level = 'low'
        if largest_position > 0.15:
            risk_level = 'high'
        elif largest_position > 0.10 or top_3_concentration > 0.40:
            risk_level = 'medium'
        
        return {
            'risk_level': risk_level,
            'largest_position': largest_position,
            'top_3_concentration': top_3_concentration,
            'recommendations': self._get_concentration_recommendations(risk_level)
        }
    
    def _analyze_liquidity_risk(self, portfolio: pd.DataFrame) -> Dict[str, Any]:
        """Analyze liquidity risk for micro-cap stocks."""
        # This is a simplified analysis - in practice, you'd fetch volume data
        return {
            'risk_level': 'medium',  # Micro-caps generally have higher liquidity risk
            'recommendations': [
                'Monitor daily trading volumes',
                'Use limit orders instead of market orders',
                'Consider position sizing based on average daily volume'
            ]
        }
    
    def _analyze_sector_diversification(self, portfolio: pd.DataFrame) -> Dict[str, Any]:
        """Analyze sector diversification for blue-chip portfolio."""
        # This would require sector data - simplified for now
        return {
            'diversification_score': 'medium',
            'recommendations': [
                'Consider adding exposure to underrepresented sectors',
                'Monitor sector concentration risk',
                'Review sector rotation opportunities'
            ]
        }
    
    def _analyze_dividend_potential(self, portfolio: pd.DataFrame) -> Dict[str, Any]:
        """Analyze dividend potential for blue-chip stocks."""
        return {
            'estimated_yield': 'N/A',  # Would require dividend data
            'recommendations': [
                'Consider dividend-paying blue chips for income',
                'Monitor dividend sustainability',
                'Balance growth and income objectives'
            ]
        }
    
    def _calculate_risk_metrics(self, portfolio: pd.DataFrame, total_equity: float) -> Dict[str, Any]:
        """Calculate portfolio risk metrics."""
        if portfolio.empty:
            return {'position_risk': 'low', 'diversification': 'N/A'}
        
        # Position sizing risk
        max_position = (portfolio['cost_basis'].max() / total_equity) if total_equity > 0 else 0
        position_risk = 'low'
        if max_position > self.risk_params['position_limit']:
            position_risk = 'high'
        elif max_position > self.risk_params['position_limit'] * 0.8:
            position_risk = 'medium'
        
        # Diversification score
        position_count = len(portfolio)
        optimal_positions = self.risk_params['max_stocks']
        diversification = 'good' if position_count >= optimal_positions * 0.7 else 'limited'
        
        return {
            'position_risk': position_risk,
            'max_position_size': max_position,
            'diversification': diversification,
            'position_count': position_count,
            'optimal_range': f"{int(optimal_positions * 0.7)}-{optimal_positions}"
        }
    
    def _get_concentration_recommendations(self, risk_level: str) -> List[str]:
        """Get recommendations based on concentration risk level."""
        if risk_level == 'high':
            return [
                'Consider reducing largest position size',
                'Diversify into additional stocks',
                'Implement position size limits'
            ]
        elif risk_level == 'medium':
            return [
                'Monitor position sizes carefully',
                'Consider rebalancing if positions grow significantly',
                'Maintain diversification discipline'
            ]
        else:
            return [
                'Concentration risk is well managed',
                'Continue monitoring position sizes',
                'Maintain current diversification approach'
            ]
    
    def _create_portfolio_analysis_prompt(self, portfolio: pd.DataFrame, cash: float, analysis: Dict[str, Any]) -> str:
        """Create enhanced prompt for portfolio analysis."""
        portfolio_text = format_portfolio_for_llm(portfolio, cash)
        
        prompt = f"""
        Market Cap Category: {self.market_cap_category.upper()}
        Risk Tolerance: {self.risk_tolerance.upper()}
        
        Current Portfolio:
        {portfolio_text}
        
        Portfolio Metrics:
        - Total Equity: ${analysis['total_equity']:,.2f}
        - Cash Percentage: {analysis['cash_percentage']:.1%}
        - Position Count: {analysis['position_count']}
        - Risk Level: {analysis.get('risk_metrics', {}).get('position_risk', 'unknown')}
        
        Please provide a comprehensive analysis considering:
        1. {self.market_cap_category} cap specific opportunities and risks
        2. Current market conditions and trends
        3. Portfolio diversification and risk management
        4. Specific recommendations for {self.risk_tolerance} risk tolerance
        5. Potential entry/exit points for existing positions
        
        Focus on actionable insights for {self.market_cap_category} cap investing.
        """
        
        return prompt
    
    def enhanced_buy_sell_decision(self, ticker: str, current_data: Dict[str, Any]) -> Dict[str, Any]:
        """Enhanced buy/sell decision with risk management."""
        # Load current portfolio
        portfolio, cash = self.trading_engine.load_portfolio_state()
        total_equity = cash + (portfolio['cost_basis'].sum() if not portfolio.empty else 0)
        
        # Check position limits
        current_position = portfolio[portfolio['ticker'] == ticker]['cost_basis'].sum() if not portfolio.empty else 0
        current_weight = current_position / total_equity if total_equity > 0 else 0
        
        # Risk assessment
        risk_assessment = self._assess_trade_risk(ticker, current_data, portfolio, cash)
        
        # Create enhanced prompt
        enhanced_prompt = self._create_enhanced_buy_sell_prompt(
            ticker, current_data, portfolio, cash, risk_assessment
        )
        
        # Get LLM decision
        llm_response = self.enhanced_ask_gpt(enhanced_prompt, 'buy_sell', ticker)
        
        # Parse and enhance decision
        decision = self._parse_buy_sell_decision(llm_response, risk_assessment)
        
        return decision
    
    def _assess_trade_risk(self, ticker: str, current_data: Dict[str, Any], 
                          portfolio: pd.DataFrame, cash: float) -> Dict[str, Any]:
        """Assess risk for a potential trade."""
        total_equity = cash + (portfolio['cost_basis'].sum() if not portfolio.empty else 0)
        
        # Current position analysis
        current_position = portfolio[portfolio['ticker'] == ticker]['cost_basis'].sum() if not portfolio.empty else 0
        current_weight = current_position / total_equity if total_equity > 0 else 0
        
        # Position size limits
        max_additional = self.risk_params['position_limit'] - current_weight
        max_cash_allocation = cash * self.max_position_size
        
        # Market cap specific risks
        market_cap_risks = self._get_market_cap_risks(ticker)
        
        return {
            'current_weight': current_weight,
            'max_additional_weight': max_additional,
            'max_cash_allocation': max_cash_allocation,
            'position_limit_reached': current_weight >= self.risk_params['position_limit'],
            'portfolio_diversification': len(portfolio) < self.risk_params['max_stocks'],
            'market_cap_risks': market_cap_risks,
            'stop_loss_level': self.risk_params['stop_loss']
        }
    
    def _get_market_cap_risks(self, ticker: str) -> List[str]:
        """Get market cap specific risks."""
        if self.market_cap_category == 'micro':
            return [
                'High volatility risk',
                'Limited liquidity',
                'Higher business failure risk',
                'Less analyst coverage',
                'Potential for manipulation'
            ]
        elif self.market_cap_category == 'blue-chip':
            return [
                'Large market moves impact',
                'Dividend sustainability risk',
                'Slower growth potential',
                'Market saturation',
                'Regulatory scrutiny'
            ]
        else:
            return ['Market volatility', 'Sector concentration', 'Economic sensitivity']
    
    def _create_enhanced_buy_sell_prompt(self, ticker: str, current_data: Dict[str, Any],
                                       portfolio: pd.DataFrame, cash: float, 
                                       risk_assessment: Dict[str, Any]) -> str:
        """Create enhanced buy/sell prompt with risk context."""
        portfolio_text = format_portfolio_for_llm(portfolio, cash)
        
        prompt = f"""
        ENHANCED {self.market_cap_category.upper()} CAP TRADING ANALYSIS
        
        Ticker: {ticker}
        Market Cap Category: {self.market_cap_category}
        Risk Tolerance: {self.risk_tolerance}
        
        Current Portfolio:
        {portfolio_text}
        
        Risk Assessment:
        - Current position weight: {risk_assessment['current_weight']:.1%}
        - Position limit: {self.risk_params['position_limit']:.1%}
        - Available for new position: {risk_assessment['max_additional_weight']:.1%}
        - Stop loss level: {risk_assessment['stop_loss_level']:.1%}
        - Portfolio diversification: {'Good' if risk_assessment['portfolio_diversification'] else 'Limited'}
        
        Market Cap Specific Risks:
        {chr(10).join('- ' + risk for risk in risk_assessment['market_cap_risks'])}
        
        Current Data for {ticker}:
        {json.dumps(current_data, indent=2)}
        
        Please provide a trading decision with the following format:
        ACTION: [BUY/SELL/HOLD]
        REASONING: [Detailed reasoning specific to {self.market_cap_category} cap investing]
        RISK_FACTORS: [Key risks to consider]
        POSITION_SIZE: [Suggested position size in dollars or percentage]
        STOP_LOSS: [Suggested stop loss level]
        TARGET_PRICE: [Potential target price]
        CONFIDENCE: [High/Medium/Low]
        
        Consider:
        1. {self.market_cap_category} cap specific factors
        2. Current position limits and diversification
        3. Risk tolerance of {self.risk_tolerance}
        4. Market conditions and trends
        5. Technical and fundamental analysis
        """
        
        return prompt
    
    def _parse_buy_sell_decision(self, llm_response: str, risk_assessment: Dict[str, Any]) -> Dict[str, Any]:
        """Parse and enhance LLM buy/sell decision."""
        decision = {
            'action': 'HOLD',
            'reasoning': llm_response,
            'confidence': 'medium',
            'risk_score': 'medium',
            'position_size': 0,
            'stop_loss': 0,
            'target_price': 0,
            'risk_adjusted': False
        }
        
        # Parse action
        action_match = re.search(r'ACTION:\s*(BUY|SELL|HOLD)', llm_response, re.IGNORECASE)
        if action_match:
            decision['action'] = action_match.group(1).upper()
        
        # Parse confidence
        confidence_match = re.search(r'CONFIDENCE:\s*(HIGH|MEDIUM|LOW)', llm_response, re.IGNORECASE)
        if confidence_match:
            decision['confidence'] = confidence_match.group(1).lower()
        
        # Parse position size
        position_match = re.search(r'POSITION_SIZE:\s*\$?(\d+(?:,\d{3})*(?:\.\d{2})?)', llm_response)
        if position_match:
            decision['position_size'] = float(position_match.group(1).replace(',', ''))
        
        # Risk adjustment
        if decision['action'] == 'BUY':
            if risk_assessment['position_limit_reached']:
                decision['action'] = 'HOLD'
                decision['reasoning'] += '\n\nRISK OVERRIDE: Position limit reached.'
                decision['risk_adjusted'] = True
            elif decision['position_size'] > risk_assessment['max_cash_allocation']:
                decision['position_size'] = risk_assessment['max_cash_allocation']
                decision['risk_adjusted'] = True
        
        return decision
    
    def run_automated_trading_session(self, max_trades: int = 3, session_duration_hours: float = 1.0) -> Dict[str, Any]:
        """Run an automated trading session with enhanced controls."""
        session_id = self.start_trading_session()
        session_results = {'session_id': session_id, 'trades': [], 'errors': []}
        
        try:
            start_time = time.time()
            end_time = start_time + (session_duration_hours * 3600)
            
            enhanced_logger.info(f"Starting automated session for {session_duration_hours} hours, max {max_trades} trades")
            
            while time.time() < end_time and self.current_session.total_trades < max_trades:
                try:
                    # Load current portfolio
                    portfolio, cash = self.trading_engine.load_portfolio_state()
                    
                    # Portfolio analysis
                    analysis = self.enhanced_portfolio_analysis(portfolio, cash)
                    
                    # Get LLM recommendations for new opportunities
                    opportunity_prompt = self._create_opportunity_prompt(portfolio, cash, analysis)
                    opportunities = self.enhanced_ask_gpt(opportunity_prompt, 'opportunity_analysis')
                    
                    # Parse opportunities and execute trades
                    potential_trades = self._parse_trading_opportunities(opportunities)
                    
                    for trade in potential_trades:
                        if self.current_session.total_trades >= max_trades:
                            break
                            
                        ticker = trade.get('ticker')
                        if ticker:
                            # Get current data
                            current_data = get_latest_financial_data(ticker)
                            
                            # Make decision
                            decision = self.enhanced_buy_sell_decision(ticker, current_data)
                            
                            # Execute if buy decision
                            if decision['action'] == 'BUY' and decision['position_size'] > 0:
                                success = self._execute_trade(ticker, decision)
                                
                                session_results['trades'].append({
                                    'ticker': ticker,
                                    'action': decision['action'],
                                    'success': success,
                                    'decision': decision
                                })
                                
                                if success:
                                    self.current_session.successful_trades += 1
                                
                                self.current_session.total_trades += 1
                    
                    # Wait before next iteration
                    time.sleep(60)  # Wait 1 minute
                    
                except Exception as e:
                    error_msg = f"Error in trading loop: {str(e)}"
                    enhanced_logger.error(error_msg)
                    session_results['errors'].append(error_msg)
                    if self.current_session:
                        self.current_session.errors.append(error_msg)
            
        except Exception as e:
            error_msg = f"Session error: {str(e)}"
            enhanced_logger.error(error_msg)
            session_results['errors'].append(error_msg)
        
        finally:
            self.end_trading_session()
        
        enhanced_logger.info(f"Automated session completed: {len(session_results['trades'])} trades attempted")
        return session_results
    
    def _create_opportunity_prompt(self, portfolio: pd.DataFrame, cash: float, analysis: Dict[str, Any]) -> str:
        """Create prompt for identifying trading opportunities."""
        portfolio_text = format_portfolio_for_llm(portfolio, cash)
        
        prompt = f"""
        TRADING OPPORTUNITY ANALYSIS - {self.market_cap_category.upper()} CAP
        
        Current Portfolio:
        {portfolio_text}
        
        Portfolio Analysis:
        {json.dumps(analysis, indent=2, default=str)}
        
        Risk Parameters:
        - Maximum position size: {self.risk_params['position_limit']:.1%}
        - Maximum positions: {self.risk_params['max_stocks']}
        - Stop loss level: {self.risk_params['stop_loss']:.1%}
        - Risk tolerance: {self.risk_tolerance}
        
        Please identify 1-3 potential {self.market_cap_category} cap trading opportunities.
        
        For each opportunity, provide:
        TICKER: [Stock symbol]
        RATIONALE: [Why this fits our {self.market_cap_category} cap strategy]
        RISK_LEVEL: [HIGH/MEDIUM/LOW]
        POTENTIAL_SIZE: [Suggested position size in dollars]
        
        Focus on:
        1. Stocks that complement current portfolio
        2. {self.market_cap_category} cap specific opportunities
        3. Current market conditions
        4. Risk-adjusted potential returns
        5. Liquidity considerations
        """
        
        return prompt
    
    def _parse_trading_opportunities(self, opportunities_text: str) -> List[Dict[str, Any]]:
        """Parse trading opportunities from LLM response."""
        opportunities = []
        
        # Simple regex parsing - could be enhanced with more sophisticated NLP
        ticker_matches = re.findall(r'TICKER:\s*([A-Z]{1,5})', opportunities_text)
        
        for ticker in ticker_matches[:3]:  # Limit to 3 opportunities
            opportunities.append({
                'ticker': ticker,
                'source': 'llm_analysis'
            })
        
        return opportunities
    
    def _execute_trade(self, ticker: str, decision: Dict[str, Any]) -> bool:
        """Execute a trade based on decision."""
        try:
            if decision['action'] == 'BUY' and decision['position_size'] > 0:
                # Get current price
                current_data = get_latest_financial_data(ticker)
                current_price = current_data.get('price', 0)
                
                if current_price > 0:
                    shares = decision['position_size'] / current_price
                    
                    # Log the trade
                    success = self.trading_engine.log_trade(
                        ticker=ticker,
                        action='BUY',
                        shares=shares,
                        price=current_price,
                        reason=decision['reasoning'][:500]  # Truncate for database
                    )
                    
                    if success:
                        enhanced_logger.info(f"Trade executed: BUY {shares:.2f} {ticker} @ ${current_price:.2f}")
                        return True
                    
        except Exception as e:
            enhanced_logger.error(f"Trade execution failed: {e}")
            
        return False
    
    def _save_session_data(self):
        """Save session data to JSON files."""
        if not self.current_session:
            return
            
        # Save session
        sessions = []
        if self.sessions_file.exists():
            with open(self.sessions_file, 'r') as f:
                sessions = json.load(f)
        
        sessions.append(self.current_session.to_dict())
        
        with open(self.sessions_file, 'w') as f:
            json.dump(sessions, f, indent=2, default=str)
        
        # Save interactions
        interactions = []
        if self.interactions_file.exists():
            with open(self.interactions_file, 'r') as f:
                interactions = json.load(f)
        
        interactions.extend([interaction.to_dict() for interaction in self.llm_interactions])
        
        with open(self.interactions_file, 'w') as f:
            json.dump(interactions, f, indent=2, default=str)
    
    def _save_session_to_database(self):
        """Save session data to database."""
        if not self.trading_engine.db_connected or not self.current_session:
            return
            
        try:
            cursor = self.trading_engine.db.legacy_connection.cursor()
            
            # Save session
            session_query = """
                INSERT INTO trading_sessions 
                (session_id, market_cap_category, start_time, end_time, total_trades, 
                 successful_trades, total_pnl, starting_cash, ending_cash, 
                 starting_equity, ending_equity, llm_interactions)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """
            
            cursor.execute(session_query, (
                self.current_session.session_id,
                self.current_session.market_cap_category,
                self.current_session.start_time,
                self.current_session.end_time,
                self.current_session.total_trades,
                self.current_session.successful_trades,
                self.current_session.total_pnl,
                self.current_session.starting_cash,
                self.current_session.ending_cash,
                self.current_session.starting_equity,
                self.current_session.ending_equity,
                self.current_session.llm_interactions
            ))
            
            self.trading_engine.db.legacy_connection.commit()
            cursor.close()
            
            enhanced_logger.info("Session data saved to database")
            
        except Error as e:
            enhanced_logger.error(f"Failed to save session to database: {e}")
    
    def _save_interaction_to_database(self, interaction: LLMInteraction):
        """Save LLM interaction to database."""
        if not self.trading_engine.db_connected:
            return
            
        try:
            cursor = self.trading_engine.db.legacy_connection.cursor()
            
            query = """
                INSERT INTO llm_interactions 
                (session_id, interaction_id, timestamp, market_cap_category, prompt_type, 
                 prompt, response, tokens_used, response_time, action_taken, ticker_analyzed)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
            """
            
            cursor.execute(query, (
                interaction.session_id,
                interaction.interaction_id,
                interaction.timestamp,
                interaction.market_cap_category,
                interaction.prompt_type,
                interaction.prompt[:1000],  # Truncate for database
                interaction.response[:2000],  # Truncate for database
                interaction.tokens_used,
                interaction.response_time,
                interaction.action_taken,
                interaction.ticker_analyzed
            ))
            
            self.trading_engine.db.legacy_connection.commit()
            cursor.close()
            
        except Error as e:
            enhanced_logger.error(f"Failed to save interaction to database: {e}")
    
    def get_session_history(self, limit: int = 10) -> List[Dict[str, Any]]:
        """Get recent trading session history."""
        if self.sessions_file.exists():
            with open(self.sessions_file, 'r') as f:
                sessions = json.load(f)
            return sessions[-limit:]
        return []
    
    def get_performance_metrics(self, days: int = 30) -> Dict[str, Any]:
        """Get performance metrics for the last N days."""
        metrics = {
            'total_sessions': 0,
            'total_trades': 0,
            'success_rate': 0,
            'average_pnl': 0,
            'best_session': 0,
            'worst_session': 0
        }
        
        if not self.sessions_file.exists():
            return metrics
        
        with open(self.sessions_file, 'r') as f:
            sessions = json.load(f)
        
        # Filter sessions from last N days
        cutoff_date = datetime.now() - timedelta(days=days)
        recent_sessions = [
            s for s in sessions 
            if datetime.fromisoformat(s['start_time']) > cutoff_date
        ]
        
        if recent_sessions:
            metrics['total_sessions'] = len(recent_sessions)
            metrics['total_trades'] = sum(s['total_trades'] for s in recent_sessions)
            
            if metrics['total_trades'] > 0:
                successful_trades = sum(s['successful_trades'] for s in recent_sessions)
                metrics['success_rate'] = successful_trades / metrics['total_trades']
            
            pnls = [s['total_pnl'] for s in recent_sessions if s['total_pnl'] is not None]
            if pnls:
                metrics['average_pnl'] = sum(pnls) / len(pnls)
                metrics['best_session'] = max(pnls)
                metrics['worst_session'] = min(pnls)
        
        return metrics


# Factory functions for different market categories
def create_micro_cap_automation(data_dir: Optional[str] = None, 
                               risk_tolerance: str = 'moderate') -> EnhancedAutomationEngine:
    """Create automation engine for micro-cap stocks."""
    return EnhancedAutomationEngine('micro', Path(data_dir) if data_dir else None, 
                                  risk_tolerance=risk_tolerance)

def create_blue_chip_automation(data_dir: Optional[str] = None,
                               risk_tolerance: str = 'conservative') -> EnhancedAutomationEngine:
    """Create automation engine for blue-chip stocks."""
    return EnhancedAutomationEngine('blue-chip', Path(data_dir) if data_dir else None,
                                  risk_tolerance=risk_tolerance)

def create_small_cap_automation(data_dir: Optional[str] = None,
                               risk_tolerance: str = 'moderate') -> EnhancedAutomationEngine:
    """Create automation engine for small-cap stocks."""
    return EnhancedAutomationEngine('small', Path(data_dir) if data_dir else None,
                                  risk_tolerance=risk_tolerance)


if __name__ == "__main__":
    # Example usage
    print("Enhanced Automation Script - Database + CSV Integration")
    print("Available market cap categories: micro, blue-chip, small, mid, large")
    
    # Example for micro-cap
    micro_automation = create_micro_cap_automation(risk_tolerance='moderate')
    print(f"Micro-cap automation engine created with moderate risk tolerance")
    
    # Example session
    # results = micro_automation.run_automated_trading_session(max_trades=2, session_duration_hours=0.5)
    # print(f"Session results: {results}")
    
    # Performance metrics
    metrics = micro_automation.get_performance_metrics(days=30)
    print(f"30-day performance metrics: {metrics}")
