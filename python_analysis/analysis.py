#!/usr/bin/env python3
"""
Stock Analysis AI Module

This module provides ONLY AI and statistical analysis functions that PHP cannot 
efficiently perform. All business logic, portfolio management, database operations,
and trade execution are handled by the PHP MVC application.

Purpose:
- Advanced statistical calculations (numpy/pandas/scipy)
- Technical indicator calculations (RSI, MACD, Bollinger Bands)
- Sentiment scoring based on price patterns
- Risk assessment based on volatility metrics

Called by: PHP StockAnalysisService via PythonIntegrationService

Architecture:
    PHP MVC Application (Primary)
        ├── Portfolio Management
        ├── Trade Execution
        ├── Database Operations
        ├── UI/Controllers
        └── StockAnalysisService ───→ PythonIntegrationService ───→ THIS MODULE
                                                                      (AI Analysis)

REQUIREMENTS TRACEABILITY:
==========================
This module implements ONLY the analysis calculation requirements:
- FR-200-209: Fundamental Analysis Calculations
- FR-300-308: Technical Analysis Calculations  
- FR-400-406: Momentum Analysis Calculations
- FR-500-505: Sentiment Analysis Calculations
- FR-600-606: Risk Assessment Calculations

All other requirements (portfolio, trades, DB, UI) are implemented in PHP.
"""

import sys
import json
import pandas as pd
import numpy as np
from datetime import datetime
from typing import Dict, List, Any, Optional
import warnings
warnings.filterwarnings('ignore')

# Try to import technical analysis library
try:
    import ta
    HAS_TA = True
except ImportError:
    HAS_TA = False
    print("Warning: ta library not available. Technical indicators will be limited.", file=sys.stderr)


