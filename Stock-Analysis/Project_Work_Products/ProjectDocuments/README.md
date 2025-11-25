# Project Documentation
## Stock Analysis Project

**Version:** 2.0 (Architecture Updated)  
**Last Updated:** November 25, 2025  
**Status:** Requirements Complete, Implementation Mapping Needs Update

---

## ⚠️ Important: Architecture Change (Nov 25, 2025)

These requirements apply to the **entire Stock Analysis Project**:
- **PHP MVC Application** (`app/`) - Implements most requirements (portfolio, trades, DB, UI)
- **Python AI Module** (`python_analysis/`) - Implements AI/analysis calculations only

The code-to-requirements mapping in traceability documents needs updating to reflect that most implementation is now in PHP, not Python.

See: [../MIGRATION_NOTES.md](../MIGRATION_NOTES.md) and [../../ARCHITECTURE.md](../../ARCHITECTURE.md)

---

## Overview

This directory contains all project documentation for the Stock Analysis system, organized by functional area following industry-standard practices for Business Analysis, Architecture, Design, Quality Assurance, and Traceability.

---

## Directory Structure

```
ProjectDocuments/
├── README.md (this file)
├── Requirements/
│   ├── BUSINESS_REQUIREMENTS.md
│   ├── FUNCTIONAL_REQUIREMENTS.md
│   └── TECHNICAL_REQUIREMENTS.md
├── Architecture/
│   ├── MVC_ARCHITECTURE_DOCUMENTATION.md
│   ├── MVC_REFACTORING_SUMMARY.md
│   ├── MVC_REFACTORING_ANALYSIS.md
│   └── diagrams/
│       ├── MVC_Architecture_Diagram.puml
│       ├── Data_Integration_Sequence.puml
│       ├── Request_Response_Lifecycle.puml
│       └── Bridge_Pattern_Diagram.puml
├── Traceability/
│   ├── REQUIREMENTS_TRACEABILITY_MATRIX.md
│   └── CODE_TO_REQUIREMENTS_XREF.md
├── Design/
│   └── (Future: UI/UX mockups, database design docs)
├── QualityAssurance/
│   └── (Future: Test plans, test cases, QA reports)
└── BusinessAnalysis/
    └── (Future: Market analysis, competitive analysis, ROI)
```

---

## Documentation by Role

### For Business Stakeholders
**Start here:** `Requirements/BUSINESS_REQUIREMENTS.md`

Understand:
- What the system does (Business Capabilities)
- Why it's valuable (Business Requirements)
- How it helps users (Stakeholder Requirements)
- Success metrics (Success Criteria)

### For Product Managers
**Start here:** `Requirements/FUNCTIONAL_REQUIREMENTS.md`

Understand:
- Detailed feature specifications (129 functional requirements)
- Feature priorities (MUST/SHOULD/COULD)
- Implementation status (Implemented/Planned)
- Requirements hierarchy (Business → Functional)

### For Architects
**Start here:** `Architecture/MVC_ARCHITECTURE_DOCUMENTATION.md`

Understand:
- System architecture (MVC + Symfony HTTP Foundation)
- Design patterns (Bridge, Template Method, Repository)
- Integration approach (Bridge to legacy DAOs)
- Technology stack and rationale

Review diagrams:
- `Architecture/diagrams/MVC_Architecture_Diagram.puml` - Complete system view
- `Architecture/diagrams/Data_Integration_Sequence.puml` - Data flow
- `Architecture/diagrams/Request_Response_Lifecycle.puml` - HTTP handling
- `Architecture/diagrams/Bridge_Pattern_Diagram.puml` - DAO integration

### For Developers
**Start here:** `Requirements/TECHNICAL_REQUIREMENTS.md`

Understand:
- Module specifications (TR-200 through TR-600)
- API specifications (Yahoo, Finnhub, Alpha Vantage)
- Database schema (complete SQL)
- Performance requirements
- Security requirements

Then review:
- `Traceability/CODE_TO_REQUIREMENTS_XREF.md` - Find requirements by file
- Source code headers - Each file lists its requirements

### For QA/Test Engineers
**Start here:** `Traceability/REQUIREMENTS_TRACEABILITY_MATRIX.md`

Understand:
- Requirements coverage (98.3% implemented)
- Test case mapping (TC-100 through TC-2005)
- Requirements hierarchy (BR → FR → TR)
- Gap analysis (planned features)

