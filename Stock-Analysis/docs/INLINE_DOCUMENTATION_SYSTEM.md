# Inline Documentation System

**Version**: 1.0  
**Purpose**: JSON-based metadata for tooltips, help modals, and contextual documentation  
**Last Updated**: December 3, 2025  

---

## Overview

This system provides structured metadata for all indicators, strategies, and patterns that can be consumed by the frontend for tooltips, help modals, and contextual documentation.

### Architecture

```
Backend (PHP)                    Frontend (React/Vue/etc)
┌────────────────────┐          ┌─────────────────────────┐
│ Documentation      │          │ Tooltip Component       │
│ Metadata Generator │◄─────────┤ (fetches on demand)     │
│                    │   API    │                         │
│ • indicators.json  │          │ HelpModal Component     │
│ • strategies.json  │          │ (detailed views)        │
│ • patterns.json    │          │                         │
│ • glossary.json    │          │ ContextualHelp          │
└────────────────────┘          │ (inline documentation)  │
                                 └─────────────────────────┘
```

---

## JSON Schema Definitions

### Indicator Metadata Schema

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "properties": {
    "id": {
      "type": "string",
      "description": "Unique identifier (e.g., 'rsi', 'macd')"
    },
    "name": {
      "type": "string",
      "description": "Full name of indicator"
    },
    "shortName": {
      "type": "string",
      "description": "Abbreviated name (for UI)"
    },
    "category": {
      "type": "string",
      "enum": ["momentum", "trend", "volatility", "volume", "statistics"]
    },
    "description": {
      "type": "object",
      "properties": {
        "short": { "type": "string", "maxLength": 100 },
        "full": { "type": "string" }
      }
    },
    "interpretation": {
      "type": "object",
      "properties": {
        "bullish": { "type": "string" },
        "bearish": { "type": "string" },
        "neutral": { "type": "string" }
      }
    },
    "range": {
      "type": "object",
      "properties": {
        "min": { "type": "number" },
        "max": { "type": "number" },
        "overbought": { "type": "number" },
        "oversold": { "type": "number" }
      }
    },
    "parameters": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "name": { "type": "string" },
          "type": { "type": "string" },
          "default": {},
          "min": { "type": "number" },
          "max": { "type": "number" },
          "description": { "type": "string" }
        }
      }
    },
    "formula": {
      "type": "object",
      "properties": {
        "simple": { "type": "string" },
        "latex": { "type": "string" },
        "steps": { "type": "array", "items": { "type": "string" } }
      }
    },
    "usage": {
      "type": "object",
      "properties": {
        "bestFor": { "type": "array", "items": { "type": "string" } },
        "avoidWhen": { "type": "array", "items": { "type": "string" } },
        "combinesWith": { "type": "array", "items": { "type": "string" } }
      }
    },
    "examples": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "scenario": { "type": "string" },
          "value": { "type": "number" },
          "signal": { "type": "string" },
          "action": { "type": "string" }
        }
      }
    },
    "resources": {
      "type": "object",
      "properties": {
        "documentation": { "type": "string", "format": "uri" },
        "tutorial": { "type": "string", "format": "uri" },
        "video": { "type": "string", "format": "uri" }
      }
    }
  },
  "required": ["id", "name", "category", "description"]
}
```

---

## Example: RSI Indicator Metadata

```json
{
  "id": "rsi",
  "name": "Relative Strength Index",
  "shortName": "RSI",
  "category": "momentum",
  "description": {
    "short": "Momentum oscillator measuring speed and magnitude of price changes (0-100 scale)",
    "full": "RSI is a momentum oscillator that measures the speed and magnitude of recent price changes to evaluate overbought or oversold conditions. It ranges from 0 to 100, with readings above 70 generally considered overbought and below 30 oversold."
  },
  "interpretation": {
    "bullish": "RSI < 30 indicates oversold conditions. Price may be due for a bounce. Look for RSI to cross back above 30 as confirmation.",
    "bearish": "RSI > 70 indicates overbought conditions. Price may be due for a pullback. Look for RSI to cross back below 70 as confirmation.",
    "neutral": "RSI between 40-60 indicates balanced momentum. No strong directional bias. Wait for breakout or use other indicators.",
    "divergence": {
      "bullish": "Price makes new low but RSI doesn't (higher low) → Bullish divergence, potential reversal up",
      "bearish": "Price makes new high but RSI doesn't (lower high) → Bearish divergence, potential reversal down"
    }
  },
  "range": {
    "min": 0,
    "max": 100,
    "overbought": 70,
    "oversold": 30,
    "neutral": 50
  },
  "parameters": [
    {
      "name": "period",
      "type": "integer",
      "default": 14,
      "min": 2,
      "max": 50,
      "description": "Number of periods to calculate RSI over. Default is 14 (days for daily charts)."
    }
  ],
  "formula": {
    "simple": "RSI = 100 - (100 / (1 + RS)), where RS = Avg Gain / Avg Loss",
    "latex": "RSI = 100 - \\frac{100}{1 + RS}, \\quad RS = \\frac{\\text{Avg Gain}}{\\text{Avg Loss}}",
    "steps": [
      "1. Calculate price changes for each period",
      "2. Separate gains (positive changes) from losses (negative changes)",
      "3. Calculate average gain and average loss over N periods",
      "4. Calculate RS (Relative Strength) = Avg Gain / Avg Loss",
      "5. Calculate RSI = 100 - (100 / (1 + RS))"
    ]
  },
  "usage": {
    "bestFor": [
      "Identifying overbought/oversold conditions",
      "Spotting divergences (price vs RSI)",
      "Confirming trend strength",
      "Short to medium-term trading (swing trading)"
    ],
    "avoidWhen": [
      "In strong trending markets (can stay overbought/oversold for extended periods)",
      "As sole trading signal (always confirm with other indicators)",
      "In low-volatility, ranging markets (frequent false signals)"
    ],
    "combinesWith": [
      "MACD (trend confirmation)",
      "Bollinger Bands (volatility context)",
      "Volume indicators (OBV, A/D Line)",
      "Moving Averages (trend direction)"
    ]
  },
  "examples": [
    {
      "scenario": "Strong Oversold",
      "value": 25,
      "signal": "Bullish",
      "action": "Consider buying if RSI crosses back above 30 with volume confirmation"
    },
    {
      "scenario": "Moderate Overbought",
      "value": 72,
      "signal": "Bearish",
      "action": "Consider taking profits or tightening stop loss. Not necessarily sell signal in strong uptrend."
    },
    {
      "scenario": "Neutral Momentum",
      "value": 52,
      "signal": "Neutral",
      "action": "No clear signal. Wait for RSI to break above 60 (bullish) or below 40 (bearish)."
    },
    {
      "scenario": "Bullish Divergence",
      "value": 35,
      "priceAction": "New low",
      "rsiAction": "Higher low",
      "signal": "Strong Bullish",
      "action": "Potential reversal. Enter long on confirmation (price breaks above recent high)."
    }
  ],
  "thresholds": [
    {
      "condition": "rsi > 80",
      "level": "Extremely Overbought",
      "color": "#DC2626",
      "icon": "⚠️",
      "message": "RSI is extremely high. Strong downside risk. Consider exiting or reducing position."
    },
    {
      "condition": "rsi > 70 && rsi <= 80",
      "level": "Overbought",
      "color": "#F59E0B",
      "icon": "⚡",
      "message": "RSI is overbought. Price may pull back. Watch for reversal signals."
    },
    {
      "condition": "rsi >= 40 && rsi <= 60",
      "level": "Neutral",
      "color": "#6B7280",
      "icon": "➖",
      "message": "RSI is neutral. No strong momentum either direction."
    },
    {
      "condition": "rsi >= 30 && rsi < 40",
      "level": "Oversold",
      "color": "#10B981",
      "icon": "✅",
      "message": "RSI is oversold. Price may bounce. Look for bullish confirmation."
    },
    {
      "condition": "rsi < 30",
      "level": "Extremely Oversold",
      "color": "#059669",
      "icon": "🚀",
      "message": "RSI is extremely low. Strong upside potential. Consider buying opportunity."
    }
  ],
  "resources": {
    "documentation": "/docs/indicators/rsi",
    "tutorial": "/tutorials/using-rsi-effectively",
    "video": "https://youtube.com/watch?v=example",
    "api": "/api/docs/TechnicalIndicatorService#rsi"
  }
}
```

---

## Example: Turtle Trading Strategy Metadata

```json
{
  "id": "turtle_trading",
  "name": "Turtle Trading",
  "shortName": "Turtle",
  "category": "trend_following",
  "creator": "Richard Dennis & William Eckhardt",
  "year": 1983,
  "description": {
    "short": "Trend-following system with breakout entries and strict position sizing based on ATR",
    "full": "The Turtle Trading system was developed by Richard Dennis and William Eckhardt in 1983 to prove that trading could be taught. The system uses channel breakouts for entry, ATR-based position sizing, and pyramid adding to winners. It's designed to capture large trends while limiting risk per trade."
  },
  "philosophy": "Trend is your friend. Cut losses short, let profits run. The majority of trades will be small losses, but the few big wins will more than compensate.",
  "rules": {
    "entry": [
      {
        "system": 1,
        "description": "System 1 (Short-term): Enter long on 20-day high breakout, short on 20-day low breakout",
        "type": "breakout",
        "period": 20
      },
      {
        "system": 2,
        "description": "System 2 (Long-term): Enter long on 55-day high breakout, short on 55-day low breakout",
        "type": "breakout",
        "period": 55
      }
    ],
    "positionSizing": {
      "description": "Risk 1-2% per trade using ATR-based units",
      "formula": "Unit = (Portfolio × Risk%) / (N × Contract Value)",
      "n": "20-day ATR",
      "maxUnitsPerPosition": 4,
      "maxUnitsTotal": 12
    },
    "stopLoss": {
      "description": "Exit at 2N (2× ATR) loss from entry",
      "formula": "Stop = Entry - (2 × ATR)",
      "type": "fixed"
    },
    "exit": [
      {
        "system": 1,
        "long": "10-day low",
        "short": "10-day high"
      },
      {
        "system": 2,
        "long": "20-day low",
        "short": "20-day high"
      }
    ],
    "adding": {
      "description": "Add 1 unit on every 0.5N price move in favorable direction (max 4 units)",
      "spacing": "0.5 × ATR"
    }
  },
  "riskManagement": {
    "riskPerTrade": "1-2%",
    "maxPositionRisk": "8% (4 units × 2%)",
    "maxPortfolioRisk": "24% (12 units × 2%)",
    "stopLoss": "2 × ATR",
    "trailingStop": "10-day or 20-day low/high (depending on system)"
  },
  "performance": {
    "winRate": "35-40%",
    "avgWin": "+25%",
    "avgLoss": "-2%",
    "profitFactor": 2.5,
    "sharpeRatio": 0.85,
    "maxDrawdown": "32%",
    "bestSuits": "Trending markets, commodities, forex"
  },
  "pros": [
    "Simple, mechanical rules (no discretion)",
    "Captures large trends effectively",
    "Defined risk on every trade",
    "Scales well with portfolio size",
    "Works across multiple markets"
  ],
  "cons": [
    "High percentage of losing trades (60-65%)",
    "Requires discipline to follow rules",
    "Significant drawdowns (20-30%)",
    "Underperforms in ranging markets",
    "Needs adequate capital (minimum $50K-$100K)"
  ],
  "usage": {
    "bestFor": [
      "Trending markets (stocks, commodities, forex)",
      "Patient traders comfortable with losses",
      "Disciplined rule-followers",
      "Long-term time horizon (months to years)"
    ],
    "avoidWhen": [
      "Ranging, choppy markets",
      "Low capital (<$25K)",
      "Need for high win rate",
      "Impatient or emotional traders"
    ]
  },
  "examples": [
    {
      "scenario": "Strong Uptrend",
      "entrySignal": "Price breaks above 55-day high at $100",
      "atr": 3.0,
      "portfolioValue": 100000,
      "riskPercent": 1,
      "positionSize": "333 shares (Risk $1,000 / ($100 - $94))",
      "stopLoss": "$94 (Entry - 2×ATR)",
      "addPoints": [
        "Add 333 shares at $101.50 (0.5×ATR)",
        "Add 333 shares at $103.00 (1.0×ATR)",
        "Add 333 shares at $104.50 (1.5×ATR)"
      ],
      "exit": "20-day low or 2×ATR stop hit",
      "outcome": "Hypothetical: If price reaches $120, profit = $24,000 (24% return on 4 units)"
    }
  ],
  "resources": {
    "documentation": "/docs/strategies/turtle-trading",
    "tutorial": "/tutorials/implementing-turtle-trading",
    "book": "The Complete TurtleTrader by Michael Covel",
    "api": "/api/docs/TurtleStrategy"
  }
}
```

---

## Example: Candlestick Pattern Metadata

```json
{
  "id": "bullish_engulfing",
  "name": "Bullish Engulfing",
  "shortName": "Engulfing",
  "type": "bullish_reversal",
  "reliability": "high",
  "description": {
    "short": "Large green candle completely engulfs previous red candle at bottom of downtrend",
    "full": "The Bullish Engulfing pattern occurs when a large bullish (green) candle completely engulfs the prior bearish (red) candle. The pattern signals that buyers have overwhelmed sellers and a reversal may be starting."
  },
  "structure": {
    "candles": 2,
    "firstCandle": {
      "color": "red",
      "position": "Day 1 in downtrend"
    },
    "secondCandle": {
      "color": "green",
      "open": "Below Day 1 close",
      "close": "Above Day 1 open",
      "requirement": "Body must completely engulf Day 1 body (shadows don't matter)"
    }
  },
  "psychology": "Bears were in control on Day 1, pushing price down. On Day 2, bulls took complete control, overwhelming the prior day's selling and closing well above the prior open. This shift in sentiment suggests a potential trend reversal.",
  "confirmation": [
    "Volume on Day 2 significantly higher than average",
    "Next candle closes higher (confirms reversal)",
    "RSI showing bullish divergence",
    "Pattern occurs at support level or after extended downtrend"
  ],
  "tradingPlan": {
    "entry": {
      "conservative": "Enter above high of engulfing candle (confirms breakout)",
      "aggressive": "Enter at close of engulfing candle"
    },
    "target": {
      "method": "Height of preceding downtrend",
      "alternative": "Resistance level or Fibonacci retracement (50%, 61.8%)"
    },
    "stopLoss": {
      "method": "Below low of engulfing candle",
      "buffer": "Add 0.5-1% buffer for noise"
    },
    "riskReward": "Minimum 2:1 (target should be 2× stop distance)"
  },
  "reliability": {
    "overall": "HIGH",
    "factors": [
      {
        "condition": "Volume > 1.5× average",
        "impact": "+20% reliability"
      },
      {
        "condition": "After extended downtrend (>10 days)",
        "impact": "+15% reliability"
      },
      {
        "condition": "At support level",
        "impact": "+10% reliability"
      },
      {
        "condition": "With RSI divergence",
        "impact": "+15% reliability"
      }
    ]
  },
  "examples": [
    {
      "ticker": "AAPL",
      "date": "2025-12-03",
      "day1": {
        "open": 187.50,
        "close": 185.00,
        "color": "red"
      },
      "day2": {
        "open": 184.00,
        "close": 188.50,
        "color": "green",
        "volume": "2.3× average"
      },
      "signal": "Strong Bullish",
      "strength": 85,
      "entry": 188.50,
      "target": 195.00,
      "stopLoss": 182.50,
      "riskReward": "3.2:1"
    }
  ],
  "relatedPatterns": [
    "piercing_pattern",
    "morning_star",
    "hammer"
  ],
  "resources": {
    "documentation": "/docs/patterns/bullish-engulfing",
    "tutorial": "/tutorials/trading-engulfing-patterns",
    "api": "/api/docs/CandlestickPatternCalculator#bullishEngulfing"
  }
}
```

---

## API Endpoints

### Get Indicator Metadata
```
GET /api/docs/indicators/{id}
GET /api/docs/indicators (list all)
GET /api/docs/indicators/category/{category}
```

**Response Example**:
```json
{
  "success": true,
  "data": {
    "id": "rsi",
    "name": "Relative Strength Index",
    ...
  }
}
```

### Get Strategy Metadata
```
GET /api/docs/strategies/{id}
GET /api/docs/strategies (list all)
```

### Get Pattern Metadata
```
GET /api/docs/patterns/{id}
GET /api/docs/patterns/type/{type}
```

### Search Documentation
```
GET /api/docs/search?q={query}&type={indicator|strategy|pattern}
```

---

## Frontend Integration

### React Component Example

```typescript
// components/IndicatorTooltip.tsx
import { useQuery } from '@tanstack/react-query';
import { Tooltip } from '@/components/ui/tooltip';

