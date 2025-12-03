# User Acceptance Test (UAT) Cases
**Trading Strategy System**  
**Version**: 1.0  
**Date**: December 2, 2025  
**Project**: ChatGPT Micro-Cap Trading Strategy System

---

## UAT Overview

This document defines User Acceptance Tests (UAT) for the trading strategy system. Each test case represents a real-world user scenario that validates business requirements and functional specifications.

### Test Environment Setup
- **Database**: SQLite test database with sample data
- **Test Account**: $100,000 initial capital
- **Test Symbols**: AAPL, MSFT, TSLA, AMD, NVDA, GME, AMC
- **Test Period**: 2023-01-01 to 2024-12-31
- **Test User**: test_trader@example.com

---

## UAT Test Cases

### **UAT-001: Single Strategy Analysis - Basic Usage**

**Priority**: HIGH  
**User Story**: As a trader, I want to analyze a stock using a single strategy to get buy/sell/hold recommendations.

**Preconditions**:
- System is running
- User has access to strategy analysis interface
- Market data is available for target symbol

**Test Steps**:
1. Navigate to strategy analysis page
2. Select "Mean Reversion" strategy
3. Enter symbol: "AAPL"
4. Click "Analyze" button
5. Review results displayed

**Expected Results**:
- ✅ Analysis completes within 5 seconds
- ✅ Results show: Action (BUY/SELL/HOLD), Confidence (0-100%), Reasoning (text explanation)
- ✅ Metrics displayed include: RSI, Bollinger Bands position, volume confirmation, mean reversion score
- ✅ User can understand the recommendation without technical knowledge

**Acceptance Criteria**:
- [ ] Action is clearly displayed (BUY/SELL/HOLD)
- [ ] Confidence percentage is shown
- [ ] Reasoning explains why the action was recommended
- [ ] Key metrics are displayed in user-friendly format
- [ ] No errors or warnings displayed

**Test Data**:
```
Symbol: AAPL
Strategy: Mean Reversion
Date: 2024-01-15
Expected Action: BUY or HOLD (based on market conditions)
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________  
**Notes**: _____________

---

### **UAT-002: Multi-Strategy Portfolio Analysis**

**Priority**: HIGH  
**User Story**: As a trader, I want to analyze a stock using all 6 strategies simultaneously to get consensus recommendations.

**Preconditions**:
- All 6 strategies are configured and operational
- Weighting engine is set to "Balanced" profile
- Market data is available

**Test Steps**:
1. Navigate to portfolio analysis page
2. Select allocation profile: "Balanced"
3. Enter symbol: "TSLA"
4. Click "Analyze with All Strategies"
5. Review consensus results

**Expected Results**:
- ✅ All 6 strategies provide individual recommendations
- ✅ Weighted consensus action is displayed prominently
- ✅ Individual strategy results are available for review
- ✅ Confidence scores are weighted according to profile
- ✅ Reasoning combines insights from multiple strategies

**Acceptance Criteria**:
- [ ] Consensus action clearly displayed
- [ ] Weighted confidence percentage shown
- [ ] Individual strategy results expandable/viewable
- [ ] Each strategy shows its own action and confidence
- [ ] Profile allocation percentages visible
- [ ] Total confidence adds up correctly

**Test Data**:
```
Symbol: TSLA
Profile: Balanced
Strategies: All 6 (SmallCapCatalyst, IPlace, MeanReversion, QualityDividend, MomentumQuality, Contrarian)
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-003: Strategy Profile Comparison**

**Priority**: MEDIUM  
**User Story**: As a trader, I want to compare how different risk profiles affect recommendations for the same stock.

**Preconditions**:
- Multiple allocation profiles available (Conservative, Balanced, Aggressive, Growth, Value)
- Same symbol and date used for comparison

**Test Steps**:
1. Navigate to profile comparison page
2. Enter symbol: "AMD"
3. Select profiles to compare: "Conservative", "Aggressive", "Balanced"
4. Click "Compare Profiles"
5. Review side-by-side comparison

