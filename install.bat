@echo off
title Court System Installer
color 0A

echo.
echo ===============================================
echo        COURT SYSTEM INSTALLER
echo ===============================================
echo.

:: Check if Python is installed
python --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Python is not installed or not in PATH
    echo Please install Python 3.6+ from https://python.org
    echo.
    pause
    exit /b 1
)

:: Check if pip is available
pip --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] pip is not available
    echo Please ensure pip is installed with Python
    echo.
    pause
    exit /b 1
)

echo [INFO] Installing required Python packages...
pip install -r requirements.txt

if errorlevel 1 (
    echo [ERROR] Failed to install required packages
    echo Please check your internet connection and try again
    echo.
    pause
    exit /b 1
)

echo.
echo [INFO] Starting Court System Installer...
echo.

:: Run the Python installer
python installer.py

echo.
pause
