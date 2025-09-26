@echo off
echo 📚 ChatGPT Micro-Cap Experiment - Documentation Quality Assurance
echo ===============================================================
echo.

REM Check if Python is available
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Python not found. Please install Python 3.6+ to run documentation tests.
    pause
    exit /b 1
)

REM Create a simple menu
echo Please select an option:
echo.
echo 1. Quick Check (Links + Format + Images)
echo 2. Full Audit (All checks including spell check)
echo 3. Check Links Only
echo 4. Validate Format Only  
echo 5. Check Images Only
echo 6. Fix Broken Links
echo 7. Generate Quality Report
echo 8. Run All + Generate Report
echo.

set /p choice=Enter your choice (1-8): 

if "%choice%"=="1" (
    echo Running quick documentation check...
    python docs_qa_suite.py --check-links --validate-format --check-images
) else if "%choice%"=="2" (
    echo Running full documentation audit...
    python docs_qa_suite.py --full-audit
) else if "%choice%"=="3" (
    echo Checking links...
    python docs_qa_suite.py --check-links
) else if "%choice%"=="4" (
    echo Validating format...
    python docs_qa_suite.py --validate-format
) else if "%choice%"=="5" (
    echo Checking images...
    python docs_qa_suite.py --check-images
) else if "%choice%"=="6" (
    echo Attempting to fix broken links...
    python docs_qa_suite.py --check-links --fix-links
) else if "%choice%"=="7" (
    echo Generating quality report...
    python docs_qa_suite.py --check-links --validate-format --check-images --generate-report
) else if "%choice%"=="8" (
    echo Running complete audit with report generation...
    python docs_qa_suite.py --full-audit --generate-report
) else (
    echo Invalid choice. Please run the script again and select 1-8.
    pause
    exit /b 1
)

echo.
echo ===============================================================
echo Documentation QA complete. Check the output above for results.
echo.

if exist "docs_quality_report.json" (
    echo 📊 Quality report generated: docs_quality_report.json
    echo.
)

echo Press any key to exit...
pause >nul