**Expected Results**:
- ✅ Results for all selected profiles displayed side-by-side
- ✅ Conservative profile favors Quality Dividend and Mean Reversion
- ✅ Aggressive profile favors Small Cap Catalyst and Momentum
- ✅ Differences in recommendations are clearly highlighted
- ✅ Confidence levels differ based on profile

**Acceptance Criteria**:
- [ ] Side-by-side comparison table displayed
- [ ] Each profile shows action and confidence
- [ ] Profile allocation percentages shown
- [ ] Differences highlighted (e.g., different actions)
- [ ] User can understand which profile suits their risk tolerance

**Test Data**:
```
Symbol: AMD
Profiles: Conservative, Balanced, Aggressive
Expected: Different confidence levels and possibly different actions
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-004: Historical Backtesting - Single Strategy**

**Priority**: HIGH  
**User Story**: As a trader, I want to backtest a strategy on historical data to see how it would have performed.

**Preconditions**:
- Historical data available for test period
- Strategy is configured with default parameters
- Initial capital: $100,000

**Test Steps**:
1. Navigate to backtesting page
2. Select strategy: "Momentum Quality"
3. Select symbol: "NVDA"
4. Set date range: 2023-01-01 to 2023-12-31
5. Configure parameters:
   - Position size: 10%
   - Stop loss: 10%
   - Take profit: 20%
6. Click "Run Backtest"
7. Review results

**Expected Results**:
- ✅ Backtest completes within 30 seconds
- ✅ Results show: Total return, Win rate, Profit factor, Sharpe ratio, Max drawdown
- ✅ Trade history is displayed with entry/exit dates and prices
- ✅ Equity curve chart is displayed
- ✅ Performance metrics are accurate and realistic

**Acceptance Criteria**:
- [ ] Total return percentage displayed
- [ ] Number of trades shown
- [ ] Win rate percentage calculated correctly
- [ ] Profit factor > 1.0 for profitable strategy
- [ ] Sharpe ratio calculated
- [ ] Max drawdown displayed as percentage
- [ ] Trade-by-trade details available
- [ ] Equity curve chart renders correctly

**Test Data**:
```
Symbol: NVDA
Strategy: Momentum Quality
Period: 2023-01-01 to 2023-12-31
Initial Capital: $100,000
Expected: Multiple trades, positive or negative return
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-005: Portfolio Backtesting with Multiple Strategies**

**Priority**: HIGH  
**User Story**: As a portfolio manager, I want to backtest a portfolio using multiple strategies simultaneously.

**Preconditions**:
- Historical data available for multiple symbols
- Multiple strategies configured
- Portfolio allocation profile selected

**Test Steps**:
1. Navigate to portfolio backtesting page
2. Select allocation profile: "Balanced"
3. Add symbols: AAPL, MSFT, TSLA, AMD, NVDA
4. Set date range: 2023-01-01 to 2024-12-31
5. Configure portfolio parameters:
   - Max positions: 5
   - Position size: 15% per position
   - Rebalance frequency: 30 days
6. Click "Run Portfolio Backtest"
7. Review results

**Expected Results**:
- ✅ Portfolio backtest completes within 2 minutes
- ✅ Portfolio-level metrics displayed
- ✅ Strategy breakdown shows performance by strategy
- ✅ Position history shows all trades across all symbols
- ✅ Portfolio equity curve displayed
- ✅ Diversification metrics shown

**Acceptance Criteria**:
- [ ] Portfolio total return displayed
- [ ] Strategy breakdown shows contribution of each strategy
- [ ] All trades listed with symbol, strategy, dates, prices
- [ ] Portfolio equity curve chart rendered
- [ ] Max portfolio drawdown calculated
- [ ] Portfolio Sharpe ratio displayed
- [ ] Rebalancing events shown
- [ ] Position sizing adhered to limits