interface Props {
  indicatorId: string;
  value: number;
  children: React.ReactNode;
}

export function IndicatorTooltip({ indicatorId, value, children }: Props) {
  const { data: metadata } = useQuery({
    queryKey: ['indicator-metadata', indicatorId],
    queryFn: () => fetch(`/api/docs/indicators/${indicatorId}`).then(r => r.json()),
    staleTime: 1000 * 60 * 60, // Cache for 1 hour
  });
  
  if (!metadata) return <>{children}</>;
  
  // Determine signal based on value and thresholds
  const threshold = metadata.thresholds.find(t => eval(t.condition.replace('rsi', value.toString())));
  
  return (
    <Tooltip>
      <TooltipTrigger asChild>{children}</TooltipTrigger>
      <TooltipContent className="max-w-sm">
        <div className="space-y-2">
          <div className="flex items-center gap-2">
            <span className="text-lg">{threshold?.icon}</span>
            <div>
              <h4 className="font-bold">{metadata.name}</h4>
              <p className="text-sm text-gray-500">{metadata.shortName}</p>
            </div>
          </div>
          
          <div className="flex items-center gap-2">
            <span className="text-2xl font-bold">{value}</span>
            <span
              className="px-2 py-1 rounded text-xs font-medium"
              style={{ backgroundColor: threshold?.color, color: 'white' }}
            >
              {threshold?.level}
            </span>
          </div>
          
          <p className="text-sm">{threshold?.message}</p>
          
          <div className="pt-2 border-t flex justify-between text-xs">
            <button className="text-blue-600 hover:underline">
              Learn More
            </button>
            <button className="text-gray-600 hover:underline">
              Configure
            </button>
          </div>
        </div>
      </TooltipContent>
    </Tooltip>
  );
}

