@echo off
echo Setting up automated cart truncation for NeoCafe
echo ================================================
echo.

REM Get the current directory
set "SCRIPT_DIR=%~dp0"
set "PHP_PATH=C:\xampp\php\php.exe"
set "SCRIPT_PATH=%SCRIPT_DIR%auto-truncate-cart.php"

echo Current directory: %SCRIPT_DIR%
echo PHP path: %PHP_PATH%
echo Script path: %SCRIPT_PATH%
echo.

REM Check if PHP exists
if not exist "%PHP_PATH%" (
    echo ERROR: PHP not found at %PHP_PATH%
    echo Please update the PHP_PATH variable in this batch file
    echo to point to your PHP installation
    pause
    exit /b 1
)

REM Check if the script exists
if not exist "%SCRIPT_PATH%" (
    echo ERROR: auto-truncate-cart.php not found at %SCRIPT_PATH%
    echo Please ensure the script file exists in the same directory
    pause
    exit /b 1
)

echo Creating scheduled task for cart truncation...
echo.

REM Create a scheduled task that runs every 5 minutes
schtasks /create /tn "NeoCafe Cart Truncation" /tr "%PHP_PATH% %SCRIPT_PATH%" /sc minute /mo 5 /f

if %ERRORLEVEL% EQU 0 (
    echo SUCCESS: Scheduled task created successfully!
    echo.
    echo The task will run every 5 minutes to check if business hours are closed
    echo and automatically truncate the cart_availtoday table.
    echo.
    echo To view the task: schtasks /query /tn "NeoCafe Cart Truncation"
    echo To delete the task: schtasks /delete /tn "NeoCafe Cart Truncation" /f
    echo.
    echo Logs will be written to: %SCRIPT_DIR%cart-truncation.log
) else (
    echo ERROR: Failed to create scheduled task
    echo You may need to run this as Administrator
    echo.
    echo Manual setup instructions:
    echo 1. Open Task Scheduler (taskschd.msc)
    echo 2. Create Basic Task
    echo 3. Name: NeoCafe Cart Truncation
    echo 4. Trigger: Every 5 minutes
    echo 5. Action: Start a program
    echo 6. Program: %PHP_PATH%
    echo 7. Arguments: %SCRIPT_PATH%
)

echo.
echo Setup complete!
pause