**Test Data**:
```
Symbols: AAPL, MSFT, TSLA, AMD, NVDA
Profile: Balanced
Period: 2023-01-01 to 2024-12-31
Initial Capital: $100,000
Max Positions: 5
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-006: Walk-Forward Analysis**

**Priority**: MEDIUM  
**User Story**: As a quantitative analyst, I want to run walk-forward analysis to validate strategy robustness.

**Preconditions**:
- Historical data available for extended period (2+ years)
- Strategy parameters can be optimized
- Sufficient data for training and testing periods

**Test Steps**:
1. Navigate to walk-forward analysis page
2. Select strategy: "Mean Reversion"
3. Select symbol: "MSFT"
4. Set date range: 2022-01-01 to 2024-12-31
5. Configure walk-forward parameters:
   - Training period: 60 days
   - Testing period: 30 days
   - Step size: 30 days
6. Click "Run Walk-Forward Analysis"
7. Review results

**Expected Results**:
- ✅ Walk-forward analysis completes within 3 minutes
- ✅ Multiple periods are analyzed (training → testing)
- ✅ Each period shows training performance and testing performance
- ✅ Aggregate metrics across all periods displayed
- ✅ Parameter stability chart shown
- ✅ Out-of-sample performance validated

**Acceptance Criteria**:
- [ ] Multiple periods analyzed (minimum 10)
- [ ] Each period shows training and testing metrics
- [ ] Testing performance compared to training performance
- [ ] Aggregate metrics calculated across all testing periods
- [ ] Parameter stability visualized
- [ ] Overfitting indicators displayed (if testing << training)
- [ ] Results exportable to CSV

**Test Data**:
```
Symbol: MSFT
Strategy: Mean Reversion
Period: 2022-01-01 to 2024-12-31
Training: 60 days, Testing: 30 days, Step: 30 days
Expected: 15-20 periods
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-007: Strategy Performance Comparison**

**Priority**: HIGH  
**User Story**: As a trader, I want to compare the performance of different strategies to choose the best one.

**Preconditions**:
- Trade history exists for multiple strategies
- Sufficient trades for statistical significance (minimum 20 trades per strategy)

**Test Steps**:
1. Navigate to performance comparison page
2. Select strategies to compare:
   - Mean Reversion
   - Momentum Quality
   - Quality Dividend
3. Set comparison period: 2023-01-01 to 2024-12-31
4. Click "Compare Strategies"
5. Review comparison results

**Expected Results**:
- ✅ Side-by-side comparison of all metrics
- ✅ Metrics include: Total return, Win rate, Profit factor, Sharpe ratio, Max drawdown
- ✅ Best performing strategy highlighted
- ✅ Charts compare equity curves
- ✅ Statistical significance of differences shown

**Acceptance Criteria**:
- [ ] Comparison table displays all key metrics
- [ ] Best strategy for each metric highlighted
- [ ] Equity curve comparison chart rendered
- [ ] Win rate comparison visible
- [ ] Sharpe ratio comparison visible
- [ ] Drawdown comparison visible
- [ ] Trade count shown for each strategy
- [ ] Rankings displayed (1st, 2nd, 3rd)

**Test Data**:
```
Strategies: Mean Reversion, Momentum Quality, Quality Dividend
Period: 2023-01-01 to 2024-12-31
Minimum trades per strategy: 20
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-008: Strategy Correlation Analysis**

**Priority**: MEDIUM  
**User Story**: As a portfolio manager, I want to understand correlations between strategies to optimize diversification.

**Preconditions**:
- Trade history exists for multiple strategies
- Strategies have overlapping time periods
- Sufficient data for correlation calculation

**Test Steps**:
1. Navigate to correlation analysis page
2. Select strategies for analysis: All 6 strategies
3. Set analysis period: 2023-01-01 to 2024-12-31
4. Click "Calculate Correlations"
5. Review correlation matrix

**Expected Results**:
- ✅ Correlation matrix displayed with all strategies
- ✅ Correlation values between -1.0 and +1.0
- ✅ High correlations highlighted (>0.7)
- ✅ Low correlations highlighted (<0.3)
- ✅ Heatmap visualization available
- ✅ Diversification score calculated

**Acceptance Criteria**:
- [ ] Correlation matrix shows all strategy pairs
- [ ] Correlation values are within [-1, 1]
- [ ] Heatmap color-coded (red=high, blue=low)
- [ ] Highly correlated pairs identified
- [ ] Uncorrelated/negatively correlated pairs identified
- [ ] Diversification recommendations provided
- [ ] Portfolio correlation score calculated

**Test Data**:
```
Strategies: All 6 strategies
Period: 2023-01-01 to 2024-12-31
Expected: Some pairs correlated, some uncorrelated
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-009: Optimal Strategy Combination Finder**

