# WealthSystem Documentation Viewer

**Version**: 1.0  
**Purpose**: Web-based documentation portal with search, navigation, and interactive examples  
**Target Audience**: All WealthSystem users  
**Last Updated**: December 3, 2025  

---

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [User Interface](#user-interface)
4. [Search System](#search-system)
5. [Navigation Structure](#navigation-structure)
6. [Content Types](#content-types)
7. [Interactive Features](#interactive-features)
8. [Implementation Guide](#implementation-guide)

---

## Overview

### Purpose
Create a searchable, user-friendly web portal for all WealthSystem documentation, including:
- Technical indicator references
- Trading strategy guides
- Fund analysis tutorials
- API documentation
- User manuals
- Video tutorials

### Key Features
1. **Full-text search** with filtering and ranking
2. **Hierarchical navigation** by category
3. **Cross-reference links** between related topics
4. **Interactive examples** with live code
5. **Bookmark and history** tracking
6. **Mobile-responsive** design
7. **Offline access** (PWA capabilities)

### Technology Stack
- **Frontend**: Next.js 14 with App Router
- **Content**: MDX (Markdown + React components)
- **Search**: Algolia or local Fuse.js
- **Styling**: Tailwind CSS + shadcn/ui
- **Code Highlighting**: Prism.js or Shiki
- **Deployment**: Vercel or Netlify

---

## Architecture

### High-Level Design

```
┌─────────────────────────────────────────────────────┐
│                  Next.js App                         │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ┌────────────────┐        ┌──────────────────┐   │
│  │  Search Index  │◄──────►│  Search Engine   │   │
│  │  (Algolia/     │        │  (API Routes)    │   │
│  │   Fuse.js)     │        └──────────────────┘   │
│  └────────────────┘                 │              │
│         │                            │              │
│         ▼                            ▼              │
│  ┌────────────────────────────────────────────┐   │
│  │         Content Layer (MDX Files)          │   │
│  │  • indicators/*.mdx                        │   │
│  │  • strategies/*.mdx                        │   │
│  │  • tutorials/*.mdx                         │   │
│  │  • api/*.mdx                               │   │
│  └────────────────────────────────────────────┘   │
│         │                                           │
│         ▼                                           │
│  ┌────────────────────────────────────────────┐   │
│  │       Rendering Components                  │   │
│  │  • ArticleLayout                           │   │
│  │  • TableOfContents                         │   │
│  │  • CodeBlock (with syntax highlighting)    │   │
│  │  • InteractiveExample                      │   │
│  │  • RelatedArticles                         │   │
│  └────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

### Directory Structure

```
docs-viewer/
├── app/
│   ├── layout.tsx                 # Root layout
│   ├── page.tsx                   # Homepage
│   ├── search/
│   │   └── page.tsx               # Search results page
│   ├── [category]/
│   │   └── [slug]/
│   │       └── page.tsx           # Article page
│   └── api/
│       ├── search/
│       │   └── route.ts           # Search API
│       └── related/
│           └── route.ts           # Related articles API
├── components/
│   ├── navigation/
│   │   ├── Sidebar.tsx
│   │   ├── Breadcrumbs.tsx
│   │   └── TableOfContents.tsx
│   ├── search/
│   │   ├── SearchBar.tsx
│   │   ├── SearchResults.tsx
│   │   └── SearchFilters.tsx
│   ├── article/
│   │   ├── ArticleLayout.tsx
│   │   ├── ArticleHeader.tsx
│   │   ├── ArticleFooter.tsx
│   │   └── RelatedArticles.tsx
│   ├── code/
│   │   ├── CodeBlock.tsx
│   │   ├── InteractiveExample.tsx
│   │   └── CopyButton.tsx
│   └── ui/
│       ├── Button.tsx
│       ├── Input.tsx
│       └── Card.tsx
├── content/
│   ├── indicators/
│   │   ├── rsi.mdx
│   │   ├── macd.mdx
│   │   └── bollinger-bands.mdx
│   ├── strategies/
│   │   ├── turtle-trading.mdx
│   │   ├── buffett-value.mdx
│   │   └── quality-dividend.mdx
│   ├── patterns/
│   │   ├── hammer.mdx
│   │   ├── engulfing.mdx
│   │   └── doji.mdx
│   ├── tutorials/
│   │   ├── getting-started.mdx
│   │   ├── first-strategy.mdx
│   │   └── fund-analysis.mdx
│   └── api/
│       ├── technical-indicators.mdx
│       └── fund-composition.mdx
├── lib/
│   ├── search.ts                  # Search utilities
│   ├── navigation.ts              # Navigation helpers
│   └── mdx.ts                     # MDX processing
├── public/
│   ├── images/
│   └── videos/
└── styles/
    └── globals.css
```

---

## User Interface

### Homepage Layout

```
┌──────────────────────────────────────────────────────────────┐
│  [WealthSystem Logo]    Documentation         [Search 🔍]    │
├────────┬─────────────────────────────────────────────────────┤
│        │                                                      │
│ NAV    │  WealthSystem Documentation                         │
│        │  Your complete guide to trading, investing, and     │
│ Getting│  portfolio management.                               │
│ Started│                                                      │
│        │  ┌──────────────────┐  ┌──────────────────┐        │
│ Indic. │  │ 📊 Indicators     │  │ 📈 Strategies     │        │
│        │  │ Learn about RSI,  │  │ Turtle Trading,   │        │
│ Strats │  │ MACD, Bollinger   │  │ Buffett Value,    │        │
│        │  │ Bands & more      │  │ Quality Dividend  │        │
│ Patterns│  │                   │  │                   │        │
│        │  │ [Explore →]       │  │ [Explore →]       │        │
│ Funds  │  └──────────────────┘  └──────────────────┘        │
│        │                                                      │
│ API    │  ┌──────────────────┐  ┌──────────────────┐        │
│        │  │ 🕯️ Patterns       │  │ 💰 Fund Analysis │        │
│ Tutors │  │ Candlestick       │  │ Composition,      │        │
│        │  │ pattern           │  │ overlap, MER      │        │
│        │  │ recognition       │  │ comparison        │        │
│        │  │                   │  │                   │        │
│        │  │ [Explore →]       │  │ [Explore →]       │        │
│        │  └──────────────────┘  └──────────────────┘        │
│        │                                                      │
│        │  Quick Links:                                        │
│        │  • Getting Started Guide                            │
│        │  • Your First Trading Strategy                      │
│        │  • Understanding Risk Management                    │
│        │  • Fund Analysis Tutorial                           │
│        │                                                      │
└────────┴─────────────────────────────────────────────────────┘
```

### Article Page Layout

```
┌──────────────────────────────────────────────────────────────┐
│  [Logo] Documentation / Indicators / RSI       [Search 🔍]   │
├────────┬────────────────────────────────────┬────────────────┤
│        │                                     │ ON THIS PAGE   │
│ NAV    │  RSI (Relative Strength Index)     │ • Overview     │
│        │  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━       │ • Calculation  │
│ Getting│                                     │ • Interpret.   │
│ Started│  📊 Momentum Indicator              │ • Examples     │
│        │  ⏱️ 5 min read                      │ • Best Pract.  │
│ Indic. │  📅 Updated: Dec 3, 2025            │ • Related      │
│ ├─ RSI │                                     │                │
│ ├─ MACD│  Overview                           │ RELATED        │
│ ├─ BB  │  ─────────                          │ • Stochastic   │
│ └─ ... │  RSI is a momentum oscillator       │ • MFI          │
│        │  that measures the speed and        │ • CCI          │
│ Strats │  magnitude of recent price          │ • Divergence   │
│        │  changes to evaluate overbought     │                │
│ Patterns│  or oversold conditions.            │ ACTIONS        │
│        │                                     │ [🔖 Bookmark] │
│ Funds  │  Range: 0 to 100                    │ [🖨️ Print]    │
│        │  Overbought: > 70                   │ [📱 Share]    │
│ API    │  Oversold: < 30                     │ [✏️ Edit]     │
│        │                                     │                │
│ Tutors │  Calculation Formula                │                │
│        │  ──────────────────                 │                │
│        │  ```                                │                │
│        │  RS = Avg Gain / Avg Loss           │                │
│        │  RSI = 100 - (100 / (1 + RS))       │                │
│        │  ```                                │                │
│        │                                     │                │
│        │  [▶ Try Interactive Example]        │                │
│        │                                     │                │
│        │  Interpretation Guide               │                │
│        │  ──────────────────                 │                │
│        │  • RSI > 70: Overbought...          │                │
│        │  • RSI < 30: Oversold...            │                │
│        │                                     │                │
│        │  [Continue reading...]              │                │
│        │                                     │                │
│        │  ← Previous: MACD  |  Next: Stoch →│                │
└────────┴────────────────────────────────────┴────────────────┘
```

### Search Results Page

```
┌──────────────────────────────────────────────────────────────┐
│  [Logo] Documentation                    [Search: "rsi" 🔍]  │
├────────┬─────────────────────────────────────────────────────┤
│        │                                                      │
│ FILTER │  Search Results for "rsi" (23 results)              │
│        │                                                      │
│ Type   │  ┌──────────────────────────────────────────────┐  │
│ ☑ All  │  │ 📊 RSI (Relative Strength Index)             │  │
│ ☐ Indic│  │ indicators/rsi                                │  │
│ ☐ Strat│  │ RSI is a momentum oscillator that measures    │  │
│ ☐ Patt │  │ the speed and magnitude of recent price...    │  │
│ ☐ Funds│  │                                               │  │
│        │  │ Relevance: ████████░░ 85%                     │  │
│ Level  │  │ [View Article →]                              │  │
│ ☐ Begin│  └──────────────────────────────────────────────┘  │
│ ☑ Inter│                                                      │
│ ☐ Advan│  ┌──────────────────────────────────────────────┐  │
│        │  │ 📈 Using RSI with MACD                        │  │
│ Updated│  │ tutorials/combining-indicators                │  │
│ ☐ <7d  │  │ Learn how to combine RSI and MACD for         │  │
│ ☐ <30d │  │ stronger trading signals. This tutorial...    │  │
│ ☑ All  │  │                                               │  │
│        │  │ Relevance: ███████░░░ 72%                     │  │
│        │  │ [View Tutorial →]                             │  │
│        │  └──────────────────────────────────────────────┘  │
│        │                                                      │
│        │  ┌──────────────────────────────────────────────┐  │
│        │  │ 🔌 TechnicalIndicatorService::rsi()          │  │
│        │  │ api/technical-indicators                      │  │
│        │  │ Calculate RSI values for given price data.    │  │
│        │  │ Parameters: period (default: 14), data...     │  │
│        │  │                                               │  │
│        │  │ Relevance: ██████░░░░ 65%                     │  │
│        │  │ [View API Docs →]                             │  │
│        │  └──────────────────────────────────────────────┘  │
│        │                                                      │
│        │  [Load More Results...]                             │
│        │                                                      │
└────────┴─────────────────────────────────────────────────────┘
```

---

## Search System

### Search Features

#### 1. Full-Text Search
- Search across all content (titles, body text, code examples)
- Fuzzy matching for typos
- Stemming (e.g., "trading" matches "trade", "trades")
- Phrase search with quotes ("bollinger bands")

#### 2. Filters
- **Content Type**: Indicators, Strategies, Patterns, Funds, API, Tutorials
- **Difficulty Level**: Beginner, Intermediate, Advanced
- **Last Updated**: Last 7 days, Last 30 days, Last 90 days, All time
- **Category**: Technical Analysis, Fundamental, Risk Management, etc.

#### 3. Ranking Algorithm
```javascript
// Pseudo-code for ranking
function calculateRelevance(article, query) {
  let score = 0;
  
  // Exact title match: +50 points
  if (article.title.toLowerCase() === query.toLowerCase()) {
    score += 50;
  }
  
  // Title contains query: +30 points
  else if (article.title.toLowerCase().includes(query.toLowerCase())) {
    score += 30;
  }
  
  // Body text matches: +10 per match (max 40)
  const bodyMatches = countMatches(article.body, query);
  score += Math.min(bodyMatches * 10, 40);
  
  // Recent update: +10 points if < 30 days
  const daysSinceUpdate = (Date.now() - article.lastUpdated) / (1000 * 60 * 60 * 24);
  if (daysSinceUpdate < 30) {
    score += 10;
  }
  
  // Popularity (view count): +20 if top 10%
  if (article.viewCount > percentile90) {
    score += 20;
  }
  
  return score;
}
```

#### 4. Search Suggestions
- Autocomplete as user types
- "Did you mean...?" for misspellings
- Recent searches (local storage)
- Popular searches

### Implementation: Algolia vs Fuse.js

#### Option A: Algolia (Recommended for Production)
**Pros**:
- Blazing fast (<10ms response)
- Typo tolerance built-in
- Faceted search (filters)
- Analytics dashboard
- CDN-hosted (no backend needed)

**Cons**:
- Monthly cost (free tier: 10K searches/mo)
- Requires separate indexing pipeline

**Setup**:
```bash
npm install algoliasearch instantsearch.js react-instantsearch
```

```typescript
// lib/algolia.ts
import algoliasearch from 'algoliasearch';

const client = algoliasearch(
  process.env.NEXT_PUBLIC_ALGOLIA_APP_ID!,
  process.env.NEXT_PUBLIC_ALGOLIA_SEARCH_KEY!
);

export const searchIndex = client.initIndex('documentation');

// Search function
export async function search(query: string, filters?: string) {
  const results = await searchIndex.search(query, {
    filters,
    hitsPerPage: 20,
    attributesToHighlight: ['title', 'excerpt'],
    typoTolerance: true,
  });
  
  return results.hits;
}
```

#### Option B: Fuse.js (Free, Local Search)
**Pros**:
- Completely free
- No external dependencies
- Works offline
- Privacy-friendly (no data sent to 3rd party)

**Cons**:
- Slower for large datasets (>1000 articles)
- Less sophisticated ranking
- No analytics

**Setup**:
```bash
npm install fuse.js
```

```typescript
// lib/search.ts
import Fuse from 'fuse.js';
import { allArticles } from '@/content';

const fuse = new Fuse(allArticles, {
  keys: [
    { name: 'title', weight: 2 },
    { name: 'excerpt', weight: 1.5 },
    { name: 'content', weight: 1 },
    { name: 'tags', weight: 1.2 },
  ],
  threshold: 0.3,
  includeScore: true,
  minMatchCharLength: 2,
});

export function search(query: string) {
  return fuse.search(query).map(result => ({
    ...result.item,
    relevance: (1 - (result.score || 0)) * 100, // Convert to percentage
  }));
}
```

---

## Navigation Structure

### Categories & Subcategories

```
📖 Getting Started
├── Welcome to WealthSystem
├── Quick Start Guide
├── Your First Strategy
└── Key Concepts

📊 Technical Indicators
├── Momentum
│   ├── RSI
│   ├── MACD
│   ├── Stochastic
│   ├── CCI
│   ├── MFI
│   └── Williams %R
├── Trend
│   ├── Moving Averages (SMA, EMA, WMA, DEMA, TEMA)
│   ├── ADX
│   ├── Aroon
│   └── Parabolic SAR
├── Volatility
│   ├── Bollinger Bands
│   ├── ATR
│   └── Standard Deviation
└── Volume
    ├── OBV
    └── Chaikin A/D

📈 Trading Strategies
├── Turtle Trading
├── Warren Buffett Value
├── Quality Dividend Growth
├── Momentum
└── Mean Reversion

🕯️ Candlestick Patterns
├── Bullish Reversal
│   ├── Hammer
│   ├── Inverted Hammer
│   ├── Bullish Engulfing
│   ├── Morning Star
│   └── Piercing Pattern
├── Bearish Reversal
│   ├── Shooting Star
│   ├── Hanging Man
│   ├── Evening Star
│   └── Dark Cloud Cover
└── Indecision
    ├── Doji
    ├── Spinning Top
    └── Harami

💰 Fund Analysis
├── Fund Composition
├── Overlap Analysis
├── MER Comparison
├── Eligibility Tiers
└── Segregated Funds

🔌 API Reference
├── TechnicalIndicatorService
├── CandlestickPatternCalculator
├── FundCompositionService
├── SectorAnalysisService
└── IndexBenchmarkingService

🎓 Tutorials
├── Beginner
│   ├── Understanding Technical Analysis
│   ├── Reading Candlestick Charts
│   └── Risk Management Basics
├── Intermediate
│   ├── Combining Indicators
│   ├── Building Your First Strategy
│   └── Fund Portfolio Construction
└── Advanced
    ├── Custom Indicator Development
    ├── Backtesting Strategies
    └── Optimizing Position Sizing

📚 Glossary
└── A-Z Terms & Definitions
```

### Sidebar Navigation Component

```typescript
// components/navigation/Sidebar.tsx
import Link from 'next/link';
import { usePathname } from 'next/navigation';

interface NavItem {
  label: string;
  href?: string;
  icon?: string;
  children?: NavItem[];
}

const navItems: NavItem[] = [
  {
    label: 'Getting Started',
    icon: '📖',
    children: [
      { label: 'Welcome', href: '/docs/welcome' },
      { label: 'Quick Start', href: '/docs/quick-start' },
    ],
  },
  {
    label: 'Technical Indicators',
    icon: '📊',
    children: [
      {
        label: 'Momentum',
        children: [
          { label: 'RSI', href: '/docs/indicators/rsi' },
          { label: 'MACD', href: '/docs/indicators/macd' },
        ],
      },
    ],
  },
];

export function Sidebar() {
  const pathname = usePathname();
  
  return (
    <aside className="w-64 border-r bg-slate-50 p-4">
      <nav>
        {navItems.map((item) => (
          <NavSection key={item.label} item={item} pathname={pathname} />
        ))}
      </nav>
    </aside>
  );
}

function NavSection({ item, pathname }: { item: NavItem; pathname: string }) {
  const [isOpen, setIsOpen] = useState(true);
  
  return (
    <div className="mb-2">
      {item.children ? (
        <>
          <button
            onClick={() => setIsOpen(!isOpen)}
            className="flex items-center gap-2 font-medium"
          >
            <span>{item.icon}</span>
            <span>{item.label}</span>
            <span className={isOpen ? 'rotate-90' : ''}>▶</span>
          </button>
          {isOpen && (
            <ul className="ml-6 mt-1">
              {item.children.map((child) => (
                <li key={child.label}>
                  <NavSection item={child} pathname={pathname} />
                </li>
              ))}
            </ul>
          )}
        </>
      ) : (
        <Link
          href={item.href!}
          className={`block py-1 hover:text-blue-600 ${
            pathname === item.href ? 'text-blue-600 font-medium' : ''
          }`}
        >
          {item.label}
        </Link>
      )}
    </div>
  );
}
```

---

## Content Types

### MDX Article Structure

```mdx
---
title: "RSI (Relative Strength Index)"
category: "indicators"
subcategory: "momentum"
difficulty: "beginner"
readTime: 5
lastUpdated: "2025-12-03"
tags: ["rsi", "momentum", "oscillator", "overbought", "oversold"]
relatedArticles: ["macd", "stochastic", "mfi"]
---

# RSI (Relative Strength Index)

<ArticleMeta
  icon="📊"
  category="Momentum Indicator"
  readTime={5}
  lastUpdated="Dec 3, 2025"
/>

## Overview

RSI is a momentum oscillator that measures the speed and magnitude of recent price changes to evaluate overbought or oversold conditions.

<InfoBox type="key-concept">
  **Key Concept**: RSI ranges from 0 to 100. Values above 70 indicate overbought conditions, while values below 30 indicate oversold conditions.
</InfoBox>

## Calculation Formula

The RSI formula is:

$$
RS = \frac{\text{Average Gain}}{\text{Average Loss}} \text{ (over N periods)}
$$

$$
RSI = 100 - \frac{100}{1 + RS}
$$

Default period: 14 days

<InteractiveExample
  component="RSICalculator"
  initialData={samplePriceData}
/>

## Interpretation Guide

<Tabs>
  <TabItem label="Overbought/Oversold">
    - **RSI > 70**: Overbought (potential sell signal)
    - **RSI < 30**: Oversold (potential buy signal)
    - **RSI ≈ 50**: Neutral momentum
  </TabItem>
  
  <TabItem label="Divergence">
    **Bullish Divergence**: Price makes new low, RSI doesn't → Reversal up
    
    **Bearish Divergence**: Price makes new high, RSI doesn't → Reversal down
  </TabItem>
  
  <TabItem label="Failure Swings">
    More advanced pattern indicating strong reversals...
  </TabItem>
</Tabs>

## Best Practices

<CheckList>
  - [ ] Don't rely on RSI alone—combine with other indicators
  - [ ] In strong trends, RSI can remain overbought/oversold
  - [ ] Look for divergences (price vs RSI)
  - [ ] Adjust thresholds in trending markets (80/20 instead of 70/30)
</CheckList>

## Code Example

```php
use Ksfraser\Finance\Services\TechnicalIndicatorService;

$service = new TechnicalIndicatorService();

// Calculate RSI
$rsi = $service->rsi($priceData, 14);

// Interpret signal
if ($rsi[count($rsi) - 1] > 70) {
    echo "Overbought - Consider selling";
} elseif ($rsi[count($rsi) - 1] < 30) {
    echo "Oversold - Consider buying";
}
```

## Related Topics

<RelatedArticles
  articles={[
    { title: "MACD", href: "/docs/indicators/macd" },
    { title: "Stochastic Oscillator", href: "/docs/indicators/stochastic" },
    { title: "Money Flow Index (MFI)", href: "/docs/indicators/mfi" },
  ]}
/>

<ArticleFooter
  previousArticle={{ title: "MACD", href: "/docs/indicators/macd" }}
  nextArticle={{ title: "Stochastic", href: "/docs/indicators/stochastic" }}
  githubEditUrl="https://github.com/ksfraser/WealthSystem/edit/main/docs-viewer/content/indicators/rsi.mdx"
/>
```

### Custom MDX Components

```typescript
// components/mdx/InfoBox.tsx
export function InfoBox({ type, children }: { type: 'tip' | 'warning' | 'key-concept'; children: React.ReactNode }) {
  const styles = {
    tip: 'bg-green-50 border-green-200',
    warning: 'bg-yellow-50 border-yellow-200',
    'key-concept': 'bg-blue-50 border-blue-200',
  };
  
  return (
    <div className={`p-4 border-l-4 ${styles[type]} my-4`}>
      {children}
    </div>
  );
}

// components/mdx/InteractiveExample.tsx
export function InteractiveExample({ component, initialData }: { component: string; initialData: any }) {
  const Component = lazy(() => import(`../examples/${component}`));
  
  return (
    <div className="my-6 p-4 border rounded-lg bg-white">
      <h4 className="font-bold mb-2">📊 Interactive Example</h4>
      <Suspense fallback={<Spinner />}>
        <Component initialData={initialData} />
      </Suspense>
    </div>
  );
}

// components/mdx/CheckList.tsx
export function CheckList({ children }: { children: React.ReactNode }) {
  return (
    <ul className="space-y-2 my-4">
      {children}
    </ul>
  );
}
```

---

## Interactive Features

### 1. Interactive Code Editor

```typescript
// components/examples/InteractiveCodeEditor.tsx
import { useState } from 'react';
import Editor from '@monaco-editor/react';

export function InteractiveCodeEditor({ initialCode, language = 'php' }: Props) {
  const [code, setCode] = useState(initialCode);
  const [output, setOutput] = useState('');
  const [loading, setLoading] = useState(false);
  
  const runCode = async () => {
    setLoading(true);
    try {
      const response = await fetch('/api/execute', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ code, language }),
      });
      const result = await response.json();
      setOutput(result.output);
    } catch (error) {
      setOutput(`Error: ${error.message}`);
    }
    setLoading(false);
  };
  
  return (
    <div className="border rounded-lg overflow-hidden">
      <Editor
        height="300px"
        language={language}
        value={code}
        onChange={(value) => setCode(value || '')}
        theme="vs-dark"
      />
      <div className="p-2 bg-gray-100 flex justify-between">
        <button
          onClick={runCode}
          disabled={loading}
          className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          {loading ? '⏳ Running...' : '▶ Run Code'}
        </button>
      </div>
      {output && (
        <div className="p-4 bg-black text-green-400 font-mono text-sm">
          <pre>{output}</pre>
        </div>
      )}
    </div>
  );
}
```

### 2. Chart Visualizations

```typescript
// components/examples/RSIChart.tsx
import { Line } from 'react-chartjs-2';

export function RSIChart({ priceData, rsiData }: Props) {
  const chartData = {
    labels: priceData.map(d => d.date),
    datasets: [
      {
        label: 'Price',
        data: priceData.map(d => d.close),
        borderColor: 'rgb(59, 130, 246)',
        yAxisID: 'y',
      },
      {
        label: 'RSI',
        data: rsiData,
        borderColor: 'rgb(234, 88, 12)',
        yAxisID: 'y1',
      },
    ],
  };
  
  const options = {
    scales: {
      y: { position: 'left' },
      y1: {
        position: 'right',
        min: 0,
        max: 100,
        grid: { drawOnChartArea: false },
      },
    },
    plugins: {
      annotation: {
        annotations: {
          overbought: {
            type: 'line',
            yMin: 70,
            yMax: 70,
            borderColor: 'red',
            borderDash: [5, 5],
            label: { content: 'Overbought' },
          },
          oversold: {
            type: 'line',
            yMin: 30,
            yMax: 30,
            borderColor: 'green',
            borderDash: [5, 5],
            label: { content: 'Oversold' },
          },
        },
      },
    },
  };
  
  return <Line data={chartData} options={options} />;
}
```

### 3. Interactive Calculators

```typescript
// components/examples/PositionSizeCalculator.tsx
export function PositionSizeCalculator() {
  const [portfolioValue, setPortfolioValue] = useState(100000);
  const [riskPercent, setRiskPercent] = useState(1);
  const [entryPrice, setEntryPrice] = useState(50);
  const [stopLoss, setStopLoss] = useState(48);
  
  const riskAmount = (portfolioValue * riskPercent) / 100;
  const riskPerShare = entryPrice - stopLoss;
  const shares = Math.floor(riskAmount / riskPerShare);
  const positionValue = shares * entryPrice;
  
  return (
    <div className="p-6 bg-white border rounded-lg">
      <h3 className="text-lg font-bold mb-4">Position Size Calculator</h3>
      
      <div className="space-y-4">
        <div>
          <label>Portfolio Value: ${portfolioValue.toLocaleString()}</label>
          <input
            type="range"
            min="10000"
            max="1000000"
            step="10000"
            value={portfolioValue}
            onChange={(e) => setPortfolioValue(Number(e.target.value))}
            className="w-full"
          />
        </div>
        
        <div>
          <label>Risk per Trade: {riskPercent}%</label>
          <input
            type="range"
            min="0.5"
            max="5"
            step="0.5"
            value={riskPercent}
            onChange={(e) => setRiskPercent(Number(e.target.value))}
            className="w-full"
          />
        </div>
        
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label>Entry Price</label>
            <input
              type="number"
              value={entryPrice}
              onChange={(e) => setEntryPrice(Number(e.target.value))}
              className="w-full p-2 border rounded"
            />
          </div>
          <div>
            <label>Stop Loss</label>
            <input
              type="number"
              value={stopLoss}
              onChange={(e) => setStopLoss(Number(e.target.value))}
              className="w-full p-2 border rounded"
            />
          </div>
        </div>
      </div>
      
      <div className="mt-6 p-4 bg-blue-50 rounded">
        <h4 className="font-bold">Results:</h4>
        <ul className="mt-2 space-y-1">
          <li>Risk Amount: <strong>${riskAmount.toFixed(2)}</strong></li>
          <li>Risk per Share: <strong>${riskPerShare.toFixed(2)}</strong></li>
          <li>Position Size: <strong>{shares} shares</strong></li>
          <li>Position Value: <strong>${positionValue.toLocaleString()}</strong></li>
        </ul>
      </div>
    </div>
  );
}
```

---

## Implementation Guide

### Step 1: Initialize Next.js Project

```bash
npx create-next-app@latest docs-viewer --typescript --tailwind --app
cd docs-viewer
npm install @next/mdx mdx-components gray-matter
```

### Step 2: Configure MDX

```typescript
// next.config.mjs
import createMDX from '@next/mdx';

const nextConfig = {
  pageExtensions: ['js', 'jsx', 'md', 'mdx', 'ts', 'tsx'],
};

const withMDX = createMDX({
  options: {
    remarkPlugins: [],
    rehypePlugins: [],
  },
});

export default withMDX(nextConfig);
```

### Step 3: Create Content Loader

```typescript
// lib/mdx.ts
import fs from 'fs';
import path from 'path';
import matter from 'gray-matter';

const contentDirectory = path.join(process.cwd(), 'content');

export interface Article {
  slug: string;
  title: string;
  category: string;
  content: string;
  frontMatter: Record<string, any>;
}

export async function getArticle(category: string, slug: string): Promise<Article> {
  const filePath = path.join(contentDirectory, category, `${slug}.mdx`);
  const fileContent = fs.readFileSync(filePath, 'utf8');
  const { data, content } = matter(fileContent);
  
  return {
    slug,
    title: data.title,
    category,
    content,
    frontMatter: data,
  };
}

export async function getAllArticles(): Promise<Article[]> {
  const categories = fs.readdirSync(contentDirectory);
  const articles: Article[] = [];
  
  for (const category of categories) {
    const categoryPath = path.join(contentDirectory, category);
    const files = fs.readdirSync(categoryPath);
    
    for (const file of files) {
      if (file.endsWith('.mdx')) {
        const slug = file.replace('.mdx', '');
        const article = await getArticle(category, slug);
        articles.push(article);
      }
    }
  }
  
  return articles;
}
```

### Step 4: Deploy

```bash
# Install Vercel CLI
npm i -g vercel

# Deploy
vercel --prod
```

---

**Next Steps**: Start with MVP (20 articles across all categories), then expand based on user feedback.

**Document Owner**: Documentation Team  
**Last Updated**: December 3, 2025
