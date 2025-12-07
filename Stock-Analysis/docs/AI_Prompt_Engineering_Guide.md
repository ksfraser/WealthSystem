# AI Prompt Engineering Guide for Trading Systems

## Executive Summary

Based on CIBC's best practices and analysis of the current prompt implementation, this guide provides improved prompts for AI-driven trading recommendations. The original Python script uses amateur prompts that can be significantly enhanced using professional prompt engineering techniques.

**Key Improvements:**
- ✅ Be specific, not vague (avoid "is this a buy or sell?")
- ✅ Ask for frameworks and assumptions, not just answers
- ✅ Request sources and confidence levels
- ✅ Focus on fundamentals and drivers, not sentiment
- ✅ Use structured, fact-based questions
- ✅ Verify outputs (math, sources, logic)

---

## Table of Contents

1. [CIBC's Core Principles](#cibcs-core-principles)
2. [Current Implementation Analysis](#current-implementation-analysis)
3. [Improved Prompts](#improved-prompts)
4. [Do's and Don'ts](#dos-and-donts)
5. [Verification Checklist](#verification-checklist)
6. [Real-World Examples](#real-world-examples)

---

## CIBC's Core Principles

### 1. **Specificity Over Vagueness**

❌ **Bad:** "What should I buy today?"  
✅ **Good:** "How well was this company's dividend covered by earnings and free cash flow over the last 3 years?"

**Why:** AI is predictive. The more specific your question, the better the answer. Vague questions yield vague, sentiment-driven responses.

### 2. **Focus on Fundamentals, Not Opinions**

❌ **Bad:** "Is AAPL a buy or a sell?"  
✅ **Good:** "Give me the bull and bear case for AAPL based on fundamentals. List assumptions and what would invalidate each case."

**Why:** Forces the AI to reason about fundamentals and keeps the decision in your hands.

### 3. **Request Sources and Assumptions**

✅ **Always include:** "Cite the filings, transcripts or data sources you used. List assumptions and confidence levels."

**Why:** Helps catch hallucinations and compounding errors. Makes outputs verifiable.

### 4. **Use Scenario Analysis**

✅ **Framework:** "Build 3 scenarios — base, upside, downside — for [company] over the next 2 years using conservative assumptions."

**Why:** Plausible ranges and their implications are more useful than single predictions.

### 5. **Ask for "What Would Change Your Mind?"**

✅ **Include:** "What could make this conclusion wrong? What are the leading indicators and risk triggers?"

**Why:** Identifies leading indicators and makes reasoning transparent.

---

## Current Implementation Analysis

### Original Python Prompt (simple_automation.py)

```python
def generate_trading_prompt(portfolio_df, cash, total_equity):
    prompt = f"""You are a professional portfolio analyst. Here is your current portfolio state as of {today}:

[ Holdings ]
{holdings_text}

[ Snapshot ]
Cash Balance: ${cash:,.2f}
Total Equity: ${total_equity:,.2f}

Rules:
- You have ${cash:,.2f} in cash available for new positions
- Prefer U.S. micro-cap stocks (<$300M market cap)
- Full shares only, no options or derivatives
- Use stop-losses for risk management
- Be conservative with position sizing

Analyze the current market conditions and provide specific trading recommendations.

Respond with ONLY a JSON object in this exact format:
{{
    "analysis": "Brief market analysis",
    "trades": [
        {{
            "action": "buy",
            "ticker": "SYMBOL",
            "shares": 100,
            "price": 25.50,
            "stop_loss": 20.00,
            "reason": "Brief rationale"
        }}
    ],
    "confidence": 0.8
}}"""
```

### Current PHP Implementation (LLMTradingAssistant_v2.php)

```php
private function generatePrompt(...): string {
    $prompt = <<<PROMPT
You are a professional portfolio analyst. Here is your current portfolio state as of {$today}:

[ Holdings ]
{$holdingsText}

[ Snapshot ]
Cash Balance: \${$cashBalance}
Total Equity: \${$totalEquity}

Rules:
- You have \${$cashBalance} in cash available for new positions
- Prefer U.S. {$focus} stocks (<\${$marketCapLimit} market cap)
- Maximum position size: {$maxPositionSize}% of portfolio
- Maintain minimum cash reserve: \${$minCashReserve}
- Full shares only, no options or derivatives
- Use stop-losses for risk management
- Be conservative with position sizing
- Only recommend trades with confidence >= {$minConfidence}

Analyze the current market conditions and provide specific trading recommendations.

Respond with ONLY a valid JSON object in this exact format:
{
    "analysis": "Brief market analysis and reasoning",
    "trades": [...],
    "confidence": 0.8
}
PROMPT;
}
```

### Problems Identified

| Issue | Impact | CIBC Principle Violated |
|-------|--------|------------------------|
| **Too vague:** "Analyze the current market conditions" | Gets generic sentiment instead of specific analysis | Specificity |
| **No source requirement** | Can't verify claims or catch hallucinations | Sources & Assumptions |
| **No scenario analysis** | Single prediction instead of ranges | Scenario Analysis |
| **No invalidation triggers** | Can't identify what would change the thesis | "What Changes Your Mind?" |
| **Brief rationale only** | Shallow reasoning, hard to verify | Fundamentals Over Opinions |
| **No fundamental metrics** | Missing P/E, revenue growth, cash flow, etc. | Focus on Fundamentals |
| **No risk triggers** | Doesn't identify leading indicators | Verification |

---

## Improved Prompts

### Version 1: Enhanced Fundamental Analysis (Recommended)

```php
private function generatePromptV1Enhanced(
    array $holdings,
    float $cashBalance,
    float $totalEquity,
    array $config
): string {
    $today = date('Y-m-d');
    $holdingsText = $this->formatHoldings($holdings);
    
    // Config extraction...
    
    return <<<PROMPT
You are a professional portfolio analyst specializing in micro-cap stocks. Today is {$today}.

=== PORTFOLIO STATE ===

Current Holdings:
{$holdingsText}

Cash Available: \${$cashBalance}
Total Portfolio Value: \${$totalEquity}
Max Position Size: {$maxPositionSize}% (\$" . round($totalEquity * ($maxPositionSize / 100), 2) . ")
Min Cash Reserve: \${$minCashReserve}

=== INVESTMENT MANDATE ===

Focus: U.S. {$focus} stocks (market cap < \${$marketCapLimit})
Position Limits: {$maxPositionSize}% max, full shares only
Risk Tools: Stop-losses required on all positions
Confidence Threshold: Minimum {$minConfidence} to recommend

=== ANALYSIS FRAMEWORK ===

For each potential trade, analyze:

1. **Fundamental Drivers**
   - Revenue growth trajectory (last 4 quarters)
   - Profit margins and trend (gross, operating, net)
   - Cash flow quality (operating CF vs net income)
   - Balance sheet health (debt/equity, current ratio)
   - Valuation metrics (P/E, P/S, EV/EBITDA vs peers)

2. **Bull Case**
   - Key catalysts over next 6-12 months
   - Competitive advantages or moats
   - Addressable market size and penetration
   - Management track record
   - Assumptions that must hold true

3. **Bear Case**
   - Primary risks to thesis
   - What could invalidate the investment?
   - Regulatory, competitive, or macro headwinds
   - Leading indicators to monitor

4. **Scenario Analysis**
   - Base case: Expected outcome with conservative assumptions
   - Upside: What would need to happen for 2x returns?
   - Downside: What triggers a stop-loss or exit?

5. **Position Sizing Logic**
   - Risk/reward ratio calculation
   - Why this position size vs others?
   - Portfolio diversification impact

=== VERIFICATION REQUIREMENTS ===

For each recommendation:
- Cite specific data sources (e.g., "Q3 2024 10-Q filing showed...")
- State confidence level (0.0 to 1.0) with reasoning
- List 3 key assumptions
- Identify 2-3 risk triggers that would invalidate thesis
- Provide specific entry price and stop-loss with rationale

=== OUTPUT FORMAT ===

Respond with ONLY valid JSON:

{
  "market_analysis": {
    "summary": "Current macro environment assessment",
    "key_themes": ["Theme 1", "Theme 2"],
    "risks": ["Risk 1", "Risk 2"]
  },
  "trades": [
    {
      "action": "buy" | "sell" | "hold",
      "ticker": "SYMBOL",
      "shares": 100,
      "entry_price": 25.50,
      "stop_loss": 20.00,
      "price_target": 35.00,
      
      "fundamentals": {
        "revenue_growth_yoy": "25%",
        "operating_margin": "15%",
        "pe_ratio": 18.5,
        "debt_to_equity": 0.3,
        "key_metric": "Important company-specific metric"
      },
      
      "bull_case": {
        "thesis": "Concise bull thesis",
        "catalysts": ["Catalyst 1", "Catalyst 2"],
        "assumptions": ["Assumption 1", "Assumption 2", "Assumption 3"]
      },
      
      "bear_case": {
        "thesis": "Concise bear thesis",
        "risks": ["Risk 1", "Risk 2"],
        "invalidation_triggers": ["What would prove this wrong?"]
      },
      
      "scenarios": {
        "base": {"return": "20%", "probability": "50%", "conditions": "Conditions"},
        "upside": {"return": "100%", "probability": "20%", "conditions": "Conditions"},
        "downside": {"return": "-20%", "probability": "30%", "conditions": "Conditions"}
      },
      
      "position_logic": "Why this position size makes sense",
      "sources": ["Source 1", "Source 2"],
      "confidence": 0.75,
      "holding_period": "6-12 months"
    }
  ],
  "portfolio_adjustments": {
    "rebalancing_needed": false,
    "diversification_notes": "Portfolio concentration analysis",
    "risk_level": "Current portfolio risk assessment"
  },
  "overall_confidence": 0.8,
  "reasoning": "Overall reasoning for recommendations"
}

=== CRITICAL RULES ===

- Do NOT recommend trades if confidence < {$minConfidence}
- Do NOT exceed available cash (\${$cashBalance})
- Do NOT exceed max position size per holding
- Do NOT make up data — if unknown, state "Data unavailable"
- Do verify math — position sizes must sum correctly
- Do focus on fundamentals, NOT social media sentiment
- Do provide specific, verifiable claims

If no high-confidence trades exist, return empty trades array with explanation.
PROMPT;
}
```

### Version 2: Streamlined with Mandatory Sources

```php
private function generatePromptV2Streamlined(
    array $holdings,
    float $cashBalance,
    float $totalEquity,
    array $config
): string {
    // Similar setup...
    
    return <<<PROMPT
Role: Professional portfolio analyst, {$today}

=== PORTFOLIO ===
{$holdingsText}

Cash: \${$cashBalance} | Total: \${$totalEquity} | Max Position: {$maxPositionSize}%

=== TASK ===

Recommend high-conviction trades for micro-cap stocks (< \${$marketCapLimit} market cap).

For EACH recommendation, provide:

1. **Fundamental Metrics** (cite sources)
   - Last 4 quarters: revenue, margins, cash flow
   - Valuation: P/E, P/S vs industry median
   - Balance sheet: Debt/equity, current ratio

2. **Bull + Bear Case**
   - Bull: Key thesis, 3 catalysts, 3 assumptions
   - Bear: Main risks, what invalidates thesis?

3. **3 Scenarios** (base/upside/downside)
   - Conditions, expected return, probability

4. **Position Sizing Logic**
   - Why this size? Risk/reward calculation

5. **Data Sources**
   - Cite filings (e.g., "10-Q Q3 2024, page 12")
   - No hallucinations — state if data unavailable

=== OUTPUT ===

JSON format:
{
  "analysis": "Market overview (2-3 sentences)",
  "trades": [
    {
      "ticker": "SYM",
      "action": "buy",
      "shares": 100,
      "entry": 25.50,
      "stop_loss": 20.00,
      "target": 35.00,
      "fundamentals": {"revenue_growth": "25%", "pe": 18, "margin": "15%"},
      "bull_case": {"thesis": "...", "catalysts": [...], "assumptions": [...]},
      "bear_case": {"risks": [...], "invalidators": [...]},
      "scenarios": {"base": {...}, "upside": {...}, "downside": {...}},
      "position_logic": "Why this size fits portfolio",
      "sources": ["10-Q Q3 2024", "Earnings call Nov 5"],
      "confidence": 0.75
    }
  ],
  "confidence": 0.8
}

Rules:
- Confidence >= {$minConfidence} to recommend
- Total cost <= \${$cashBalance}
- Verify all math
- No sentiment-driven picks
- Empty trades array if nothing meets criteria
PROMPT;
}
```

### Version 3: Question-Based Discovery

```php
private function generatePromptV3Discovery(
    array $holdings,
    float $cashBalance,
    string $sector = null,
    string $ticker = null
): string {
    // For research-focused prompts
    
    if ($ticker) {
        // Deep dive on specific stock
        return <<<PROMPT
Analyze {$ticker} for potential investment. Answer these specific questions:

1. **Financial Health**
   - What was the revenue growth rate over the last 3 years? Cite quarterly results.
   - How well is the dividend (if any) covered by free cash flow? Show calculation.
   - What is the debt-to-equity ratio and trend?

2. **Business Quality**
   - What are the top 3 factors that drive revenue for this company?
   - What competitive advantages exist? Are they durable?
   - How does management allocate capital? Share buybacks? Acquisitions?

3. **Valuation**
   - Current P/E, P/S, EV/EBITDA vs 5-year average and peers
   - Is the company cheap or expensive relative to growth?

4. **Risk Assessment**
   - What are 3 scenarios (base, bull, bear) with probabilities?
   - What specific events would trigger a sell decision?
   - What leading indicators should be monitored quarterly?

5. **Sources**
   - Cite specific filings, earnings calls, or financial statements
   - Note data confidence level for each claim

Respond in JSON with answers to each question.
PROMPT;
    }
    
    // Sector exploration
    return <<<PROMPT
Sector Research: {$sector}

1. Given today's macro environment (interest rates, inflation, growth), which sectors might suit a moderate risk profile with 12-24 month horizon?

2. For the {$sector} sector specifically:
   - What are the typical drivers of performance?
   - Which metrics matter most (margins, revenue growth, etc.)?
   - What are key risks over the next 12-24 months?
   - What would change the investment outlook?

3. Provide shortlist of 5 companies in {$sector}:
   - High-level fundamentals (valuation, growth, income)
   - Why each might fit a micro-cap portfolio
   - Key differentiators

Format: JSON with sector_analysis and company_shortlist arrays.
Sources required for all claims.
PROMPT;
}
```

---

## Do's and Don'ts

### ✅ DO

| Practice | Example | Why |
|----------|---------|-----|
| **Be specific** | "What was AAPL's revenue growth last 3 years?" | Gets facts, not sentiment |
| **Ask for bull + bear** | "Give me both bull and bear case with assumptions" | Balanced view, keeps decision yours |
| **Request scenarios** | "Build base/upside/downside scenarios" | Plausible ranges > single predictions |
| **Demand sources** | "Cite filings, transcripts, data sources" | Catches hallucinations |
| **Ask about invalidation** | "What would prove this thesis wrong?" | Identifies risk triggers |
| **Focus on fundamentals** | "How is cash flow? What drives margins?" | Facts over narratives |
| **Verify math** | "Show calculation for payout ratio" | AI can miscalculate |
| **Use conservative assumptions** | "Use conservative assumptions for scenarios" | Reduces overoptimism |
| **Request confidence levels** | "State confidence and reasoning" | Transparent uncertainty |
| **Keep tasks modular** | One question per prompt or clear sections | Prevents error compounding |

### ❌ DON'T

| Mistake | Example | Problem |
|---------|---------|---------|
| **Vague questions** | "What should I buy today?" | Sentiment-driven, not analytical |
| **Binary questions** | "Is this a buy or sell?" | Forces hedged or biased answer |
| **Force answers** | "Give me 5 recommendations no matter what" | Invites hallucinations |
| **Trust math blindly** | Accept ratios without verification | AI miscalculates often |
| **Ignore sources** | Accept claims without citations | Can't verify, might be made up |
| **Long multi-step chains** | Ask for 10-step analysis in one prompt | Small errors snowball |
| **Rely on sentiment** | "What's the buzz around this stock?" | Mirrors social media bias |
| **Skip verification** | Use output directly without checks | Dangerous for real money |
| **Accept vague reasoning** | "Good fundamentals" without specifics | No way to evaluate |

---

## Verification Checklist

After receiving AI output, always verify:

### 1. ✅ Check the Math

- [ ] Spot-check all ratios (P/E, debt/equity, margins)
- [ ] Verify growth rates (use calculator)
- [ ] Confirm position sizes sum correctly
- [ ] Check that cost <= available cash
- [ ] Verify stop-loss placement makes sense (not too tight/loose)

**How:** Use a calculator or official filings. If AI says "P/E ratio is 18", pull the stock price and EPS to verify.

### 2. ✅ Check the Sources

- [ ] Ask: "Cite the filings, transcripts or data sources you used"
- [ ] Follow links to confirm data exists
- [ ] Prefer primary sources (SEC filings, earnings calls)
- [ ] Be skeptical of "recent news" — provide documents yourself

**Red Flag:** If AI says "according to recent reports" without specifics, it's likely making it up.

### 3. ✅ Check Assumptions and Logic

- [ ] Ask: "List all assumptions and confidence levels"
- [ ] Ask: "What could make this conclusion wrong?"
- [ ] Verify assumptions are conservative, not optimistic
- [ ] Check for circular reasoning
- [ ] Ensure leading indicators are identified

**Example:**
- **Good:** "Assumes 15% revenue growth (vs 20% last year), validated by management guidance"
- **Bad:** "Stock will do well because market likes it"

### 4. ✅ Check for Hallucinations

**Common hallucinations:**
- Fake ticker symbols
- Made-up financial metrics
- Non-existent filings ("Q5 2024 earnings")
- Impossible numbers (400% margins)
- Companies that don't exist

**How to spot:**
- Cross-reference tickers with actual exchanges
- Verify company names with search
- Check if dates make sense (Q5 doesn't exist)
- Sanity-check numbers (30% margins rare, 400% impossible)

### 5. ✅ Validate Against Real Market Data

- [ ] Check current stock price against AI's price
- [ ] Verify market cap classification (micro/small/mid)
- [ ] Confirm sector classification
- [ ] Check if stock is actually tradeable (not delisted, not OTC if not allowed)
- [ ] Verify stop-loss price is below entry (for longs)

---

## Real-World Examples

### Example 1: Bad Prompt → Improved

❌ **Original (Bad):**
```
"Give me 5 micro-cap stocks to buy today."
```

**Problems:**
- Vague
- Forces answer (invites hallucinations)
- No criteria specified
- No verification possible

✅ **Improved:**
```
"Analyze the biotechnology sector for micro-cap opportunities (<$300M market cap).

For each candidate:
1. Provide last 4 quarters of revenue and cash burn rate
2. State pipeline status (Phase 1/2/3, expected catalysts)
3. Calculate cash runway (current cash / quarterly burn)
4. Compare valuation (EV/Sales) to peers
5. Bull case: What approval or partnership would drive 2x returns?
6. Bear case: What would cause stock to drop 50%?
7. Cite sources: 10-Q filings, press releases

Provide 2-3 candidates only if they meet criteria:
- Confidence >= 0.70
- Cash runway >= 18 months
- Credible catalyst within 12 months

If no candidates meet criteria, explain why and suggest wait-and-see approach."
```

**Improvements:**
- Specific sector and criteria
- Doesn't force answer
- Requests verification data
- Includes scenarios and sources
- Allows for "no recommendation"

---

### Example 2: Portfolio Recommendation (Enhanced)

❌ **Original (Simple):**
```
"I have $10,000 cash. What should I buy?"
```

✅ **Improved:**
```
"I have $10,000 cash in a portfolio focused on micro-cap growth stocks. Current holdings:
- ABCD: 200 shares at $15 (cost basis $3,000)
- WXYZ: 300 shares at $8 (cost basis $2,400)

Total portfolio value: $15,000

Analysis required:

1. **Portfolio Review**
   - Current sector concentration
   - Risk level assessment
   - Diversification score

2. **New Position Recommendations**
   - Suggest 1-2 new positions that complement existing holdings
   - Avoid overlapping sectors
   - Target different risk profiles
   - Max position size: $3,000 (20% of portfolio)

3. **For each recommendation:**
   - Fundamental metrics (last 4 quarters)
   - Why this fits portfolio (diversification, risk balance)
   - Bull/bear case with scenarios
   - Stop-loss and target prices
   - Position sizing logic
   - Data sources

4. **Rebalancing Analysis**
   - Should existing positions be trimmed or increased?
   - Risk triggers for each current holding

Rules:
- Confidence >= 0.75 to recommend
- Keep $1,000 cash reserve minimum
- No single position > 20% of portfolio
- Cite all sources

If no high-conviction trades exist, explain why and suggest patience."
```

---

### Example 3: Earnings Analysis

❌ **Original:**
```
"What did you think of AAPL's earnings?"
```

✅ **Improved:**
```
"Analyze Apple Inc (AAPL) Q4 2024 earnings release.

1. **Results vs Expectations**
   - Revenue: Actual vs analyst consensus (cite source)
   - EPS: Actual vs consensus
   - Key segments: iPhone, Services, Mac, iPad, Wearables performance
   - Geographic breakout: Notable trends

2. **Management Commentary**
   - Changes in forward guidance
   - Capital allocation plans (dividends, buybacks)
   - New products or services mentioned
   - Risk factors highlighted

3. **Market Reaction**
   - Stock price movement post-earnings (%, context)
   - What explains the move? Beat/miss? Guidance? Macro?
   - Analyst changes (upgrades/downgrades)

4. **Investment Implications**
   - Does this change the bull/bear thesis?
   - What metrics should be monitored next quarter?
   - Risk triggers to watch

5. **Sources**
   - Cite earnings release, 10-Q filing, earnings call transcript
   - Note page numbers for key claims

Respond with structured analysis. Highlight surprises (positive/negative)."
```

---

### Example 4: Sector Research

✅ **CIBC-Style Sector Prompt:**
```
"I'm researching the renewable energy sector for a moderate risk, 2-year investment horizon.

1. **Sector Analysis**
   - Current macro environment: How do interest rates, inflation, energy policy affect this sector?
   - Key performance drivers: What metrics matter most? (capacity growth, power prices, subsidies)
   - Typical valuation ranges: P/E, EV/EBITDA for sector

2. **Risk Assessment**
   - Top 3 risks over next 24 months
   - What would change the investment outlook (bullish or bearish)?
   - Leading indicators to monitor

3. **Micro-Cap Opportunities**
   - Shortlist 3-5 micro-cap companies (<$300M) in renewable energy
   - For each:
     * Revenue growth trajectory
     * Profit margins and cash flow
     * Valuation vs peers
     * Competitive positioning
     * Key catalyst in next 12 months

4. **Comparison Matrix**
   - Compare shortlist on: Growth, Profitability, Valuation, Risk
   - Which 1-2 stand out and why?

5. **Sources**
   - Cite industry reports, company filings, analyst reports
   - State confidence level for each claim

Provide analysis even if conclusion is 'wait and see.' Transparency preferred over false confidence."
```

---

## Prompt Templates Library

### Template 1: Stock Deep Dive

```
Analyze {TICKER} as of {DATE} for investment potential.

**Fundamental Analysis:**
- Revenue: Last 4 quarters, YoY growth
- Margins: Gross, Operating, Net (trends)
- Cash Flow: Operating CF vs Net Income
- Balance Sheet: Debt/Equity, Current Ratio
- Valuation: P/E, P/S, EV/EBITDA vs peers
- Sources: Cite specific filings

**Investment Thesis:**
- Bull Case: 3 catalysts, 3 key assumptions
- Bear Case: 3 risks, what invalidates thesis?
- Scenarios: Base/Upside/Downside with probabilities

**Trade Setup:**
- Entry price, stop-loss, target
- Position size logic
- Holding period
- Confidence level

Respond in JSON. No hallucinations — state if data unavailable.
```

### Template 2: Portfolio Rebalancing

```
Current portfolio: {HOLDINGS}
Cash: {CASH}
Total value: {EQUITY}

**Analysis:**
1. Sector concentration and risk level
2. Which positions to trim/add/hold? Why?
3. New positions that complement portfolio (1-2 max)
4. Risk triggers for each holding

**For each recommendation:**
- Fundamental data (cite sources)
- Bull/bear cases
- Position size logic
- Confidence >= 0.70

Respond in JSON.
```

### Template 3: Weekly Market Check

```
Portfolio: {HOLDINGS}
Date: {TODAY}

**Weekly Review:**
1. Major macro events this week (Fed, earnings, geopolitical)
2. How do these affect my holdings? Sector impacts?
3. Should any positions be adjusted? Risk triggers hit?
4. New opportunities emerged? (Brief scan, not deep dive)
5. Action items for next week

Keep concise. Focus on portfolio-relevant information.
Sources required for factual claims.
```

### Template 4: Risk Assessment

```
Holding: {TICKER} - {SHARES} shares at ${COST_BASIS}
Current price: ${CURRENT_PRICE}

**Risk Analysis:**
1. What are 3 scenarios that would cause 25%+ drawdown?
2. What leading indicators predict these scenarios?
3. How to monitor these indicators?
4. What's the appropriate stop-loss level? Why?
5. Are there hedging opportunities?

Cite recent filings, earnings calls for context.
Respond with risk triggers and monitoring plan.
```

---

## Implementation in Code

### PHP Implementation (LLMTradingAssistant_v2.php)

```php
class LLMTradingAssistant
{
    // Add prompt version selector
    public function getRecommendations(
        array $holdings,
        float $cashBalance,
        float $totalEquity,
        array $config = []
    ): TradingRecommendation {
        $promptVersion = $config['prompt_version'] ?? 'enhanced'; // 'simple', 'enhanced', 'discovery'
        
        $prompt = match($promptVersion) {
            'simple' => $this->generatePromptSimple(...),
            'enhanced' => $this->generatePromptEnhanced(...),
            'discovery' => $this->generatePromptDiscovery(...),
            default => $this->generatePromptEnhanced(...),
        };
        
        // Rest of implementation...
    }
    
    private function generatePromptEnhanced(
        array $holdings,
        float $cashBalance,
        float $totalEquity,
        array $config
    ): string {
        // Use Version 1 Enhanced from above
        // ... (full implementation from "Version 1: Enhanced Fundamental Analysis")
    }
    
    private function generatePromptSimple(...): string {
        // Keep original for backwards compatibility
        // ... (current simple implementation)
    }
    
    private function generatePromptDiscovery(...): string {
        // Question-based research format
        // ... (Version 3 implementation)
    }
}
```

### Python Implementation (simple_automation.py)

```python
def generate_trading_prompt_enhanced(
    portfolio_df: pd.DataFrame,
    cash: float,
    total_equity: float,
    config: dict = None
) -> str:
    """Generate enhanced trading prompt with CIBC best practices"""
    
    config = config or {}
    today = last_trading_date().date().isoformat()
    
    # Format holdings
    holdings_text = portfolio_df.to_string(index=False) if not portfolio_df.empty else "No holdings"
    
    max_position_pct = config.get('max_position_pct', 10)
    max_position_size = total_equity * (max_position_pct / 100)
    min_confidence = config.get('min_confidence', 0.70)
    market_cap_limit = config.get('market_cap_limit', 300_000_000)
    
    prompt = f"""You are a professional portfolio analyst specializing in micro-cap stocks. Today is {today}.

=== PORTFOLIO STATE ===

Current Holdings:
{holdings_text}

Cash Available: ${cash:,.2f}
Total Portfolio Value: ${total_equity:,.2f}
Max Position Size: {max_position_pct}% (${max_position_size:,.2f})

=== INVESTMENT MANDATE ===

Focus: U.S. micro-cap stocks (market cap < ${market_cap_limit:,.0f})
Position Limits: {max_position_pct}% max per position, full shares only
Risk Tools: Stop-losses required on all positions
Confidence Threshold: Minimum {min_confidence} to recommend

=== ANALYSIS FRAMEWORK ===

For each potential trade, analyze:

1. **Fundamental Drivers**
   - Revenue growth trajectory (last 4 quarters)
   - Profit margins and trend (gross, operating, net)
   - Cash flow quality (operating CF vs net income)
   - Balance sheet health (debt/equity, current ratio)
   - Valuation metrics (P/E, P/S, EV/EBITDA vs peers)

2. **Bull Case**
   - Key catalysts over next 6-12 months
   - Competitive advantages or moats
   - Management track record
   - Assumptions that must hold true

3. **Bear Case**
   - Primary risks to thesis
   - What could invalidate the investment?
   - Leading indicators to monitor

4. **Scenario Analysis**
   - Base case: Expected outcome
   - Upside: What drives 2x returns?
   - Downside: What triggers stop-loss?

=== OUTPUT FORMAT ===

Respond with ONLY valid JSON:

{{
  "market_analysis": {{
    "summary": "Current environment",
    "key_themes": ["Theme 1", "Theme 2"],
    "risks": ["Risk 1", "Risk 2"]
  }},
  "trades": [
    {{
      "action": "buy",
      "ticker": "SYMBOL",
      "shares": 100,
      "entry_price": 25.50,
      "stop_loss": 20.00,
      "price_target": 35.00,
      "fundamentals": {{
        "revenue_growth_yoy": "25%",
        "operating_margin": "15%",
        "pe_ratio": 18.5,
        "debt_to_equity": 0.3
      }},
      "bull_case": {{
        "thesis": "Brief thesis",
        "catalysts": ["Catalyst 1", "Catalyst 2"],
        "assumptions": ["Assumption 1", "Assumption 2"]
      }},
      "bear_case": {{
        "risks": ["Risk 1", "Risk 2"],
        "invalidation_triggers": ["Trigger 1"]
      }},
      "scenarios": {{
        "base": {{"return": "20%", "probability": "50%"}},
        "upside": {{"return": "100%", "probability": "20%"}},
        "downside": {{"return": "-20%", "probability": "30%"}}
      }},
      "position_logic": "Why this position size",
      "sources": ["Source 1", "Source 2"],
      "confidence": 0.75
    }}
  ],
  "overall_confidence": 0.8
}}

=== CRITICAL RULES ===

- Do NOT recommend if confidence < {min_confidence}
- Do NOT exceed available cash (${cash:,.2f})
- Do NOT make up data — state if unavailable
- Do verify math — totals must be correct
- Do focus on fundamentals, NOT sentiment
- Do provide verifiable claims with sources

If no high-confidence trades, return empty trades array with explanation.
"""
    
    return prompt


# Backwards compatible wrapper
def generate_trading_prompt(portfolio_df, cash, total_equity, enhanced=True, config=None):
    """Generate trading prompt - defaults to enhanced version"""
    if enhanced:
        return generate_trading_prompt_enhanced(portfolio_df, cash, total_equity, config)
    else:
        # Original simple version (keep for compatibility)
        return generate_trading_prompt_simple(portfolio_df, cash, total_equity)
```

---

## Testing and Validation

### Validation Framework

```php
class PromptValidator
{
    public function validateRecommendation(TradingRecommendation $rec): array
    {
        $issues = [];
        
        // 1. Check sources
        foreach ($rec->trades as $trade) {
            if (empty($trade->sources)) {
                $issues[] = "Missing sources for {$trade->ticker}";
            }
        }
        
        // 2. Check math
        $totalCost = array_sum(array_map(
            fn($t) => $t->shares * $t->price,
            $rec->trades
        ));
        
        // 3. Check assumptions
        foreach ($rec->trades as $trade) {
            if (empty($trade->bullCase->assumptions ?? [])) {
                $issues[] = "Missing assumptions for {$trade->ticker}";
            }
        }
        
        // 4. Check confidence
        if ($rec->confidence < 0.7) {
            $issues[] = "Overall confidence too low: {$rec->confidence}";
        }
        
        return $issues;
    }
}
```

---

## Key Takeaways

1. **Be Specific:** "How well was the dividend covered by FCF over 3 years?" beats "Is this a buy?"

2. **Demand Sources:** Always ask "Cite the filings, transcripts or data sources you used."

3. **Bull + Bear:** Get both cases with assumptions. Decision stays with you.

4. **Scenarios > Predictions:** "Build 3 scenarios (base/upside/downside)" gives plausible ranges.

5. **Ask "What Changes Your Mind?":** Identifies risk triggers and leading indicators.

6. **Verify Everything:** Check math, sources, and logic. AI can hallucinate.

7. **Fundamentals > Sentiment:** Focus on revenue, margins, cash flow, not social media buzz.

8. **Modular Tasks:** Keep prompts focused. Long chains compound errors.

9. **Conservative Assumptions:** Reduces AI overoptimism.

10. **Allow "No Recommendation":** Better than forced, low-confidence picks.

---

## References

- **CIBC Article:** "Put AI to work in your investing strategy" (Chris Patterson, Oct 30, 2025)
- **Current Implementation:** `simple_automation.py` (Python), `LLMTradingAssistant_v2.php` (PHP)
- **OpenAI Best Practices:** https://platform.openai.com/docs/guides/prompt-engineering
- **SEC Filings:** https://www.sec.gov/edgar

---

**Last Updated:** December 6, 2025  
**Version:** 1.0  
**Author:** WealthSystem Team