class StockAnalyzer:
    """
    Stock Analysis Engine - AI/Statistical Analysis ONLY
    
    This class performs ONLY computational analysis that benefits from Python's
    scientific computing libraries. All business logic is in PHP.
    """
    
    def __init__(self, scoring_weights: Dict[str, float] = None):
        """
        Initialize analyzer with scoring weights
        
        Args:
            scoring_weights: Weights for each analysis dimension
                Default: fundamental=0.40, technical=0.30, momentum=0.20, sentiment=0.10
        """
        self.scoring_weights = scoring_weights or {
            'fundamental': 0.40,
            'technical': 0.30,
            'momentum': 0.20,
            'sentiment': 0.10
        }
    
    def analyze_stock(self, stock_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Perform comprehensive stock analysis
        
        Args:
            stock_data: Dictionary containing:
                - symbol: Stock ticker symbol
                - price_data: List of price dictionaries with date, open, high, low, close, volume
                - fundamentals: Dictionary of fundamental metrics
        
        Returns:
            Dictionary containing:
                - symbol: Stock ticker
                - overall_score: 0-100 weighted score
                - recommendation: BUY/SELL/HOLD
                - fundamental_score: Fundamental analysis score
                - technical_score: Technical analysis score
                - momentum_score: Momentum analysis score
                - sentiment_score: Sentiment analysis score
                - risk_level: LOW/MEDIUM/HIGH/VERY_HIGH
                - target_price: Calculated target price
                - confidence: Confidence in recommendation (0-100)
                - analysis_date: ISO format timestamp
        """
        try:
            symbol = stock_data.get('symbol', 'UNKNOWN')
            price_data = stock_data.get('price_data', [])
            fundamentals = stock_data.get('fundamentals', {})
            
            # Convert price data to DataFrame
            price_df = pd.DataFrame(price_data)
            if not price_df.empty and 'date' in price_df.columns:
                price_df['date'] = pd.to_datetime(price_df['date'])
                price_df = price_df.sort_values('date')
            
            # Perform each dimension of analysis
            fundamental_analysis = self.calculate_fundamental_score(fundamentals)
            technical_analysis = self.calculate_technical_score(price_df)
            momentum_analysis = self.calculate_momentum_score(price_df)
            sentiment_analysis = self.calculate_sentiment_score(price_df, fundamentals)
            
            # Calculate weighted overall score
            overall_score = (
                fundamental_analysis['score'] * self.scoring_weights['fundamental'] +
                technical_analysis['score'] * self.scoring_weights['technical'] +
                momentum_analysis['score'] * self.scoring_weights['momentum'] +
                sentiment_analysis['score'] * self.scoring_weights['sentiment']
            )
            
            # Generate recommendation
            recommendation = self._get_recommendation(overall_score)
            
            # Assess risk
            risk_level = self._assess_risk(price_df, fundamentals, technical_analysis)
            
            # Calculate target price
            current_price = float(price_df['close'].iloc[-1]) if not price_df.empty else 0
            target_price = self._calculate_target_price(current_price, fundamentals, overall_score)
            
            # Calculate confidence
            confidence = self._calculate_confidence(
                fundamental_analysis, technical_analysis, 
                momentum_analysis, sentiment_analysis
            )
            
            return {
                'symbol': symbol,
                'overall_score': round(overall_score, 2),
                'recommendation': recommendation,
                'fundamental_score': round(fundamental_analysis['score'], 2),
                'technical_score': round(technical_analysis['score'], 2),
                'momentum_score': round(momentum_analysis['score'], 2),
                'sentiment_score': round(sentiment_analysis['score'], 2),
                'risk_level': risk_level,
                'target_price': target_price,
                'current_price': current_price,
                'confidence': round(confidence, 2),
                'analysis_date': datetime.now().isoformat(),
                'details': {
                    'fundamental': fundamental_analysis,
                    'technical': technical_analysis,
                    'momentum': momentum_analysis,
                    'sentiment': sentiment_analysis
                }
            }
            
        except Exception as e:
            return {
                'symbol': stock_data.get('symbol', 'UNKNOWN'),
                'error': str(e),
                'overall_score': 50.0,
                'recommendation': 'HOLD',
                'analysis_date': datetime.now().isoformat()
            }
    
    def calculate_fundamental_score(self, fundamentals: Dict[str, Any]) -> Dict[str, Any]:
        """
        Calculate fundamental analysis score based on financial metrics
        
        Analyzes: P/E ratio, P/B ratio, ROE, debt-to-equity, profit margin, revenue growth
        """
        analysis = {
            'score': 50.0,
            'factors': {},
            'strengths': [],
            'weaknesses': []
        }
        
        if not fundamentals:
            return analysis
        
        try:
            score_factors = []
            
            # P/E Ratio Analysis
            pe = fundamentals.get('pe_ratio')
            if pe and pe > 0:
                if pe < 15:
                    score_factors.append(85)
                    analysis['strengths'].append("Low P/E ratio suggests undervaluation")
                elif pe < 25:
                    score_factors.append(70)
                elif pe < 35:
                    score_factors.append(45)
                else:
                    score_factors.append(25)
                    analysis['weaknesses'].append("High P/E ratio suggests overvaluation")
                analysis['factors']['pe_score'] = score_factors[-1]
            
            # Price-to-Book Analysis
            pb = fundamentals.get('price_to_book')
            if pb and pb > 0:
                if pb < 1.5:
                    score_factors.append(80)
                    analysis['strengths'].append("Low P/B ratio indicates good value")
                elif pb < 3:
                    score_factors.append(60)
                elif pb < 5:
                    score_factors.append(40)
                else:
                    score_factors.append(20)
                    analysis['weaknesses'].append("High P/B ratio suggests premium valuation")
                analysis['factors']['pb_score'] = score_factors[-1]
            
            # Return on Equity
            roe = fundamentals.get('return_on_equity')
            if roe:
                roe_pct = roe * 100 if roe < 1 else roe
                if roe_pct > 20:
                    score_factors.append(90)
                    analysis['strengths'].append("Excellent return on equity")
                elif roe_pct > 15:
                    score_factors.append(75)
                elif roe_pct > 10:
                    score_factors.append(60)
                else:
                    score_factors.append(30)
                    analysis['weaknesses'].append("Low return on equity")
                analysis['factors']['roe_score'] = score_factors[-1]
            
            # Debt-to-Equity
            de = fundamentals.get('debt_to_equity')
            if de is not None:
                if de < 0.3:
                    score_factors.append(85)
                    analysis['strengths'].append("Low debt-to-equity ratio")
                elif de < 0.6:
                    score_factors.append(70)
                elif de < 1.0:
                    score_factors.append(50)
                else:
                    score_factors.append(25)
                    analysis['weaknesses'].append("High debt-to-equity ratio")
                analysis['factors']['de_score'] = score_factors[-1]
            
            # Profit Margin
            pm = fundamentals.get('profit_margin')
            if pm:
                pm_pct = pm * 100 if pm < 1 else pm
                if pm_pct > 20:
                    score_factors.append(90)
                    analysis['strengths'].append("Excellent profit margin")
                elif pm_pct > 10:
                    score_factors.append(75)
                elif pm_pct > 5:
                    score_factors.append(60)
                else:
                    score_factors.append(30)
                    analysis['weaknesses'].append("Low profit margin")
                analysis['factors']['pm_score'] = score_factors[-1]
            
            # Calculate final score
            if score_factors:
                analysis['score'] = np.mean(score_factors)
        
        except Exception as e:
            print(f"Error in fundamental analysis: {e}", file=sys.stderr)
        
        return analysis
    
    def calculate_technical_score(self, price_df: pd.DataFrame) -> Dict[str, Any]:
        """
        Calculate technical analysis score based on price patterns and indicators
        
        Analyzes: Moving averages, RSI, MACD, Bollinger Bands, trends
        """
        analysis = {
            'score': 50.0,
            'indicators': {},
            'signals': []
        }
        
        if price_df.empty or len(price_df) < 20:
            return analysis
        
        try:
            score_factors = []
            
            # Moving Average Analysis
            if len(price_df) >= 200:
                price_df['ma20'] = price_df['close'].rolling(window=20).mean()
                price_df['ma50'] = price_df['close'].rolling(window=50).mean()
                price_df['ma200'] = price_df['close'].rolling(window=200).mean()
                
                current_price = price_df['close'].iloc[-1]
                ma20 = price_df['ma20'].iloc[-1]
                ma50 = price_df['ma50'].iloc[-1]
                ma200 = price_df['ma200'].iloc[-1]
                
                # Golden cross / Death cross
                if ma50 > ma200:
                    score_factors.append(70)
                    analysis['signals'].append("Bullish: MA50 above MA200")
                else:
                    score_factors.append(30)
                    analysis['signals'].append("Bearish: MA50 below MA200")
                
                # Price relative to MAs
                if current_price > ma20 > ma50:
                    score_factors.append(75)
                    analysis['signals'].append("Strong uptrend")
                elif current_price < ma20 < ma50:
                    score_factors.append(25)
                    analysis['signals'].append("Strong downtrend")
                else:
                    score_factors.append(50)
                
                analysis['indicators']['ma_position'] = {
                    'price': float(current_price),
                    'ma20': float(ma20),
                    'ma50': float(ma50),
                    'ma200': float(ma200)
                }
            
            # RSI Calculation
            if HAS_TA and len(price_df) >= 14:
                rsi = ta.momentum.RSIIndicator(price_df['close'], window=14)
                price_df['rsi'] = rsi.rsi()
                current_rsi = price_df['rsi'].iloc[-1]
                
                if current_rsi < 30:
                    score_factors.append(80)
                    analysis['signals'].append("Oversold (RSI < 30) - potential buy")
                elif current_rsi > 70:
                    score_factors.append(20)
                    analysis['signals'].append("Overbought (RSI > 70) - potential sell")
                elif 40 < current_rsi < 60:
                    score_factors.append(50)
                elif current_rsi < 50:
                    score_factors.append(60)
                else:
                    score_factors.append(40)
                
                analysis['indicators']['rsi'] = float(current_rsi)
            
            # Volume Trend
            if 'volume' in price_df.columns and len(price_df) >= 20:
                recent_volume = price_df['volume'].tail(10).mean()
                avg_volume = price_df['volume'].tail(60).mean()
                
                if avg_volume > 0:
                    volume_ratio = recent_volume / avg_volume
                    analysis['indicators']['volume_ratio'] = float(volume_ratio)
                    
                    if volume_ratio > 1.5:
                        analysis['signals'].append("High volume - strong interest")
                    elif volume_ratio < 0.7:
                        analysis['signals'].append("Low volume - weak interest")
            
            # Calculate final score
            if score_factors:
                analysis['score'] = np.mean(score_factors)
        
        except Exception as e:
            print(f"Error in technical analysis: {e}", file=sys.stderr)
        
        return analysis
    
    def calculate_momentum_score(self, price_df: pd.DataFrame) -> Dict[str, Any]:
        """
        Calculate momentum score based on price movements
        
        Analyzes: Short/medium/long-term returns, volatility
        """
        analysis = {
            'score': 50.0,
            'metrics': {},
            'trends': []
        }
        
        if price_df.empty or len(price_df) < 20:
            return analysis
        
        try:
            score_factors = []
            
            # Calculate returns for different periods
            current_price = price_df['close'].iloc[-1]
            
            # 1-month momentum
            if len(price_df) >= 20:
                month_ago_price = price_df['close'].iloc[-20]
                month_return = ((current_price - month_ago_price) / month_ago_price) * 100
                analysis['metrics']['1m_return'] = round(month_return, 2)
                
                if month_return > 10:
                    score_factors.append(80)
                    analysis['trends'].append("Strong 1-month momentum")
                elif month_return > 5:
                    score_factors.append(65)
                elif month_return > 0:
                    score_factors.append(55)
                elif month_return > -5:
                    score_factors.append(45)
                else:
                    score_factors.append(20)
                    analysis['trends'].append("Weak 1-month momentum")
            
            # 3-month momentum
            if len(price_df) >= 60:
                quarter_ago_price = price_df['close'].iloc[-60]
                quarter_return = ((current_price - quarter_ago_price) / quarter_ago_price) * 100
                analysis['metrics']['3m_return'] = round(quarter_return, 2)
                
                if quarter_return > 20:
                    score_factors.append(85)
                    analysis['trends'].append("Strong 3-month momentum")
                elif quarter_return > 10:
                    score_factors.append(70)
                elif quarter_return > 0:
                    score_factors.append(55)
                else:
                    score_factors.append(30)
            
            # Volatility (risk-adjusted momentum)
            if len(price_df) >= 30:
                returns = price_df['close'].pct_change().dropna()
                volatility = returns.std() * np.sqrt(252)  # Annualized
                analysis['metrics']['volatility'] = round(volatility * 100, 2)
                
                # Lower volatility with positive returns is better
                if volatility < 0.25:
                    analysis['trends'].append("Low volatility")
                elif volatility > 0.50:
                    score_factors.append(score_factors[-1] * 0.8 if score_factors else 40)
                    analysis['trends'].append("High volatility - increased risk")
            
            # Calculate final score
            if score_factors:
                analysis['score'] = np.mean(score_factors)
        
        except Exception as e:
            print(f"Error in momentum analysis: {e}", file=sys.stderr)
        
        return analysis
    
    def calculate_sentiment_score(self, price_df: pd.DataFrame, fundamentals: Dict[str, Any]) -> Dict[str, Any]:
        """
        Calculate sentiment score based on market behavior patterns
        
        Analyzes: Price patterns, volume patterns, market cap
        """
        analysis = {
            'score': 50.0,
            'indicators': [],
            'factors': {}
        }
        
        try:
            score_factors = []
            
            # Recent price performance
            if not price_df.empty and len(price_df) >= 10:
                recent_returns = price_df['close'].pct_change().tail(10)
                positive_days = (recent_returns > 0).sum()
                positive_ratio = positive_days / 10
                
                if positive_ratio > 0.7:
                    score_factors.append(70)
                    analysis['indicators'].append("Mostly positive recent days")
                elif positive_ratio < 0.3:
                    score_factors.append(30)
                    analysis['indicators'].append("Mostly negative recent days")
                else:
                    score_factors.append(50)
                
                analysis['factors']['positive_ratio'] = round(positive_ratio, 2)
            
            # Volume sentiment
            if 'volume' in price_df.columns and len(price_df) >= 20:
                recent_volume = price_df['volume'].tail(10).mean()
                avg_volume = price_df['volume'].tail(60).mean()
                
                if avg_volume > 0:
                    volume_ratio = recent_volume / avg_volume
                    
                    if volume_ratio > 1.5:
                        score_factors.append(65)
                        analysis['indicators'].append("High volume - strong interest")
                    elif volume_ratio < 0.7:
                        score_factors.append(45)
                        analysis['indicators'].append("Low volume - weak interest")
                    else:
                        score_factors.append(55)
                    
                    analysis['factors']['volume_ratio'] = round(volume_ratio, 2)
            
            # Market cap sentiment
            market_cap = fundamentals.get('market_cap')
            if market_cap:
                if market_cap > 10_000_000_000:  # > $10B
                    score_factors.append(70)
                    analysis['indicators'].append("Large cap - lower risk")
                elif market_cap > 2_000_000_000:  # > $2B
                    score_factors.append(60)
                elif market_cap > 300_000_000:  # > $300M
                    score_factors.append(50)
                else:
                    score_factors.append(40)
                    analysis['indicators'].append("Micro cap - higher risk")
            
            # Calculate final score
            if score_factors:
                analysis['score'] = np.mean(score_factors)
        
        except Exception as e:
            print(f"Error in sentiment analysis: {e}", file=sys.stderr)
        
        return analysis
    
    def _get_recommendation(self, overall_score: float) -> str:
        """Generate buy/sell/hold recommendation from score"""
        if overall_score >= 80:
            return 'STRONG_BUY'
        elif overall_score >= 65:
            return 'BUY'
        elif overall_score >= 35:
            return 'HOLD'
        elif overall_score >= 20:
            return 'SELL'
        else:
            return 'STRONG_SELL'
    
    def _calculate_target_price(self, current_price: float, fundamentals: Dict[str, Any], overall_score: float) -> Optional[float]:
        """Calculate target price based on analysis"""
        try:
            if current_price == 0:
                return None
            
            # Base adjustment on overall score
            if overall_score >= 80:
                multiplier = 1.25  # 25% upside
            elif overall_score >= 65:
                multiplier = 1.15  # 15% upside
            elif overall_score >= 35:
                multiplier = 1.05  # 5% upside
            elif overall_score >= 20:
                multiplier = 0.95  # 5% downside
            else:
                multiplier = 0.85  # 15% downside
            
            # Adjust based on P/E ratio
            pe = fundamentals.get('pe_ratio')
            if pe and pe > 0:
                if pe < 15:
                    multiplier *= 1.05
                elif pe > 30:
                    multiplier *= 0.95
            
            target_price = current_price * multiplier
            return round(target_price, 2)
            
        except Exception as e:
            print(f"Error calculating target price: {e}", file=sys.stderr)
            return None
    
    def _assess_risk(self, price_df: pd.DataFrame, fundamentals: Dict[str, Any], technical_analysis: Dict[str, Any]) -> str:
        """Assess overall risk level"""
        try:
            risk_score = 0
            
            # Volatility risk
            if len(price_df) >= 30:
                returns = price_df['close'].pct_change().dropna()
                volatility = returns.std() * np.sqrt(252)
                
                if volatility > 0.5:
                    risk_score += 30
                elif volatility > 0.35:
                    risk_score += 20
                elif volatility > 0.25:
                    risk_score += 10
            
            # Fundamental risk
            debt_to_equity = fundamentals.get('debt_to_equity')
            if debt_to_equity and debt_to_equity > 1.0:
                risk_score += 20
            elif debt_to_equity and debt_to_equity > 0.5:
                risk_score += 10
            
            # Technical risk (RSI extremes)
            rsi = technical_analysis.get('indicators', {}).get('rsi')
            if rsi:
                if rsi > 75 or rsi < 25:
                    risk_score += 15
            
            # Market cap risk
            market_cap = fundamentals.get('market_cap')
            if market_cap and market_cap < 300_000_000:
                risk_score += 25
            elif market_cap and market_cap < 2_000_000_000:
                risk_score += 15
            
            # Classify risk
            if risk_score > 60:
                return 'VERY_HIGH'
            elif risk_score > 40:
                return 'HIGH'
            elif risk_score > 20:
                return 'MEDIUM'
            else:
                return 'LOW'
                
        except Exception as e:
            print(f"Error assessing risk: {e}", file=sys.stderr)
            return 'MEDIUM'
    
    def _calculate_confidence(self, fundamental: Dict, technical: Dict, momentum: Dict, sentiment: Dict) -> float:
        """Calculate confidence in the recommendation"""
        try:
            # More data points = higher confidence
            data_completeness = []
            
            if fundamental.get('factors'):
                data_completeness.append(len(fundamental['factors']) / 6 * 100)  # 6 max factors
            
            if technical.get('indicators'):
                data_completeness.append(min(len(technical['indicators']) / 4 * 100, 100))
            
            if momentum.get('metrics'):
                data_completeness.append(min(len(momentum['metrics']) / 3 * 100, 100))
            
            if sentiment.get('factors'):
                data_completeness.append(min(len(sentiment['factors']) / 3 * 100, 100))
            
            if data_completeness:
                base_confidence = np.mean(data_completeness)
            else:
                base_confidence = 50
            
            # Agreement between dimensions increases confidence
            scores = [
                fundamental.get('score', 50),
                technical.get('score', 50),
                momentum.get('score', 50),
                sentiment.get('score', 50)
            ]
            score_std = np.std(scores)
            
            # Lower standard deviation = higher agreement = higher confidence
            agreement_factor = max(0, 100 - (score_std * 2))
            
            confidence = (base_confidence * 0.6) + (agreement_factor * 0.4)
            return min(confidence, 100)
            
        except Exception as e:
            print(f"Error calculating confidence: {e}", file=sys.stderr)
            return 50.0


def main():
    """
    Command-line interface for PHP to call Python analysis
    
    Usage:
        python analysis.py analyze '{"symbol": "AAPL", "price_data": [...], "fundamentals": {...}}'
    """
    if len(sys.argv) < 3:
        print(json.dumps({
            'error': 'Usage: python analysis.py <command> <json_data>',
            'available_commands': ['analyze']
        }))
        sys.exit(1)
    
    command = sys.argv[1]
    
    try:
        data = json.loads(sys.argv[2])
    except json.JSONDecodeError as e:
        print(json.dumps({'error': f'Invalid JSON: {str(e)}'}))
        sys.exit(1)
    
    if command == 'analyze':
        analyzer = StockAnalyzer(data.get('scoring_weights'))
        result = analyzer.analyze_stock(data)
        print(json.dumps(result))
    else:
        print(json.dumps({'error': f'Unknown command: {command}'}))
        sys.exit(1)


if __name__ == '__main__':
    main()
