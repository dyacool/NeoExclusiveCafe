@echo off
REM Batch file to run auto-update-order-status.php via Windows Task Scheduler
REM This should be scheduled to run hourly during business hours

REM Change to the script directory
cd /d "%~dp0"

REM Run the PHP script
php auto-update-order-status.php

REM Log the execution
echo [%date% %time%] Auto-update executed >> auto-update.log
