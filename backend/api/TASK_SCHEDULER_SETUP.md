# Windows Task Scheduler Setup for Auto-Status Updates

## Overview
This document explains how to set up Windows Task Scheduler to automatically run the order status update script hourly.

## Prerequisites
- PHP installed on the server
- Access to Windows Task Scheduler
- Admin privileges

## Setup Instructions

### Step 1: Locate PHP Installation
1. Find your PHP installation path (e.g., `C:\php\php.exe` or `C:\xampp\php\php.exe`)
2. Open `run-auto-status-update.bat` in a text editor
3. Update the `PHP_PATH` variable with your PHP path:
   ```batch
   SET PHP_PATH=C:\your\path\to\php.exe
   ```

### Step 2: Test the Batch File
1. Open Command Prompt as Administrator
2. Navigate to the `backend/api` directory
3. Run the batch file manually:
   ```cmd
   run-auto-status-update.bat
   ```
4. Check the log file at `backend/logs/auto-status-cron.log` to verify it ran successfully

### Step 3: Create Windows Task Scheduler Task
1. Open **Task Scheduler** (search for it in Windows Start menu)
2. Click **Create Basic Task** in the right panel
3. Name: `Auto Update Order Status`
4. Description: `Automatically updates order statuses based on delivery/pickup dates`
5. Click **Next**

### Step 4: Set Trigger (Schedule)
1. Select **Daily**
2. Click **Next**
3. Set Start date to today
4. Set Start time to **06:00 AM**
5. Recur every: **1 days**
6. Click **Next**

### Step 5: Set Action
1. Select **Start a program**
2. Click **Next**
3. Program/script: Browse to `run-auto-status-update.bat`
4. Start in: Enter the `backend/api` directory path
5. Click **Next**

### Step 6: Configure Advanced Settings
1. Check **Open the Properties dialog** when finished
2. Click **Finish**
3. In the Properties dialog:
   - Go to **Triggers** tab
   - Edit the trigger
   - Check **Repeat task every:** `1 hour`
   - For a duration of: `16 hours` (from 6 AM to 10 PM)
   - Click **OK**

### Step 7: Set Additional Options
1. In the **General** tab:
   - Check **Run whether user is logged on or not**
   - Check **Run with highest privileges**
2. In the **Conditions** tab:
   - Uncheck **Start the task only if the computer is on AC power**
3. In the **Settings** tab:
   - Check **Allow task to be run on demand**
   - Check **Run task as soon as possible after a scheduled start is missed**
   - If the task fails, restart every: **10 minutes**
   - Attempt to restart up to: **3 times**
4. Click **OK**

### Step 8: Test the Scheduled Task
1. In Task Scheduler, find your task in the list
2. Right-click and select **Run**
3. Check the **Last Run Result** column (should show success code 0x0)
4. Verify the log file was updated

## Monitoring

### Check Logs
- Cron execution log: `backend/logs/auto-status-cron.log`
- Auto-status updates log: `backend/logs/auto-status-updates.log`

### Verify Task is Running
1. Open Task Scheduler
2. Find the task in the list
3. Check **Last Run Time** and **Next Run Time**
4. Check **Last Run Result** (0x0 means success)

## Troubleshooting

### Task doesn't run
- Verify PHP path in batch file is correct
- Check Windows Event Viewer for errors
- Ensure the task has proper permissions

### Script runs but doesn't update orders
- Check if auto-status toggle is enabled in the admin panel
- Verify database connection in the script
- Check the auto-status-updates.log for errors

### Permission errors
- Run Task Scheduler as Administrator
- Ensure the batch file has execute permissions
- Check that the PHP user has database write permissions

## Manual Execution
To manually trigger the status update:
```cmd
cd path\to\backend\api
run-auto-status-update.bat
```

Or directly via PHP:
```cmd
php auto-update-order-status.php
```

## Disabling Auto-Updates
To temporarily disable:
1. Open Task Scheduler
2. Find the task
3. Right-click and select **Disable**

Or disable via the admin panel toggle switch.
