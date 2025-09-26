# ChatGPT Micro-Cap Experiment 🤖📈
**A Comprehensive Financial Analysis & Portfolio Management Platform**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Python 3.8+](https://img.shields.io/badge/python-3.8+-blue.svg)](https://www.python.org/downloads/)
[![PHP 8.0+](https://img.shields.io/badge/php-8.0+-777BB4.svg)](https://www.php.net/)
[![Architecture](https://img.shields.io/badge/Architecture-Multi--Layer-green.svg)](#architecture-overview)
[![Documentation](https://img.shields.io/badge/Documentation-Complete-brightgreen.svg)](#documentation)

Welcome to the repository behind my 6-month live trading experiment where ChatGPT manages a real-money micro-cap portfolio. This project has evolved from a simple experiment into a comprehensive financial analysis platform with professional-grade capabilities.

## 🚀 **Quick Start Guide**
- **🏁 Getting Started:** [Start Your Own Experiment](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Start%20Your%20Own/README.md)
- **📊 Live Data:** View real portfolio updates in `Scripts and CSV Files/`
- **🔧 Installation:** See [Installation Guide](#installation) below
- **📖 Documentation:** Browse comprehensive docs in `docs/` directory

## 📁 **Repository Structure**

### **Core Components**
- **`trading_script.py`** - Advanced trading engine with multi-source data fetching
- **`web_ui/`** - Professional PHP web interface with authentication & portfolio management
- **`src/Services/Calculators/`** - TA-Lib integration with 150+ technical indicators
- **`worker.php`** - Background job processing system
- **`Scripts and CSV Files/`** - Live portfolio data (updated daily)

### **Documentation & Research**
- **`docs/`** - Comprehensive technical documentation with UML diagrams
- **`Weekly Deep Research (MD|PDF)/`** - Research summaries and performance analysis
- **`Experiment Details/`** - Methodology, prompts, and experimental design
- **`tests/`** - Organized unit and integration testing suite

### **Supporting Files**
- **`Start Your Own/`** - Complete template for replicating the experiment
- **`archive/`** - Historical versions and backup files
- **`vendor/`** - PHP dependencies (Composer packages)

# The Concept
Every day, I kept seeing the same ad about having some A.I. pick undervalued stocks. It was obvious it was trying to get me to subscribe to some garbage, so I just rolled my eyes.  
Then I started wondering, "How well would that actually work?"

So, starting with just $100, I wanted to answer a simple but powerful question:

**Can powerful large language models like ChatGPT actually generate alpha (or at least make smart trading decisions) using real-time data?**

## Each trading day:

- I provide it trading data on the stocks in its portfolio.  
- Strict stop-loss rules apply.  
- Every week I allow it to use deep research to reevaluate its account.  
- I track and publish performance data weekly on my blog: [Here](https://nathanbsmith729.substack.com)

## Research & Documentation

- [Research Index](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Experiment%20Details/Deep%20Research%20Index.md)  
- [Disclaimer](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Experiment%20Details/Disclaimer.md)  
- [Q&A](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Experiment%20Details/Q%26A.md)  
- [Prompts](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Experiment%20Details/Prompts.md)  
- [Starting Your Own](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Start%20Your%20Own/README.md)  
- [Research Summaries (MD)](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/tree/main/Weekly%20Deep%20Research%20(MD))  
- [Full Deep Research Reports (PDF)](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/tree/main/Weekly%20Deep%20Research%20(PDF))
- [Chats](https://github.com/LuckyOne7777/ChatGPT-Micro-Cap-Experiment/blob/main/Experiment%20Details/Chats.md)
# Current Performance

<!-- To update performance chart: 
     1. Replace the image file with updated results
     2. Update the dates and description below
     3. Update the "Last Updated" date -->

**Last Updated:** August 29th, 2025

![Latest Performance Results](Results.png)

**Current Status:** Portfolio is outperforming the S&P 500 benchmark

*Performance data is updated after each trading day. See the CSV files in `Scripts and CSV Files/` for detailed daily tracking.*

# Features of This Repo
- Live trading scripts — used to evaluate prices and update holdings daily  
- LLM-powered decision engine — ChatGPT picks the trades  
- Performance tracking — CSVs with daily PnL, total equity, and trade history  
- Visualization tools — Matplotlib graphs comparing ChatGPT vs. Index  
- Logs & trade data — auto-saved logs for transparency  

# Why This Matters
AI is being hyped across every industry, but can it really manage money without guidance?

This project is an attempt to find out — with transparency, data, and a real budget.

# 🏗️ **Architecture Overview**

## **System Architecture**
The platform implements a **multi-layered architecture** with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────┐
│                 Presentation Layer                       │
│  [Web Interface] [Navigation] [Authentication UI]       │
└─────────────────────┬───────────────────────────────────┘
                      │
┌─────────────────────┴───────────────────────────────────┐
│                Business Logic Layer                      │
│  [Portfolio Management] [Trade Logic] [User Management] │
└─────────────────────┬───────────────────────────────────┘
                      │
┌─────────────────────┴───────────────────────────────────┐
│                 Data Access Layer                       │
│  [DAO Pattern] [Session Manager] [CSV Handler]          │
└─────────────────────┬───────────────────────────────────┘
                      │
┌─────────────────────┴───────────────────────────────────┐
│                  Storage Layer                          │
│  [MySQL Database] [CSV Files] [Session Storage]        │
└─────────────────────────────────────────────────────────┘
```

**Key Architectural Features:**
- **🔄 Dual-Storage Strategy:** Database + CSV for maximum reliability
- **🏭 DAO Pattern:** Standardized data access with inheritance
- **🔐 Centralized Authentication:** Session-based security with CSRF protection
- **⚡ Background Processing:** Asynchronous job system for technical analysis
- **📊 Professional Analytics:** TA-Lib integration with 150+ indicators

## **Tech Stack & Features**

### **Backend Technologies**
- **🐍 Python 3.8+** - Core trading engine and automation
- **🐘 PHP 8.0+** - Web application framework
- **🗄️ MySQL** - Primary database with connection pooling
- **📊 TA-Lib** - Professional-grade technical analysis library
- **🔧 Composer** - PHP dependency management

### **Frontend Technologies**  
- **🎨 HTML5/CSS3** - Responsive web interface
- **⚡ JavaScript** - Interactive components and AJAX
- **📱 Bootstrap** - Mobile-responsive design framework
- **📈 Chart.js** - Financial data visualization

### **Data & Analytics**
- **📈 yFinance + pandas** - Multi-source market data fetching
- **📊 Matplotlib** - Advanced performance visualization  
- **🔍 Technical Analysis** - 150+ indicators via TA-Lib integration
- **📉 Risk Analytics** - CAPM, Sharpe/Sortino ratios, drawdown analysis

### **Infrastructure & DevOps**
- **🔄 Background Jobs** - Asynchronous processing system
- **🧪 Testing Framework** - Comprehensive unit and integration tests
- **📝 Documentation** - Auto-generated API docs with UML diagrams
- **🐳 Containerization** - Docker support for deployment
- **🔒 Security** - CSRF protection, input validation, SQL injection prevention

## **🌟 Key Features**

### **Trading & Portfolio Management**
- ✅ **Multi-Source Data Fetching** - Yahoo Finance primary, Stooq fallback
- ✅ **Advanced Order Types** - Market-on-Open (MOO), Limit Orders, Stop-Loss
- ✅ **Risk Management** - Automated position sizing and stop-loss execution  
- ✅ **Portfolio Analytics** - Real-time P&L, performance metrics, risk analysis
- ✅ **Backtesting Framework** - Historical analysis with ASOF_DATE override

### **Technical Analysis Engine**
- ✅ **150+ Technical Indicators** - RSI, MACD, Moving Averages, Volume Analysis
- ✅ **61 Candlestick Patterns** - Professional pattern recognition
- ✅ **Advanced Analytics** - Hilbert Transform, Statistical Indicators, Cycle Analysis
- ✅ **Composite Signals** - Multi-indicator analysis for enhanced accuracy
- ✅ **Real-time Processing** - Background job system for continuous analysis

### **Web Interface & User Management**
- ✅ **Multi-User Support** - Role-based access control (Admin/User)
- ✅ **Secure Authentication** - Password hashing, CSRF protection, session management
- ✅ **Responsive Design** - Mobile-friendly interface with modern UI
- ✅ **Portfolio Dashboard** - Real-time portfolio views and performance tracking
- ✅ **Trade Management** - Interactive trade entry, history, and analysis

### **Data Management & Reliability**
- ✅ **Dual-Write Strategy** - Database + CSV for maximum data integrity
- ✅ **Automatic Retry Logic** - Session-based retry for failed operations
- ✅ **Data Validation** - Comprehensive input validation and error handling
- ✅ **Export/Import** - CSV-based data portability and backup
- ✅ **Audit Trail** - Complete transaction and change logging

## 📋 **System Requirements**

### **Minimum Requirements**
- **🐍 Python 3.8+** (Python 3.11+ recommended for optimal performance)
- **🐘 PHP 8.0+** with extensions: `pdo_mysql`, `json`, `curl`, `mbstring`
- **🗄️ MySQL 5.7+** or **MariaDB 10.3+**
- **💾 Storage:** ~100MB for application, ~50MB for data files
- **🌐 Network:** Internet connection for real-time market data
- **🧠 Memory:** 512MB RAM minimum, 2GB+ recommended

### **Recommended Production Environment**
- **💻 OS:** Linux (Ubuntu 20.04+), Windows 10+, or macOS 10.15+
- **⚡ CPU:** Multi-core processor for background job processing
- **🗄️ Database:** MySQL 8.0+ with InnoDB storage engine
- **🔧 Web Server:** Apache 2.4+ or Nginx 1.18+
- **📊 Monitoring:** System monitoring for production deployments

## 🚀 **Installation**

### **📦 Quick Installation (Development)**
```bash
# Clone the repository
git clone https://github.com/ksfraser/ChatGPT-Micro-Cap-Experiment.git
cd ChatGPT-Micro-Cap-Experiment

# Install Python dependencies
pip install -r requirements.txt

# Install PHP dependencies (requires Composer)
composer install

# Set up database (see Database Setup section)
# Configure web server to point to web_ui/ directory
```

### **🐳 Docker Installation (Recommended)**
```bash
# Using Docker Compose for full stack
docker-compose up -d

# Access the application at http://localhost:8080
# Database will be automatically configured
```

### **🔧 Manual Installation**

#### **1. Python Environment Setup**
```bash
# Create virtual environment (recommended)
python -m venv trading_env
source trading_env/bin/activate  # Linux/Mac
# or
trading_env\Scripts\activate     # Windows

# Install Python packages
pip install --upgrade pip
pip install -r requirements.txt

# For systems with older Python versions
pip install -r requirements-fedora30.txt  # Python 3.7 compatibility
```

#### **2. PHP Environment Setup**
```bash
# Install Composer (if not already installed)
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install TA-Lib PHP extension
composer require lupecode/php-trader-native
```

#### **3. Database Setup**
```sql
-- Create database and user
CREATE DATABASE chatgpt_trading CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'trading_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON chatgpt_trading.* TO 'trading_user'@'localhost';
FLUSH PRIVILEGES;

-- Import database schema
mysql -u trading_user -p chatgpt_trading < database/schema.sql
```

#### **4. Web Server Configuration**

**Apache (.htaccess example):**
```apache
DocumentRoot /path/to/ChatGPT-Micro-Cap-Experiment/web_ui
<Directory /path/to/ChatGPT-Micro-Cap-Experiment/web_ui>
    AllowOverride All
    Require all granted
</Directory>
```

**Nginx (site configuration example):**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/ChatGPT-Micro-Cap-Experiment/web_ui;
    index index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### **⚙️ Configuration**

#### **1. Database Configuration**
Create `web_ui/config/database.php`:
```php
<?php
return [
    'host' => 'localhost',
    'database' => 'chatgpt_trading', 
    'username' => 'trading_user',
    'password' => 'your_secure_password',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
];
```

#### **2. Application Configuration**  
Create `config/app.php`:
```php
<?php
return [
    'app_name' => 'ChatGPT Trading Platform',
    'debug' => false, // Set to true for development
    'timezone' => 'America/New_York',
    'session_timeout' => 3600, // 1 hour
    'csrf_protection' => true,
];
```

### **🔧 Development Setup**
```bash
# Install development dependencies
composer install
pip install -r requirements-dev.txt

# Enable debug mode
export APP_DEBUG=true

# Run development server (PHP built-in)
php -S localhost:8000 -t web_ui/

# Run background job worker
php worker.php --config=config/worker.json
```

### **🧪 Testing Setup**
```bash
# Run test suite
cd tests/
php -f unit/test_runner.php

# Run integration tests
php -f integration/integration_test_runner.php

# Generate test coverage report
phpunit --coverage-html coverage/
```

### **🚨 Troubleshooting**

#### **Common Installation Issues:**
1. **TA-Lib Installation Fails**
   ```bash
   # Install TA-Lib system library first
   # Ubuntu/Debian:
   sudo apt-get install ta-lib-dev
   
   # CentOS/RHEL:
   sudo yum install ta-lib-devel
   
   # macOS:
   brew install ta-lib
   ```

2. **PHP Extension Missing**
   ```bash
   # Install required PHP extensions
   sudo apt-get install php-mysql php-curl php-json php-mbstring
   ```

3. **Database Connection Errors**
   - Verify MySQL service is running
   - Check firewall settings
   - Confirm credentials in configuration file

4. **Permission Errors**
   ```bash
   # Set correct permissions for web directory
   sudo chown -R www-data:www-data web_ui/
   chmod -R 755 web_ui/
   ```

#### **Platform-Specific Notes:**
- **🪟 Windows:** Use XAMPP or WampServer for easy PHP/MySQL setup
- **🍎 macOS:** Install via Homebrew: `brew install php mysql composer`
- **🐧 Linux:** Package manager installation recommended for dependencies

# 📖 **Documentation**

## **📚 Complete Documentation Suite**

### **🏗️ Architecture & Design**
- **[📋 Project Starting Point](docs/Project_Starting_Point.md)** - Complete project evolution and current status
- **[🏛️ System Requirements](docs/System_Requirements.md)** - Detailed functional and non-functional requirements
- **[🎯 Technical Design](docs/Technical_Design.md)** - Comprehensive technical architecture and implementation
- **[🔗 Class Integration Patterns](docs/Class_Integration_Patterns.md)** - System-wide integration and data flow analysis

### **💻 Development Documentation**
- **[🗂️ Dead Code Analysis](docs/Dead_Code_Analysis.md)** - Codebase cleanup and optimization recommendations
- **[📊 TA-Lib Integration](docs/TA-Lib_Integration_Analysis.md)** - Technical analysis implementation details
- **[🧪 Testing Guide](tests/README.md)** - Comprehensive testing framework and standards

### **🎯 Business & Requirements**
- **[📋 Project Vision](docs/Project_Vision.md)** - Executive summary and business strategy
- **[📖 User Stories](docs/User_Stories.md)** - Detailed user requirements and acceptance criteria
- **[🔧 Configuration Guide](docs/Configuration.md)** - Environment setup and configuration options

### **🔍 Research & Analysis**
- **[📈 Weekly Research Summaries](Weekly%20Deep%20Research%20(MD)/)** - Performance analysis and market insights
- **[📊 Full Research Reports (PDF)](Weekly%20Deep%20Research%20(PDF)/)** - Comprehensive research documentation
- **[❓ Q&A Documentation](Experiment%20Details/Q&A.md)** - Frequently asked questions and answers

## **🎯 API Documentation**

### **📊 Auto-Generated API Docs**
```bash
# Generate comprehensive API documentation
composer require phpdocumentor/phpdocumentor
vendor/bin/phpdoc -f web_ui/ -t docs/api/

# View generated documentation
open docs/api/index.html
```

### **📋 Key API Endpoints**
- **Authentication:** `POST /api/auth/login`, `POST /api/auth/logout`
- **Portfolio:** `GET /api/portfolio`, `POST /api/portfolio/update`
- **Trades:** `GET /api/trades`, `POST /api/trades/execute`
- **Analytics:** `GET /api/analytics/performance`, `GET /api/analytics/indicators`

### **🔧 UML Diagrams & Architecture**
All major classes include comprehensive PHPDoc comments with embedded UML diagrams:
- **Class Diagrams:** Inheritance hierarchies and relationships
- **Sequence Diagrams:** Method call flows and interactions  
- **Activity Diagrams:** Business logic and decision flows
- **State Diagrams:** System state transitions

## **🚀 Getting Started Guides**

### **👥 For Users**
1. **[🏁 Quick Start](Start%20Your%20Own/README.md)** - Set up your own trading experiment
2. **[📱 Web Interface Guide](docs/User_Guide.md)** - Complete web application walkthrough
3. **[📊 Portfolio Management](docs/Portfolio_Guide.md)** - Managing positions and analyzing performance

### **💻 For Developers** 
1. **[🔧 Development Setup](docs/Development_Setup.md)** - Local development environment
2. **[🏗️ Architecture Overview](#architecture-overview)** - System design and patterns
3. **[🧪 Testing Framework](tests/README.md)** - Unit and integration testing
4. **[📝 Contributing Guide](docs/Contributing.md)** - Code standards and contribution process

### **🎯 For Analysts**
1. **[📊 Technical Analysis](docs/Technical_Analysis.md)** - TA-Lib integration and indicators
2. **[📈 Performance Metrics](docs/Performance_Analytics.md)** - Risk analysis and reporting
3. **[🔍 Research Methodology](Experiment%20Details/Prompts.md)** - AI-driven analysis approach

## **📈 Live Experiment Updates**

### **🎯 Current Performance Status**
**Experiment Duration:** June 2025 - December 2025  
**Update Frequency:** Every trading day  
**Performance Tracking:** Real-time CSV updates in `Scripts and CSV Files/`

### **📊 Key Metrics Dashboard**
- **📈 Total Return:** Track vs S&P 500 benchmark  
- **📉 Maximum Drawdown:** Risk assessment and control
- **⚡ Sharpe Ratio:** Risk-adjusted performance measurement
- **🎯 Win Rate:** Trade success percentage and analysis

### **📝 Weekly Research Updates**
Every week includes:
- 📊 **Performance Analysis** - Detailed P&L and risk metrics
- 🔍 **Market Research** - AI-driven fundamental analysis  
- 📈 **Technical Analysis** - Chart patterns and indicator signals
- 🎯 **Strategy Updates** - Portfolio rebalancing and new positions

## **🔗 External Resources**

### **📰 Blog & Updates**
- **[📝 Weekly Blog Posts](https://nathanbsmith729.substack.com)** - "A.I Controls Stock Account" 
- **[📊 Live Performance Data](Scripts%20and%20CSV%20Files/)** - Real-time portfolio updates
- **[💬 Community Discussion](Experiment%20Details/Chats.md)** - ChatGPT conversation logs

### **🎓 Educational Resources**
- **[📚 Methodology](Experiment%20Details/Disclaimer.md)** - Experimental design and limitations
- **[🔬 Research Process](Experiment%20Details/Deep%20Research%20Index.md)** - Weekly analysis framework
- **[💡 Prompt Engineering](Experiment%20Details/Prompts.md)** - AI interaction strategies

## **📧 Contact & Support**

### **💬 Get Involved**
- **🐛 Bug Reports:** [GitHub Issues](https://github.com/ksfraser/ChatGPT-Micro-Cap-Experiment/issues)
- **💡 Feature Requests:** [GitHub Discussions](https://github.com/ksfraser/ChatGPT-Micro-Cap-Experiment/discussions)  
- **📧 Direct Contact:** nathanbsmith.business@gmail.com
- **📊 Performance Updates:** [Weekly Blog](https://nathanbsmith729.substack.com)

### **🤝 Contributing**
We welcome contributions! Please see our [Contributing Guide](docs/Contributing.md) for:
- 📝 Code style and standards
- 🧪 Testing requirements  
- 📋 Pull request process
- 🔧 Development workflow

### **📜 License & Disclaimer**
- **📋 License:** MIT License - see [LICENSE](LICENSE) file
- **⚠️ Disclaimer:** [Risk Warning](Experiment%20Details/Disclaimer.md) - Not financial advice
- **🔒 Privacy:** No personal financial data is stored or transmitted

---

## **🏆 Project Achievements**

✅ **Professional Architecture** - Multi-layered design with 150+ technical indicators  
✅ **Production Ready** - Comprehensive error handling, security, and testing  
✅ **Fully Documented** - Complete UML diagrams and API documentation  
✅ **Real Trading Results** - Live experiment with transparent performance tracking  
✅ **Open Source** - Available for community use and contribution  

**⭐ Star this repo to follow along with the experiment!**