**Priority**: MEDIUM  
**User Story**: As a portfolio manager, I want the system to recommend optimal strategy allocations for my goals.

**Preconditions**:
- Performance history exists for all strategies
- Risk/return preferences can be specified
- Sufficient historical data for optimization

**Test Steps**:
1. Navigate to optimal combination page
2. Set optimization objectives:
   - Return weight: 30%
   - Sharpe ratio weight: 50%
   - Drawdown weight: 20%
3. Select strategies to include: All 6
4. Set constraints:
   - Minimum allocation per strategy: 5%
   - Maximum allocation per strategy: 40%
5. Click "Find Optimal Combination"
6. Review recommended allocations

**Expected Results**:
- ✅ Optimal allocations calculated for all strategies
- ✅ Allocations sum to 100%
- ✅ Constraints are respected
- ✅ Expected metrics displayed (return, Sharpe, drawdown)
- ✅ Comparison to equal-weight portfolio shown
- ✅ Rationale for allocations provided

**Acceptance Criteria**:
- [ ] Allocation percentages displayed for each strategy
- [ ] Total allocations sum to 100%
- [ ] Min/max constraints respected
- [ ] Expected portfolio Sharpe ratio shown
- [ ] Expected portfolio return shown
- [ ] Expected portfolio drawdown shown
- [ ] Improvement over equal-weight quantified
- [ ] Recommendations are actionable

**Test Data**:
```
Strategies: All 6
Objectives: Return 30%, Sharpe 50%, Drawdown 20%
Constraints: 5% min, 40% max per strategy
Expected: Varied allocations based on performance
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-010: Real-Time Stock Ranking**

**Priority**: HIGH  
**User Story**: As a trader, I want to rank multiple stocks to identify the best trading opportunities.

**Preconditions**:
- Multiple symbols available for analysis
- Strategies are operational
- Market data is current

**Test Steps**:
1. Navigate to stock ranking page
2. Select allocation profile: "Aggressive"
3. Enter symbols: GME, AMC, TSLA, NVDA, AMD, AAPL, MSFT
4. Click "Rank Stocks"
5. Review ranked results

**Expected Results**:
- ✅ All symbols analyzed and ranked
- ✅ Rankings based on weighted confidence
- ✅ Top-ranked stocks displayed first
- ✅ Each stock shows action and confidence
- ✅ Filtering options available (e.g., BUY only)
- ✅ Results sortable by different criteria

**Acceptance Criteria**:
- [ ] All symbols analyzed successfully
- [ ] Rankings displayed in descending order (best first)
- [ ] Rank number shown (1, 2, 3, etc.)
- [ ] Weighted confidence percentage shown
- [ ] Action (BUY/SELL/HOLD) displayed for each
- [ ] User can filter by action type
- [ ] User can sort by confidence, symbol, or action
- [ ] Top 3 recommendations highlighted

**Test Data**:
```
Symbols: GME, AMC, TSLA, NVDA, AMD, AAPL, MSFT
Profile: Aggressive
Expected: Ranked list with top opportunities first
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-011: Strategy Parameter Adjustment**

**Priority**: MEDIUM  
**User Story**: As an advanced user, I want to adjust strategy parameters to customize behavior.

**Preconditions**:
- User has admin/advanced permissions
- Strategy parameters are documented
- Default parameters are loaded

**Test Steps**:
1. Navigate to strategy configuration page
2. Select strategy: "Mean Reversion"
3. View current parameters:
   - BB Period: 20
   - BB Std Dev: 2.0
   - RSI Threshold: 30
   - Volume Multiple: 1.5
4. Modify parameters:
   - BB Period: 25
   - RSI Threshold: 35
5. Click "Save Parameters"
6. Run analysis with new parameters
7. Verify changes applied

