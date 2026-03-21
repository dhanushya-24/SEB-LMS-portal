@echo off
title SIET-LMS Practice Mode Launcher
echo ============================================
echo   SIET-LMS — Practice Mode
echo   Launching Safe Exam Browser...
echo ============================================
echo.
echo Flow: SEB > Login > Mode Selection > Practice Mode
echo.

SET SEB_PATH="C:\Program Files\SafeExamBrowser\SafeExamBrowser.exe"
SET SEB_PATH2="C:\Program Files (x86)\SafeExamBrowser\SafeExamBrowser.exe"
SET CONFIG=%~dp0seb_configs\practice_mode.seb

IF EXIST %SEB_PATH% (
    echo Starting SEB from Program Files...
    start "" %SEB_PATH% %CONFIG%
) ELSE IF EXIST %SEB_PATH2% (
    echo Starting SEB from Program Files (x86)...
    start "" %SEB_PATH2% %CONFIG%
) ELSE (
    echo ERROR: Safe Exam Browser not found!
    echo.
    echo Please install SEB from: https://safeexambrowser.org/download_en.html
    echo Then run this file again.
    echo.
    pause
)