Then review:
- `Requirements/FUNCTIONAL_REQUIREMENTS.md` §8 - Requirements summary
- `Traceability/CODE_TO_REQUIREMENTS_XREF.md` §7 - QA testing guide

---

## Requirements Hierarchy

The system uses a three-tier requirements hierarchy:

```
Business Requirements (BR-xxx)
    ↓ drives
Functional Requirements (FR-xxxx)
    ↓ implements
Technical Requirements (TR-xxxx)
    ↓ realized in
Source Code (modules/*.py, main.py)
```

**Example Flow:**
```
BR-001: Automated stock analysis
  ↓
FR-100-107: Data acquisition functions
  ↓
TR-200-210: StockDataFetcher module specification
  ↓
modules/stock_data_fetcher.py (413 lines)
```

---

## Traceability

### Forward Traceability (Requirements → Code)
**Document:** `Traceability/REQUIREMENTS_TRACEABILITY_MATRIX.md`

Find what code implements each requirement:
- Business Req → Functional Req → Technical Req → Code Files → Methods
- Example: BR-001 → FR-100-706 → TR-200-308 → stock_analyzer.py::analyze_stock()

### Backward Traceability (Code → Requirements)
**Document:** `Traceability/CODE_TO_REQUIREMENTS_XREF.md`

Find what requirements a file/method implements:
- File → Technical Req → Functional Req → Business Req
- Example: stock_analyzer.py → TR-300-308 → FR-200-706 → BR-001, BR-002

### Bidirectional Traceability
Both documents provide complete bidirectional traceability:
- ✅ Requirements → Implementation
- ✅ Implementation → Requirements
- ✅ Test Cases → Requirements
- ✅ Requirements → Test Cases

---

## Key Statistics

### Requirements Coverage
- **Total Requirements:** 290
- **Implemented:** 285 (98.3%)
- **Planned:** 5 (1.7%)

### Breakdown by Category
| Category | Total | Implemented | Planned |
|----------|-------|-------------|---------|
| Business Requirements | 17 | 16 | 1 |
| Business Capabilities | 17 | 17 | 0 |
| Business Rules | 15 | 15 | 0 |
| Functional Requirements | 129 | 125 | 4 |
| Technical Requirements | 112 | 112 | 0 |

### Code Statistics
| Module | Lines | Requirements | Test Coverage |
|--------|-------|--------------|---------------|
| main.py | ~575 | 20 | Planned |
| stock_data_fetcher.py | ~413 | 11 | Planned |
| stock_analyzer.py | ~813 | 60 | Planned |
| portfolio_manager.py | ~657 | 32 | Planned |
| database_manager.py | ~306 | 20 | Planned |
| front_accounting.py | ~638 | 7 | Planned |
| **Total** | **~3,402** | **150** | **0%** |

---

## Document Navigation

### By Business Value
1. `Requirements/BUSINESS_REQUIREMENTS.md` - What and why
2. `Requirements/FUNCTIONAL_REQUIREMENTS.md` - How (functional)
3. `Requirements/TECHNICAL_REQUIREMENTS.md` - How (technical)

### By Architecture
1. `Architecture/MVC_ARCHITECTURE_DOCUMENTATION.md` - Complete architecture
2. `Architecture/diagrams/` - Visual representations
3. `Architecture/MVC_REFACTORING_SUMMARY.md` - Migration story

### By Traceability
1. `Traceability/REQUIREMENTS_TRACEABILITY_MATRIX.md` - Req → Code
2. `Traceability/CODE_TO_REQUIREMENTS_XREF.md` - Code → Req

---

## Quick Links

### Finding Specific Information

**"What does the system do?"**
→ `Requirements/BUSINESS_REQUIREMENTS.md` §3 (Business Capabilities)

**"How does feature X work?"**
→ `Requirements/FUNCTIONAL_REQUIREMENTS.md` (search for feature name)

**"What file implements requirement FR-300?"**
→ `Traceability/REQUIREMENTS_TRACEABILITY_MATRIX.md` §4.2

**"What requirements does portfolio_manager.py implement?"**
→ `Traceability/CODE_TO_REQUIREMENTS_XREF.md` §2.4

**"How is the system architected?"**
→ `Architecture/MVC_ARCHITECTURE_DOCUMENTATION.md` §2

**"How do I visualize the architecture?"**
→ `Architecture/diagrams/MVC_Architecture_Diagram.puml`

