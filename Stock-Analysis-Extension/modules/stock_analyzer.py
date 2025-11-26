"""
Stock Analysis Engine
Comprehensive analysis combining fundamental, technical, momentum, and sentiment analysis

REQUIREMENTS TRACEABILITY:
==========================
Business Requirements:
- BR-001: Automated stock analysis
- BR-002: Data-driven investment recommendations
- BR-010: Professional-grade analysis tools
- BR-011: Clear buy/sell/hold recommendations
- BR-030: Comprehensive multi-dimensional analysis

Business Capabilities:
- BC-100: Four-Dimensional Analysis
- BC-101: Fundamental Analysis (40% weight)
- BC-102: Technical Analysis (30% weight)
- BC-103: Momentum Analysis (20% weight)
- BC-104: Sentiment Analysis (10% weight)
- BC-105: Risk Assessment
- BC-106: Target Price Calculation

Functional Requirements:
- FR-200-209: Fundamental Analysis Functions
  * P/E, P/B, P/S ratios; ROE, ROA; Profit margins
  * Debt-to-equity, liquidity ratios; Growth metrics
- FR-300-308: Technical Analysis Functions
  * Moving averages (20, 50, 200 day)
  * RSI, MACD, Bollinger Bands
  * Trend detection, volume analysis
- FR-400-406: Momentum Analysis Functions
  * Short/medium/long-term momentum
  * Volatility metrics, relative strength
- FR-500-505: Sentiment Analysis Functions
  * Analyst ratings, market cap assessment
  * Volume patterns, sector sentiment
- FR-600-606: Risk Assessment Functions
  * Confidence scoring, risk classification
  * Volatility, fundamental, technical risk
- FR-700-706: Recommendation Generation
  * BUY (≥70), SELL (≤40), HOLD (41-69)
  * Target price calculation, expected return

Technical Requirements:
- TR-300-308: StockAnalyzer module specification
- TR-800: Performance (< 30 sec analysis)

Business Rules:
- BRU-100-104: Analysis validation rules
- Scoring: Fundamental 40%, Technical 30%, Momentum 20%, Sentiment 10%
- Recommendations: ≥70 BUY, ≤40 SELL, 41-69 HOLD
- Risk Levels: LOW (<30), MEDIUM (30-50), HIGH (50-70), VERY_HIGH (>70)

Success Criteria:
- SC-101: Analysis completes within 30 seconds
- SC-102: Recommendations match manual analysis ±10%
"""

import pandas as pd
import numpy as np
from datetime import datetime, timedelta
from typing import Dict, List, Tuple, Any, Optional
import logging
from sklearn.preprocessing import StandardScaler
from sklearn.ensemble import RandomForestRegressor
import ta
import warnings
warnings.filterwarnings('ignore')

