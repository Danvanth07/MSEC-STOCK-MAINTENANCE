@echo off
title MSEC SMART STOCK Server
echo ========================================================
echo   MSEC SMART STOCK — Starting Local Development Server
echo ========================================================
echo Target URL: http://localhost:8000
echo.

if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S localhost:8000 -t public
) else (
    where php >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        php -S localhost:8000 -t public
    ) else (
        echo [ERROR] PHP executable was not found at C:\xampp\php\php.exe or in system PATH.
        echo Please ensure XAMPP is installed or PHP is added to PATH.
        pause
    )
)