// Usage:
<IndicatorTooltip indicatorId="rsi" value={68}>
  <div className="flex items-center gap-2">
    <span>RSI</span>
    <span className="font-bold">68</span>
    <HelpIcon className="h-4 w-4 text-gray-400" />
  </div>
</IndicatorTooltip>
```

---

## PHP Metadata Generator

```php
<?php
// scripts/generate-documentation-metadata.php

namespace Ksfraser\Finance\Documentation;

class MetadataGenerator
{
    private TechnicalIndicatorService $indicatorService;
    private CandlestickPatternCalculator $patternCalculator;
    
    public function generateAllMetadata(): void
    {
        $this->generateIndicatorMetadata();
        $this->generateStrategyMetadata();
        $this->generatePatternMetadata();
        $this->generateGlossary();
    }
    
    private function generateIndicatorMetadata(): void
    {
        $indicators = [
            'rsi' => $this->getRSIMetadata(),
            'macd' => $this->getMACDMetadata(),
            'bollinger_bands' => $this->getBollingerBandsMetadata(),
            // ... more indicators
        ];
        
        $outputPath = __DIR__ . '/../public/metadata/indicators.json';
        file_put_contents($outputPath, json_encode($indicators, JSON_PRETTY_PRINT));
    }
    
    private function getRSIMetadata(): array
    {
        return [
            'id' => 'rsi',
            'name' => 'Relative Strength Index',
            'shortName' => 'RSI',
            'category' => 'momentum',
            'description' => [
                'short' => 'Momentum oscillator measuring speed and magnitude of price changes (0-100 scale)',
                'full' => 'RSI is a momentum oscillator that measures the speed and magnitude of recent price changes to evaluate overbought or oversold conditions. It ranges from 0 to 100, with readings above 70 generally considered overbought and below 30 oversold.',
            ],
            // ... rest of metadata (see JSON example above)
        ];
    }
}

// Run generator
$generator = new MetadataGenerator();
$generator->generateAllMetadata();
echo "Metadata generated successfully!\n";
```

---

## Deployment

### Static JSON Files (Simple)
1. Generate JSON files using PHP script
2. Place in `public/metadata/` directory
3. Serve statically (cached by CDN)
4. Frontend fetches on demand

### API Endpoints (Dynamic)
1. Create API routes (`/api/docs/indicators/{id}`)
2. Query database or load from JSON
3. Cache responses (Redis, in-memory)
4. Return metadata dynamically

---

**Document Owner**: Development Team  
**Last Updated**: December 3, 2025  
**Status**: Ready for Implementation