class StockAnalyzer:
    def __init__(self, config: Dict[str, Any]):
        """
        Initialize stock analyzer with configuration
        
        Args:
            config: Configuration dictionary containing weights and thresholds
        """
        self.config = config
        self.logger = logging.getLogger(__name__)
        self.scoring_weights = config.get('SCORING_WEIGHTS', {
            'fundamental': 0.40,
            'technical': 0.30,
            'momentum': 0.20,
            'sentiment': 0.10
        })
        
    def analyze_stock(self, stock_data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Perform comprehensive stock analysis
        
        Args:
            stock_data: Dictionary containing price data and fundamentals
            
        Returns:
            Dictionary containing analysis results and scores
        """
        symbol = stock_data['symbol']
        self.logger.info(f"Starting analysis for {symbol}")
        
        analysis_result = {
            'symbol': symbol,
            'analysis_date': datetime.now().date(),
            'fundamental_score': 0.0,
            'technical_score': 0.0,
            'momentum_score': 0.0,
            'sentiment_score': 0.0,
            'overall_score': 0.0,
            'recommendation': 'HOLD',
            'target_price': None,
            'risk_rating': 'MEDIUM',
            'confidence_level': 0.0,
            'details': {},
            'error': None
        }
        
        try:
            price_df = stock_data['price_data']
            fundamentals = stock_data['fundamentals']
            
            if price_df.empty:
                analysis_result['error'] = "No price data available"
                return analysis_result
            
            # Ensure we have enough data
            if len(price_df) < 50:
                analysis_result['error'] = "Insufficient price data for analysis"
                return analysis_result
            
            # Perform individual analyses
            fundamental_analysis = self._analyze_fundamentals(fundamentals)
            technical_analysis = self._analyze_technical(price_df)
            momentum_analysis = self._analyze_momentum(price_df)
            sentiment_analysis = self._analyze_sentiment(fundamentals, price_df)
            
            # Extract scores
            analysis_result['fundamental_score'] = fundamental_analysis['score']
            analysis_result['technical_score'] = technical_analysis['score']
            analysis_result['momentum_score'] = momentum_analysis['score']
            analysis_result['sentiment_score'] = sentiment_analysis['score']
            
            # Calculate overall score
            overall_score = (
                self.scoring_weights['fundamental'] * fundamental_analysis['score'] +
                self.scoring_weights['technical'] * technical_analysis['score'] +
                self.scoring_weights['momentum'] * momentum_analysis['score'] +
                self.scoring_weights['sentiment'] * sentiment_analysis['score']
            )
            analysis_result['overall_score'] = round(overall_score, 2)
            
            # Determine recommendation
            analysis_result['recommendation'] = self._get_recommendation(overall_score)
            
            # Calculate target price
            analysis_result['target_price'] = self._calculate_target_price(
                price_df, fundamentals, overall_score
            )
            
            # Assess risk rating
            analysis_result['risk_rating'] = self._assess_risk(
                price_df, fundamentals, technical_analysis
            )
            
            # Calculate confidence level
            analysis_result['confidence_level'] = self._calculate_confidence(
                fundamental_analysis, technical_analysis, momentum_analysis, sentiment_analysis
            )
            
            # Store detailed analysis
            analysis_result['details'] = {
                'fundamental': fundamental_analysis,
                'technical': technical_analysis,
                'momentum': momentum_analysis,
                'sentiment': sentiment_analysis,
                'price_current': float(price_df['close'].iloc[-1]),
                'price_52w_high': float(price_df['high'].tail(252).max()),
                'price_52w_low': float(price_df['low'].tail(252).min()),
                'volume_avg_30d': float(price_df['volume'].tail(30).mean()),
                'volatility_30d': float(price_df['close'].pct_change().tail(30).std() * np.sqrt(252))
            }
            
            self.logger.info(f"Analysis completed for {symbol} - Score: {overall_score:.2f}")
            
        except Exception as e:
            self.logger.error(f"Error analyzing {symbol}: {e}")
            analysis_result['error'] = str(e)
            
        return analysis_result
    
    def _analyze_fundamentals(self, fundamentals: Dict[str, Any]) -> Dict[str, Any]:
        """
        Analyze fundamental metrics
        
        Args:
            fundamentals: Dictionary of fundamental data
            
        Returns:
            Dictionary containing fundamental analysis results
        """
        analysis = {
            'score': 50.0,  # Default neutral score
            'factors': {},
            'strength': [],
            'weakness': []
        }
        
        try:
            score = 50.0  # Start with neutral
            factors = {}
            
            # P/E Ratio Analysis
            pe = fundamentals.get('pe_ratio')
            if pe and pe > 0:
                if pe < 15:
                    factors['pe_score'] = 85
                    analysis['strength'].append("Low P/E ratio suggests undervaluation")
                elif pe < 25:
                    factors['pe_score'] = 70
                elif pe < 35:
                    factors['pe_score'] = 45
                else:
                    factors['pe_score'] = 25
                    analysis['weakness'].append("High P/E ratio suggests overvaluation")
            else:
                factors['pe_score'] = 40  # Negative or missing PE
            
            # Price-to-Book Analysis
            pb = fundamentals.get('price_to_book')
            if pb and pb > 0:
                if pb < 1.5:
                    factors['pb_score'] = 80
                    analysis['strength'].append("Low P/B ratio indicates good value")
                elif pb < 3:
                    factors['pb_score'] = 60
                elif pb < 5:
                    factors['pb_score'] = 40
                else:
                    factors['pb_score'] = 20
                    analysis['weakness'].append("High P/B ratio suggests premium valuation")
            else:
                factors['pb_score'] = 50
            
            # Return on Equity
            roe = fundamentals.get('return_on_equity')
            if roe:
                roe_pct = roe * 100 if roe < 1 else roe  # Handle decimal vs percentage
                if roe_pct > 20:
                    factors['roe_score'] = 90
                    analysis['strength'].append("Excellent return on equity")
                elif roe_pct > 15:
                    factors['roe_score'] = 75
                    analysis['strength'].append("Strong return on equity")
                elif roe_pct > 10:
                    factors['roe_score'] = 60
                else:
                    factors['roe_score'] = 30
                    analysis['weakness'].append("Low return on equity")
            else:
                factors['roe_score'] = 50
            
            # Debt-to-Equity
            de = fundamentals.get('debt_to_equity')
            if de is not None:
                if de < 0.3:
                    factors['de_score'] = 85
                    analysis['strength'].append("Low debt-to-equity ratio")
                elif de < 0.6:
                    factors['de_score'] = 70
                elif de < 1.0:
                    factors['de_score'] = 50
                else:
                    factors['de_score'] = 25
                    analysis['weakness'].append("High debt-to-equity ratio")
            else:
                factors['de_score'] = 50
            
            # Profit Margin
            pm = fundamentals.get('profit_margin')
            if pm:
                pm_pct = pm * 100 if pm < 1 else pm
                if pm_pct > 20:
                    factors['pm_score'] = 90
                    analysis['strength'].append("Excellent profit margin")
                elif pm_pct > 10:
                    factors['pm_score'] = 75
                    analysis['strength'].append("Strong profit margin")
                elif pm_pct > 5:
                    factors['pm_score'] = 60
                else:
                    factors['pm_score'] = 30
                    analysis['weakness'].append("Low profit margin")
            else:
                factors['pm_score'] = 50
            
            # Revenue Growth
            rg = fundamentals.get('revenue_growth')
            if rg:
                rg_pct = rg * 100 if rg < 1 else rg
                if rg_pct > 20:
                    factors['rg_score'] = 90
                    analysis['strength'].append("Strong revenue growth")
                elif rg_pct > 10:
                    factors['rg_score'] = 75
                elif rg_pct > 5:
                    factors['rg_score'] = 60
                elif rg_pct > 0:
                    factors['rg_score'] = 45
                else:
                    factors['rg_score'] = 20
                    analysis['weakness'].append("Negative revenue growth")
            else:
                factors['rg_score'] = 50
            
            # Current Ratio (Liquidity)
            cr = fundamentals.get('current_ratio')
            if cr:
                if cr > 2.5:
                    factors['cr_score'] = 80
                    analysis['strength'].append("Strong liquidity position")
                elif cr > 1.5:
                    factors['cr_score'] = 70
                elif cr > 1.0:
                    factors['cr_score'] = 50
                else:
                    factors['cr_score'] = 20
                    analysis['weakness'].append("Poor liquidity position")
            else:
                factors['cr_score'] = 50
            
            # Calculate weighted average
            if factors:
                score = np.mean(list(factors.values()))
            
            analysis['score'] = round(score, 2)
            analysis['factors'] = factors
            
        except Exception as e:
            self.logger.warning(f"Error in fundamental analysis: {e}")
            
        return analysis
    
    def _analyze_technical(self, price_df: pd.DataFrame) -> Dict[str, Any]:
        """
        Analyze technical indicators
        
        Args:
            price_df: DataFrame containing price data
            
        Returns:
            Dictionary containing technical analysis results
        """
        analysis = {
            'score': 50.0,
            'indicators': {},
            'signals': [],
            'trends': {}
        }
        
        try:
            df = price_df.copy()
            df = df.sort_values('date').reset_index(drop=True)
            
            # Calculate technical indicators
            df['sma_20'] = ta.trend.sma_indicator(df['close'], window=20)
            df['sma_50'] = ta.trend.sma_indicator(df['close'], window=50)
            df['sma_200'] = ta.trend.sma_indicator(df['close'], window=200)
            
            df['rsi'] = ta.momentum.rsi(df['close'], window=14)
            df['macd'] = ta.trend.macd_diff(df['close'])
            df['bb_upper'] = ta.volatility.bollinger_hband(df['close'])
            df['bb_lower'] = ta.volatility.bollinger_lband(df['close'])
            df['atr'] = ta.volatility.average_true_range(df['high'], df['low'], df['close'])
            
            # Get latest values
            latest = df.iloc[-1]
            current_price = latest['close']
            
            # Scoring factors
            score_factors = []
            
            # Moving Average Analysis
            if not pd.isna(latest['sma_20']) and not pd.isna(latest['sma_50']):
                if current_price > latest['sma_20'] > latest['sma_50']:
                    score_factors.append(80)
                    analysis['signals'].append("Price above short and medium-term MAs - Bullish")
                elif current_price > latest['sma_20']:
                    score_factors.append(65)
                    analysis['signals'].append("Price above 20-day MA - Short-term bullish")
                elif current_price < latest['sma_20'] < latest['sma_50']:
                    score_factors.append(20)
                    analysis['signals'].append("Price below short and medium-term MAs - Bearish")
                else:
                    score_factors.append(45)
            
            # RSI Analysis
            if not pd.isna(latest['rsi']):
                rsi = latest['rsi']
                if rsi < 30:
                    score_factors.append(75)
                    analysis['signals'].append("RSI oversold - Potential buying opportunity")
                elif rsi > 70:
                    score_factors.append(25)
                    analysis['signals'].append("RSI overbought - Potential selling pressure")
                elif 40 <= rsi <= 60:
                    score_factors.append(60)
                else:
                    score_factors.append(50)
                
                analysis['indicators']['rsi'] = round(rsi, 2)
            
            # MACD Analysis
            if not pd.isna(latest['macd']) and len(df) > 1:
                macd_current = latest['macd']
                macd_prev = df.iloc[-2]['macd'] if not pd.isna(df.iloc[-2]['macd']) else macd_current
                
                if macd_current > 0 and macd_current > macd_prev:
                    score_factors.append(75)
                    analysis['signals'].append("MACD positive and rising - Bullish momentum")
                elif macd_current > 0:
                    score_factors.append(60)
                elif macd_current < 0 and macd_current < macd_prev:
                    score_factors.append(25)
                    analysis['signals'].append("MACD negative and falling - Bearish momentum")
                else:
                    score_factors.append(40)
                
                analysis['indicators']['macd'] = round(macd_current, 4)
            
            # Bollinger Bands Analysis
            if not pd.isna(latest['bb_upper']) and not pd.isna(latest['bb_lower']):
                bb_position = (current_price - latest['bb_lower']) / (latest['bb_upper'] - latest['bb_lower'])
                
                if bb_position < 0.2:
                    score_factors.append(75)
                    analysis['signals'].append("Price near lower Bollinger Band - Oversold")
                elif bb_position > 0.8:
                    score_factors.append(25)
                    analysis['signals'].append("Price near upper Bollinger Band - Overbought")
                else:
                    score_factors.append(50)
                
                analysis['indicators']['bb_position'] = round(bb_position, 3)
            
            # Volume Analysis
            if 'volume' in df.columns and len(df) >= 20:
                avg_volume_20 = df['volume'].tail(20).mean()
                recent_volume = df['volume'].tail(5).mean()
                
                if recent_volume > avg_volume_20 * 1.5:
                    score_factors.append(70)
                    analysis['signals'].append("Above-average volume - Strong interest")
                elif recent_volume < avg_volume_20 * 0.5:
                    score_factors.append(40)
                    analysis['signals'].append("Below-average volume - Weak interest")
                else:
                    score_factors.append(55)
            
            # Price Trend Analysis
            if len(df) >= 20:
                price_20d_ago = df['close'].iloc[-20]
                price_change_20d = (current_price - price_20d_ago) / price_20d_ago * 100
                
                if price_change_20d > 10:
                    analysis['trends']['20d'] = "Strong Uptrend"
                elif price_change_20d > 5:
                    analysis['trends']['20d'] = "Uptrend"
                elif price_change_20d < -10:
                    analysis['trends']['20d'] = "Strong Downtrend"
                elif price_change_20d < -5:
                    analysis['trends']['20d'] = "Downtrend"
                else:
                    analysis['trends']['20d'] = "Sideways"
            
            # Calculate final score
            if score_factors:
                analysis['score'] = round(np.mean(score_factors), 2)
            
            # Store key indicators
            analysis['indicators'].update({
                'sma_20': round(latest['sma_20'], 2) if not pd.isna(latest['sma_20']) else None,
                'sma_50': round(latest['sma_50'], 2) if not pd.isna(latest['sma_50']) else None,
                'current_price': round(current_price, 2)
            })
            
        except Exception as e:
            self.logger.warning(f"Error in technical analysis: {e}")
            
        return analysis
    
    def _analyze_momentum(self, price_df: pd.DataFrame) -> Dict[str, Any]:
        """
        Analyze price momentum
        
        Args:
            price_df: DataFrame containing price data
            
        Returns:
            Dictionary containing momentum analysis results
        """
        analysis = {
            'score': 50.0,
            'metrics': {},
            'trends': []
        }
        
        try:
            df = price_df.copy()
            df = df.sort_values('date').reset_index(drop=True)
            
            if len(df) < 20:
                return analysis
            
            current_price = df['close'].iloc[-1]
            score_factors = []
            
            # Short-term momentum (5 days)
            if len(df) >= 5:
                price_5d = df['close'].iloc[-5]
                momentum_5d = (current_price - price_5d) / price_5d * 100
                
                if momentum_5d > 5:
                    score_factors.append(80)
                    analysis['trends'].append("Strong 5-day momentum")
                elif momentum_5d > 2:
                    score_factors.append(65)
                    analysis['trends'].append("Positive 5-day momentum")
                elif momentum_5d < -5:
                    score_factors.append(20)
                    analysis['trends'].append("Weak 5-day momentum")
                elif momentum_5d < -2:
                    score_factors.append(35)
                    analysis['trends'].append("Negative 5-day momentum")
                else:
                    score_factors.append(50)
                
                analysis['metrics']['momentum_5d'] = round(momentum_5d, 2)
            
            # Medium-term momentum (20 days)
            if len(df) >= 20:
                price_20d = df['close'].iloc[-20]
                momentum_20d = (current_price - price_20d) / price_20d * 100
                
                if momentum_20d > 10:
                    score_factors.append(85)
                    analysis['trends'].append("Strong 20-day momentum")
                elif momentum_20d > 5:
                    score_factors.append(70)
                    analysis['trends'].append("Positive 20-day momentum")
                elif momentum_20d < -10:
                    score_factors.append(15)
                    analysis['trends'].append("Weak 20-day momentum")
                elif momentum_20d < -5:
                    score_factors.append(30)
                    analysis['trends'].append("Negative 20-day momentum")
                else:
                    score_factors.append(50)
                
                analysis['metrics']['momentum_20d'] = round(momentum_20d, 2)
            
            # Long-term momentum (60 days)
            if len(df) >= 60:
                price_60d = df['close'].iloc[-60]
                momentum_60d = (current_price - price_60d) / price_60d * 100
                
                if momentum_60d > 20:
                    score_factors.append(90)
                    analysis['trends'].append("Excellent 60-day momentum")
                elif momentum_60d > 10:
                    score_factors.append(75)
                    analysis['trends'].append("Strong 60-day momentum")
                elif momentum_60d < -20:
                    score_factors.append(10)
                    analysis['trends'].append("Poor 60-day momentum")
                elif momentum_60d < -10:
                    score_factors.append(25)
                    analysis['trends'].append("Negative 60-day momentum")
                else:
                    score_factors.append(50)
                
                analysis['metrics']['momentum_60d'] = round(momentum_60d, 2)
            
            # Volatility analysis
            if len(df) >= 30:
                returns = df['close'].pct_change().dropna()
                volatility = returns.std() * np.sqrt(252) * 100  # Annualized volatility
                
                # Lower volatility with positive momentum is better
                if volatility < 20 and analysis['metrics'].get('momentum_20d', 0) > 0:
                    score_factors.append(70)
                    analysis['trends'].append("Low volatility with positive momentum")
                elif volatility > 50:
                    score_factors.append(30)
                    analysis['trends'].append("High volatility - risky")
                else:
                    score_factors.append(50)
                
                analysis['metrics']['volatility_annual'] = round(volatility, 2)
            
            # Relative strength vs market (if we have benchmark data)
            # This would require market data - placeholder for now
            analysis['metrics']['relative_strength'] = 50.0
            
            # Calculate final score
            if score_factors:
                analysis['score'] = round(np.mean(score_factors), 2)
            
        except Exception as e:
            self.logger.warning(f"Error in momentum analysis: {e}")
            
        return analysis
    
    def _analyze_sentiment(self, fundamentals: Dict[str, Any], price_df: pd.DataFrame) -> Dict[str, Any]:
        """
        Analyze market sentiment indicators
        
        Args:
            fundamentals: Dictionary of fundamental data
            price_df: DataFrame containing price data
            
        Returns:
            Dictionary containing sentiment analysis results
        """
        analysis = {
            'score': 50.0,
            'factors': {},
            'indicators': []
        }
        
        try:
            score_factors = []
            
            # Analyst sentiment (if available)
            analyst_rating = fundamentals.get('analyst_rating')
            if analyst_rating:
                rating_scores = {
                    'STRONG_BUY': 90, 'strong_buy': 90, 'Buy': 75, 'BUY': 75,
                    'HOLD': 50, 'hold': 50, 'Hold': 50,
                    'SELL': 25, 'sell': 25, 'Sell': 25,
                    'STRONG_SELL': 10, 'strong_sell': 10
                }
                
                rating_score = rating_scores.get(analyst_rating, 50)
                score_factors.append(rating_score)
                analysis['indicators'].append(f"Analyst rating: {analyst_rating}")
                analysis['factors']['analyst_rating'] = rating_score
            
            # Market cap sentiment (large caps generally more stable)
            market_cap = fundamentals.get('market_cap')
            if market_cap:
                if market_cap > 200e9:  # > $200B
                    score_factors.append(65)
                    analysis['indicators'].append("Large cap - stable")
                elif market_cap > 10e9:  # > $10B
                    score_factors.append(60)
                    analysis['indicators'].append("Mid-large cap")
                elif market_cap > 2e9:  # > $2B
                    score_factors.append(55)
                    analysis['indicators'].append("Mid cap")
                else:
                    score_factors.append(45)
                    analysis['indicators'].append("Small cap - higher risk")
                
                analysis['factors']['market_cap_sentiment'] = score_factors[-1]
            
            # Sector sentiment (simplified)
            sector = fundamentals.get('sector')
            if sector:
                # Simplified sector scoring based on general market sentiment
                sector_scores = {
                    'Technology': 70,
                    'Healthcare': 65,
                    'Consumer Discretionary': 60,
                    'Financials': 55,
                    'Industrials': 55,
                    'Consumer Staples': 60,
                    'Energy': 45,
                    'Utilities': 50,
                    'Real Estate': 50,
                    'Materials': 50,
                    'Communication Services': 60
                }
                
                sector_score = sector_scores.get(sector, 50)
                score_factors.append(sector_score)
                analysis['indicators'].append(f"Sector sentiment: {sector}")
                analysis['factors']['sector_sentiment'] = sector_score
            
            # Price momentum as sentiment proxy
            if len(price_df) >= 30:
                recent_returns = price_df['close'].pct_change().tail(30)
                positive_days = (recent_returns > 0).sum()
                positive_ratio = positive_days / len(recent_returns)
                
                if positive_ratio > 0.6:
                    score_factors.append(70)
                    analysis['indicators'].append("Strong recent performance")
                elif positive_ratio > 0.5:
                    score_factors.append(60)
                elif positive_ratio < 0.4:
                    score_factors.append(40)
                    analysis['indicators'].append("Weak recent performance")
                else:
                    score_factors.append(50)
                
                analysis['factors']['performance_sentiment'] = round(positive_ratio * 100, 1)
            
            # Volume sentiment
            if 'volume' in price_df.columns and len(price_df) >= 20:
                recent_volume = price_df['volume'].tail(10).mean()
                avg_volume = price_df['volume'].tail(60).mean()
                
                volume_ratio = recent_volume / avg_volume if avg_volume > 0 else 1
                
                if volume_ratio > 1.5:
                    score_factors.append(65)
                    analysis['indicators'].append("High volume - strong interest")
                elif volume_ratio < 0.7:
                    score_factors.append(45)
                    analysis['indicators'].append("Low volume - weak interest")
                else:
                    score_factors.append(55)
                
                analysis['factors']['volume_sentiment'] = round(volume_ratio, 2)
            
            # Calculate final score
            if score_factors:
                analysis['score'] = round(np.mean(score_factors), 2)
            
        except Exception as e:
            self.logger.warning(f"Error in sentiment analysis: {e}")
            
        return analysis
    
    def _get_recommendation(self, overall_score: float) -> str:
        """Get recommendation based on overall score"""
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
    
    def _calculate_target_price(self, price_df: pd.DataFrame, fundamentals: Dict[str, Any], overall_score: float) -> Optional[float]:
        """Calculate target price based on analysis"""
        try:
            current_price = float(price_df['close'].iloc[-1])
            
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
            
            # Adjust based on fundamentals
            pe = fundamentals.get('pe_ratio')
            if pe and pe > 0:
                if pe < 15:
                    multiplier *= 1.05
                elif pe > 30:
                    multiplier *= 0.95
            
            target_price = current_price * multiplier
            return round(target_price, 2)
            
        except Exception as e:
            self.logger.warning(f"Error calculating target price: {e}")
            return None
    
    def _assess_risk(self, price_df: pd.DataFrame, fundamentals: Dict[str, Any], technical_analysis: Dict[str, Any]) -> str:
        """Assess risk level"""
        try:
            risk_factors = []
            
            # Volatility risk
            if len(price_df) >= 30:
                returns = price_df['close'].pct_change().dropna()
                volatility = returns.std() * np.sqrt(252)
                
                if volatility > 0.4:
                    risk_factors.append('HIGH')
                elif volatility > 0.25:
                    risk_factors.append('MEDIUM')
                else:
                    risk_factors.append('LOW')
            
            # Fundamental risk
            debt_to_equity = fundamentals.get('debt_to_equity')
            if debt_to_equity:
                if debt_to_equity > 1.0:
                    risk_factors.append('HIGH')
                elif debt_to_equity > 0.5:
                    risk_factors.append('MEDIUM')
                else:
                    risk_factors.append('LOW')
            
            # Technical risk
            rsi = technical_analysis.get('indicators', {}).get('rsi')
            if rsi:
                if rsi > 80 or rsi < 20:
                    risk_factors.append('HIGH')
                elif rsi > 70 or rsi < 30:
                    risk_factors.append('MEDIUM')
                else:
                    risk_factors.append('LOW')
            
            # Determine overall risk
            risk_counts = {'LOW': 0, 'MEDIUM': 0, 'HIGH': 0, 'VERY_HIGH': 0}
            for risk in risk_factors:
                risk_counts[risk] += 1
            
            if risk_counts['HIGH'] >= 2:
                return 'VERY_HIGH'
            elif risk_counts['HIGH'] >= 1:
                return 'HIGH'
            elif risk_counts['MEDIUM'] >= 2:
                return 'HIGH'
            elif risk_counts['MEDIUM'] >= 1:
                return 'MEDIUM'
            else:
                return 'LOW'
                
        except Exception as e:
            self.logger.warning(f"Error assessing risk: {e}")
            return 'MEDIUM'
    
    def _calculate_confidence(self, fundamental_analysis: Dict, technical_analysis: Dict, 
                            momentum_analysis: Dict, sentiment_analysis: Dict) -> float:
        """Calculate confidence level based on agreement between analyses"""
        try:
            scores = [
                fundamental_analysis['score'],
                technical_analysis['score'],
                momentum_analysis['score'],
                sentiment_analysis['score']
            ]
            
            # Calculate standard deviation
            std_dev = np.std(scores)
            
            # Lower std dev means higher confidence
            if std_dev < 10:
                confidence = 90
            elif std_dev < 15:
                confidence = 80
            elif std_dev < 20:
                confidence = 70
            elif std_dev < 25:
                confidence = 60
            else:
                confidence = 50
            
            return round(confidence, 1)
            
        except Exception as e:
            self.logger.warning(f"Error calculating confidence: {e}")
            return 50.0
