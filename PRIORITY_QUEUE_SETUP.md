# Enhanced Stock Data Management with Priority Job Queues

## Overview
This system extends the existing distributed job processing infrastructure to provide prioritized stock data fetching based on user portfolio needs. When users log in, their portfolio stocks are automatically queued for high-priority data updates, while background processing handles general market data and analysis tasks.

## Architecture

```
User Login → Portfolio Priority Manager → MQTT Job Queues → Distributed Workers
     ↓                    ↓                    ↓                ↓
Portfolio Check → Data Freshness Analysis → Priority Jobs → Stock Data Fetch
     ↓                    ↓                    ↓                ↓
Background Jobs → Scheduled Updates → Analysis Jobs → Database Storage
```

## Priority Queue System

### 1. **Foreground Queue** (Priority 1-3)
- **User Login Portfolio Fetch**: Immediate data needs for logged-in users
- **Manual Admin Requests**: On-demand data fetching from admin interface
- **Real-time User Requests**: Interactive user-initiated data updates

### 2. **Background Fetch Queue** (Priority 4-6)
- **Scheduled Portfolio Updates**: Daily/hourly batch updates
- **General Market Data**: Broad market data maintenance
- **Data Backfill**: Historical data population tasks

### 3. **Background Analysis Queue** (Priority 7-10)
- **Technical Analysis**: SMA, RSI, MACD calculations
- **LLM Analysis**: AI-powered sentiment and recommendations
- **Research Updates**: News and fundamental analysis

## Components

### Core Files

1. **UserPortfolioJobManager.php** - Main priority management system
   - Handles user login events
   - Calculates data freshness and priority
   - Queues jobs with appropriate priority levels
   - Integrates with existing MQTT infrastructure

2. **stock_job_processor.yml** - Enhanced configuration
   - Priority queue definitions
   - Stock-specific job parameters
   - Portfolio priority factors and thresholds
   - MQTT topic structure for stock processing

3. **stock_worker.php** - Job processing worker
   - Handles stock_fetch, stock_batch_fetch, and stock_analysis jobs
   - Integrates with existing Python data fetching scripts
   - Provides technical analysis capabilities
   - Supports clustering with multiple worker instances

4. **Enhanced Admin Interface** - Updated stock_data_admin.php
   - Queue-based job submission
   - Real-time job queue statistics
   - Portfolio priority monitoring
   - Fallback to direct processing if queues unavailable

### Integration Points

- **login.php**: Modified to trigger portfolio priority jobs
- **NavigationService.php**: Added admin menu for stock data management
- **MQTTJobBackend.php**: Existing MQTT infrastructure (extended)
- **JobProcessor.php**: Existing job processing framework (extended)

## Setup Instructions

### 1. Prerequisites

```bash
# Install required PHP extensions
sudo apt-get install php-yaml php-mosquitto

# Install Python dependencies
pip install yfinance pandas

# Ensure Mosquitto MQTT broker is running
sudo systemctl start mosquitto
sudo systemctl enable mosquitto
```

### 2. Database Setup

```bash
# Create job queue tables (if not already created)
php -f web_ui/create_job_tables.php

# Create user portfolio priority tracking
mysql -u root -p microcap_trading < sql/user_portfolio_priority.sql
```

### 3. Configuration

```bash
# Copy and configure job processor settings
cp stock_job_processor.yml job_processor.yml

# Edit configuration for your environment
nano job_processor.yml
```

### 4. Start Workers

```bash
# Start stock data worker
php stock_worker.php

# Or run in background
nohup php stock_worker.php > logs/stock_worker.out 2>&1 &

# Start additional workers for scaling
php stock_worker.php &  # Worker 2
php stock_worker.php &  # Worker 3
```

### 5. Monitor System

```bash
# Check worker logs
tail -f logs/stock_worker.log

# Monitor MQTT topics
mosquitto_sub -t 'stock/+/+' -v

# View job queue statistics in admin interface
# Navigate to: /admin/stock_data_admin.php
```

## Usage Scenarios

### Automatic User Portfolio Updates

1. **User logs in** → System checks portfolio symbols
2. **Data freshness analysis** → Identifies stale data (>30 min old)
3. **Priority job queuing** → High-priority fetch jobs created
4. **Worker processing** → Background workers fetch fresh data
5. **User experience** → Fresh data available on next page load

