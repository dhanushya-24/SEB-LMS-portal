@echo off
title SIET-LMS Exam Mode Launcher
echo ============================================
echo   SIET-LMS — Exam Mode (SECURE)
echo   Launching Safe Exam Browser...
echo ============================================
echo.
echo Flow: SEB > Login > Mode Selection > Exam Selection > Exam
echo WARNING: Once exam starts, you CANNOT navigate away.
echo.

SET SEB_PATH="C:\Program Files\SafeExamBrowser\SafeExamBrowser.exe"
SET SEB_PATH2="C:\Program Files (x86)\SafeExamBrowser\SafeExamBrowser.exe"
SET CONFIG=%~dp0seb_configs\exam_mode.seb

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