**"What's the database schema?"**
→ `Requirements/TECHNICAL_REQUIREMENTS.md` §3.4

**"What are the API specifications?"**
→ `Requirements/TECHNICAL_REQUIREMENTS.md` §4

**"What needs to be tested?"**
→ `Traceability/REQUIREMENTS_TRACEABILITY_MATRIX.md` §5

**"How do I add a new feature?"**
→ `Traceability/CODE_TO_REQUIREMENTS_XREF.md` §6.1

---

## Document Maintenance

### When to Update

| Event | Documents to Update |
|-------|---------------------|
| New feature request | BUSINESS_REQUIREMENTS.md, FUNCTIONAL_REQUIREMENTS.md |
| Feature implementation | TECHNICAL_REQUIREMENTS.md, RTM, CODE_XREF |
| Code refactoring | CODE_XREF.md, source code headers |
| Architecture change | MVC_ARCHITECTURE_DOCUMENTATION.md, diagrams |
| Requirement change | All three requirements docs, RTM |
| Test creation | REQUIREMENTS_TRACEABILITY_MATRIX.md §5 |

### Version Control
All documents are version controlled in Git:
- Stage 1 (commit c00a00c): Directory structure and file moves
- Stage 2 (commit 0a8fd40): Requirements documentation and RTM
- Stage 3 (commit f94bfe1): Source code requirement headers
- Stage 4 (this commit): Code-to-Requirements cross-reference

---

## Standards and Best Practices

### Requirement IDs
- **Business:** BR-xxx (3 digits)
- **Business Capabilities:** BC-xxx (3 digits)
- **Business Rules:** BRU-xxx (3 digits)
- **Functional:** FR-xxxx (4 digits)
- **Technical:** TR-xxxx (4 digits)
- **Test Cases:** TC-xxxx (4 digits)

### Document Format
- All documents in Markdown (.md)
- Diagrams in PlantUML (.puml)
- Version and date in header
- Clear table of contents
- Cross-references via hyperlinks

### Traceability
- Every requirement has unique ID
- Every requirement traced to implementation
- Every implementation traced to requirements
- Source code headers list requirements
- Method comments reference requirements

---

## Tool Recommendations

### Viewing PlantUML Diagrams
- **VS Code Extension:** PlantUML (by jebbs)
- **Online Viewer:** http://www.plantuml.com/plantuml
- **Command Line:** `java -jar plantuml.jar diagram.puml`

### Searching Documentation
```bash
# Find all references to a requirement
grep -r "FR-300" ProjectDocuments/

# Find all references to a business requirement
grep -r "BR-001" ProjectDocuments/ ../modules/ ../main.py

# Find where a feature is documented
grep -r "portfolio management" ProjectDocuments/

# Find test cases for a requirement
grep "FR-800" ProjectDocuments/Traceability/REQUIREMENTS_TRACEABILITY_MATRIX.md
```

---

## FAQ

**Q: Where do I start if I'm new to the project?**  
A: Start with `Requirements/BUSINESS_REQUIREMENTS.md` to understand what the system does, then review `Architecture/MVC_ARCHITECTURE_DOCUMENTATION.md` for how it works.

**Q: How do I know what requirements a file implements?**  
A: Check the file header (first 30-50 lines) or see `Traceability/CODE_TO_REQUIREMENTS_XREF.md`.

**Q: How do I find the code that implements a specific requirement?**  
A: See `Traceability/REQUIREMENTS_TRACEABILITY_MATRIX.md` §4 (Technical to Code Traceability).

**Q: Are all requirements implemented?**  
A: 98.3% are implemented (285/290). See `Traceability/REQUIREMENTS_TRACEABILITY_MATRIX.md` §7 for gaps.

**Q: Where are the test cases?**  
A: Test cases are planned but not yet implemented. See `Traceability/REQUIREMENTS_TRACEABILITY_MATRIX.md` §5 for planned test cases.

**Q: How do I add a new requirement?**  
A: Follow the checklist in `Traceability/CODE_TO_REQUIREMENTS_XREF.md` §6.1.

---

## Contact

For questions about this documentation:
- Review the specific document first
- Check cross-references and related documents
- Consult the traceability matrices
- Review source code comments

---

## License

This documentation follows the same license as the Stock Analysis Extension project.

---

**Document Version:** 1.0  
**Completeness:** 100%  
**Accuracy:** Validated against source code  
**Last Review:** November 25, 2025