**Expected Results**:
- ✅ Current parameters displayed clearly
- ✅ Parameters are editable with validation
- ✅ Changes are saved successfully
- ✅ New parameters used in analysis
- ✅ Results differ from default parameters
- ✅ Option to reset to defaults available

**Acceptance Criteria**:
- [ ] All strategy parameters visible
- [ ] Parameters have clear descriptions
- [ ] Value ranges/constraints documented
- [ ] Invalid values rejected with error message
- [ ] Confirmation prompt before saving
- [ ] Changes reflected in next analysis
- [ ] "Reset to Defaults" button works
- [ ] Parameter history tracked

**Test Data**:
```
Strategy: Mean Reversion
Original: BB Period=20, RSI=30
Modified: BB Period=25, RSI=35
Expected: Different analysis results
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-012: Error Handling - Invalid Symbol**

**Priority**: HIGH  
**User Story**: As a user, I want clear error messages when I enter invalid data.

**Preconditions**:
- System is operational
- User has access to analysis interface

**Test Steps**:
1. Navigate to strategy analysis page
2. Enter invalid symbol: "INVALID123"
3. Click "Analyze"
4. Observe error handling

**Expected Results**:
- ✅ System does not crash
- ✅ Clear error message displayed
- ✅ Error explains the problem
- ✅ User can correct input and retry
- ✅ No technical stack traces shown to user

**Acceptance Criteria**:
- [ ] Error message displayed prominently
- [ ] Message is user-friendly (no technical jargon)
- [ ] Suggests corrective action
- [ ] Form remains filled with user input
- [ ] User can edit and resubmit
- [ ] No system errors or crashes
- [ ] Error logged for administrator review

**Test Data**:
```
Invalid Symbols: INVALID123, 123ABC, @@@@, ""
Expected: Clear error message for each
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-013: Error Handling - Insufficient Historical Data**

**Priority**: HIGH  
**User Story**: As a user, I want to know when a strategy cannot be analyzed due to insufficient data.

**Preconditions**:
- Symbol with limited historical data available
- Strategy requires minimum data points

**Test Steps**:
1. Navigate to strategy analysis page
2. Select strategy: "Momentum Quality" (requires 250 days)
3. Enter symbol with only 50 days of data
4. Click "Analyze"
5. Observe error handling

**Expected Results**:
- ✅ System detects insufficient data
- ✅ Error message explains minimum required
- ✅ Message states how many days are available vs. required
- ✅ Suggests using a different strategy or symbol
- ✅ No crash or unexpected behavior

**Acceptance Criteria**:
- [ ] Error detected before analysis attempt
- [ ] Message states: "Requires X days, only Y available"
- [ ] Suggests alternative strategies with lower requirements
- [ ] Provides list of strategies that CAN be used
- [ ] User can select alternative strategy easily
- [ ] Error is logged appropriately

**Test Data**:
```
Symbol: NEW_IPO (50 days data)
Strategy: Momentum Quality (requires 250 days)
Expected: Clear error with alternatives
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-014: Export Results to CSV**

**Priority**: MEDIUM  
**User Story**: As a user, I want to export analysis results to CSV for further analysis.

**Preconditions**:
- Analysis has been completed
- Results are displayed
- User has permission to export

**Test Steps**:
1. Complete any analysis (e.g., portfolio backtest)
2. Click "Export to CSV" button
3. Save file to local system
4. Open CSV file in Excel/spreadsheet application
5. Verify data integrity

**Expected Results**:
- ✅ CSV file downloads successfully
- ✅ File contains all relevant data
- ✅ Data is properly formatted
- ✅ Headers are clear and descriptive
- ✅ File opens without errors in Excel

**Acceptance Criteria**:
- [ ] CSV file downloads within 5 seconds
- [ ] Filename includes timestamp
- [ ] All columns have headers
- [ ] Data types preserved (numbers, dates, text)
- [ ] No data loss or truncation
- [ ] Special characters handled correctly
- [ ] File size is reasonable
- [ ] Compatible with Excel, Google Sheets

**Test Data**:
```
Export Type: Backtest Results
Expected Columns: Date, Symbol, Action, Price, Confidence, Return, etc.
Expected Rows: All trades from backtest
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

### **UAT-015: Performance Under Load**