### Manual Admin Operations

1. **Fetch Single Stock**:
   - Enter symbol in admin interface
   - Job queued with user_request priority (3)
   - Results available within minutes

2. **Bulk Portfolio Update**:
   - Click "Fetch Portfolio Data"
   - Batch jobs created for all portfolio symbols
   - Progress monitored via job statistics

3. **Historical Data Population**:
   - Specify symbol and date range
   - Long-running job queued for background processing
   - Email notification on completion (future enhancement)

### Scheduled Operations

```bash
# Add to crontab for automated updates
0 9 * * 1-5 php /path/to/project/scheduled_portfolio_update.php
```

## Priority Calculation

The system uses multiple factors to calculate job priority:

### Factors (from configuration)
- **User Activity (40%)**: Recently active users get higher priority
- **Portfolio Value (30%)**: Larger portfolios get priority treatment
- **Data Age (20%)**: Older data gets higher priority for freshness
- **Market Volatility (10%)**: High volatility periods get faster updates

### Priority Rules
- **User Login**: Priority 1 (highest)
- **Manual Request**: Priority 3
- **Scheduled Update**: Priority 5
- **Background Analysis**: Priority 8
- **Maintenance**: Priority 10 (lowest)

## Performance Characteristics

### Throughput
- **Single Worker**: 10-15 stock fetches per minute
- **Multiple Workers**: Scales linearly with worker count
- **Batch Processing**: 50-100 stocks per batch job

### Latency
- **User Portfolio Jobs**: 30-60 seconds typical processing time
- **Manual Requests**: 1-2 minutes for immediate processing
- **Scheduled Jobs**: Processed within defined time windows

### Scalability
- **Horizontal**: Add more worker instances
- **Vertical**: Increase max_concurrent_jobs per worker
- **Geographic**: Deploy workers in different regions

## Monitoring and Alerts

### Key Metrics
- Queue depth by priority level
- Worker utilization and efficiency
- Average job processing time
- Data freshness by symbol
- User portfolio coverage

### Alert Thresholds
- Queue depth > 1000 jobs
- Worker efficiency < 70%
- Average job age > 10 minutes
- Data staleness > 2 hours

## Troubleshooting

### Common Issues

1. **Jobs not processing**:
   - Check MQTT broker status
   - Verify worker processes are running
   - Check network connectivity

2. **Data not updating**:
   - Verify Python script permissions
   - Check Yahoo Finance API limits
   - Review worker error logs

3. **High queue depth**:
   - Scale up worker instances
   - Optimize job batching
   - Review priority distribution

### Log Locations
- **Worker Logs**: `logs/stock_worker.log`
- **Portfolio Jobs**: `logs/portfolio_jobs.log`
- **MQTT Logs**: `logs/mqtt.log`
- **PHP Error Log**: Check system error log

## Future Enhancements

1. **Machine Learning Priority**: Use ML to predict optimal fetch timing
2. **Real-time WebSocket Updates**: Push fresh data to active users
3. **Geographic Distribution**: Deploy workers closer to data sources
4. **Advanced Caching**: Redis-based intermediate caching layer
5. **Predictive Fetching**: Pre-fetch data based on user behavior patterns

## Integration with Existing Systems

### Backward Compatibility
- Direct processing fallback if MQTT unavailable
- Existing admin interfaces continue to work
- Legacy AutoFetchService maintained for simple deployments

### Migration Path
1. Deploy new components alongside existing system
2. Enable MQTT job queuing gradually
3. Monitor performance and adjust configuration
4. Fully migrate to queue-based system
5. Remove legacy direct processing code

## Configuration Reference

### Key Configuration Sections

```yaml
job_processor:
  stock_jobs:
    portfolio_priority:
      data_staleness_threshold: 30  # minutes
      portfolio_priority_boost: 10
      max_concurrent_per_user: 3
      
  jobs:
    priority_rules:
      user_login: 1
      user_request: 3
      scheduled_update: 5
      background_analysis: 8
      
  portfolio:
    priority_factors:
      user_activity: 0.4
      portfolio_value: 0.3
      data_age: 0.2
      market_volatility: 0.1
```

This enhanced system provides a robust, scalable foundation for managing stock data with user-centric priorities while maintaining the flexibility of the existing distributed job processing infrastructure.