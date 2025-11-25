# ChatGPT Micro-Cap Experiment

## Project Structure

This repository contains TWO distinct projects:

### 1. Original ChatGPT Micro-Cap Experiment (Root)

The original forked project for micro-cap trading experiments with ChatGPT.

**Location**: Root directory (outside of Stock-Analysis/)

This includes the original automation scripts, CSVs, and experimental trading code.

### 2. Stock Analysis System (Our Work)

A comprehensive PHP-based stock analysis platform with AI integration.

**Location**: `Stock-Analysis/` directory

```
Stock-Analysis/
├── app/                      # PHP MVC Application
├── api/                      # REST API Endpoints  
├── python_analysis/          # Python AI Module
├── database/                 # Database Schema
├── web_ui/                   # Legacy code (being refactored)
└── Project_Work_Products/    # Documentation & Requirements
```

**See**: [Stock-Analysis/README.md](Stock-Analysis/README.md) for complete documentation

## Quick Start

### For Stock Analysis System

```bash
cd Stock-Analysis

# Install dependencies
composer install
pip install pandas numpy ta

# Setup database
mysql -u root -p < database/schema.sql

# Test integration
php ../test_php_python_integration.php
```

### For Original Micro-Cap Experiment

See original README sections below.

---

## Original Project Documentation

The sections below document the original ChatGPT Micro-Cap Experiment:

