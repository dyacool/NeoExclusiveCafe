@echo off
REM Automatic Order Status Update - Windows Task Scheduler Batch File
REM This file should be scheduled to run hourly between 6 AM and 10 PM

REM Set the path to PHP executable (adjust this to your PHP installation path)
SET PHP_PATH=C:\php\php.exe

REM Set the path to the auto-update script
SET SCRIPT_PATH=%~dp0auto-update-order-status.php

REM Log file path
SET LOG_PATH=%~dp0..\..\logs\auto-status-cron.log

REM Create logs directory if it doesn't exist
if not exist "%~dp0..\..\logs\" mkdir "%~dp0..\..\logs\"

REM Log the execution start
echo [%date% %time%] Starting auto-status update >> "%LOG_PATH%"

REM Execute the PHP script and append output to log
"%PHP_PATH%" "%SCRIPT_PATH%" >> "%LOG_PATH%" 2>&1

REM Log the execution end
echo [%date% %time%] Auto-status update completed >> "%LOG_PATH%"
echo. >> "%LOG_PATH%"

exit /b 0