**Priority**: MEDIUM  
**User Story**: As a user, I expect the system to perform well even when analyzing many stocks.

**Preconditions**:
- System resources available
- Multiple symbols prepared for testing
- Normal system load

**Test Steps**:
1. Navigate to batch analysis page
2. Upload list of 50 symbols
3. Select profile: "Balanced"
4. Click "Analyze All"
5. Monitor processing time and system responsiveness

**Expected Results**:
- ✅ All 50 symbols analyzed within 5 minutes
- ✅ System remains responsive during processing
- ✅ Progress indicator shows status
- ✅ No timeouts or crashes
- ✅ Results are accurate for all symbols

**Acceptance Criteria**:
- [ ] Batch analysis completes within acceptable time
- [ ] Progress bar updates regularly
- [ ] User can cancel operation if needed
- [ ] System doesn't lock up or freeze
- [ ] All symbols analyzed successfully
- [ ] Results match individual analysis
- [ ] Memory usage remains reasonable
- [ ] No database connection errors

**Test Data**:
```
Symbols: 50 symbols from S&P 500
Profile: Balanced
Expected Time: < 5 minutes
Expected: All successful with progress updates
```

**Status**: ⬜ Not Tested | ✅ Passed | ❌ Failed  
**Tested By**: _____________  
**Date Tested**: _____________

---

## UAT Summary Report

### Test Execution Summary

| Category | Total Tests | Passed | Failed | Not Tested |
|----------|------------|--------|--------|------------|
| **Single Strategy Analysis** | 2 | 0 | 0 | 2 |
| **Multi-Strategy Analysis** | 3 | 0 | 0 | 3 |
| **Backtesting** | 3 | 0 | 0 | 3 |
| **Performance Analysis** | 3 | 0 | 0 | 3 |
| **Configuration** | 1 | 0 | 0 | 1 |
| **Error Handling** | 2 | 0 | 0 | 2 |
| **Export/Integration** | 1 | 0 | 0 | 1 |
| **Performance** | 1 | 0 | 0 | 1 |
| **TOTAL** | **15** | **0** | **0** | **15** |

### Overall UAT Status: ⬜ NOT STARTED

### Sign-Off

**Business Owner**: _________________ Date: _______  
**Product Manager**: _________________ Date: _______  
**QA Lead**: _________________ Date: _______  
**Development Lead**: _________________ Date: _______

---

## Appendix A: UAT Test Data Setup

### Required Test Data

**Symbols**:
- Large Cap: AAPL, MSFT, GOOGL
- Mid Cap: AMD, NVDA
- Small Cap: GME, AMC
- Dividend Stocks: JNJ, PG, KO
- Growth Stocks: TSLA, AMZN

**Historical Data Requirements**:
- Minimum: 2 years (2023-2024)
- Preferred: 3 years (2022-2024)
- Data Points: Open, High, Low, Close, Volume
- Frequency: Daily

**Test User Accounts**:
- Basic User: test_trader@example.com
- Advanced User: test_analyst@example.com
- Admin User: test_admin@example.com

### Data Setup Scripts

```bash
# Load test data
php scripts/load_test_data.php

# Create test users
php scripts/create_test_users.php

# Initialize test portfolios
php scripts/initialize_test_portfolios.php
```

---

## Appendix B: UAT Issue Tracking Template

**Issue ID**: UAT-XXX  
**Test Case**: UAT-###  
**Severity**: Critical / High / Medium / Low  
**Description**: [Detailed description of issue]  
**Steps to Reproduce**: [Numbered steps]  
**Expected Result**: [What should happen]  
**Actual Result**: [What actually happened]  
**Screenshots**: [Attach if applicable]  
**Environment**: [Browser, OS, etc.]  
**Reported By**: [Name]  
**Date Reported**: [Date]  
**Status**: Open / In Progress / Resolved / Closed  
**Assigned To**: [Developer name]  
**Resolution**: [How issue was fixed]  
**Verified By**: [Tester name]  
**Date Verified**: [Date]

---

**Document Version**: 1.0  
**Last Updated**: December 2, 2025  
**Next Review**: March 2, 2026
