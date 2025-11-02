# Windows Task Scheduler Setup for Auto-Update Order Status

This guide explains how to set up the automatic order status update cron job on Windows using Task Scheduler.

## Prerequisites

- PHP installed and accessible from command line
- Admin access to Windows Task Scheduler
- Auto-status toggle enabled in the order management system

## Setup Instructions

### Method 1: Using Task Scheduler GUI

1. **Open Task Scheduler**
   - Press `Win + R`
   - Type `taskschd.msc` and press Enter

2. **Create a New Task**
   - Click "Create Task" (not "Create Basic Task")
   - Name: `Neo Cafe - Auto Update Order Status`
   - Description: `Automatically updates order statuses based on delivery/pickup dates`
   - Select "Run whether user is logged on or not"
   - Check "Run with highest privileges"

3. **Configure Triggers**
   - Go to "Triggers" tab
   - Click "New"
   - Begin the task: `On a schedule`
   - Settings: `Daily`
   - Recur every: `1 days`
   - Repeat task every: `1 hour`
   - For a duration of: `24 hours`
   - Click "OK"

4. **Configure Actions**
   - Go to "Actions" tab
   - Click "New"
   - Action: `Start a program`
   - Program/script: `C:\xampp\htdocs\NeoCafe\backend\api\run-auto-update.bat`
   - (Adjust path based on your installation)
   - Click "OK"

5. **Configure Conditions**
   - Go to "Conditions" tab
   - Uncheck "Start the task only if the computer is on AC power"
   - Check "Wake the computer to run this task" (optional)

6. **Configure Settings**
   - Go to "Settings" tab
   - Check "Allow task to be run on demand"
   - Check "Run task as soon as possible after a scheduled start is missed"
   - If the task fails, restart every: `10 minutes`
   - Attempt to restart up to: `3 times`

7. **Save the Task**
   - Click "OK"
   - Enter your Windows password if prompted

### Method 2: Using Command Line (PowerShell as Administrator)

```powershell
$action = New-ScheduledTaskAction -Execute "C:\xampp\htdocs\NeoCafe\backend\api\run-auto-update.bat"
$trigger = New-ScheduledTaskTrigger -Daily -At 6:00AM -RepetitionInterval (New-TimeSpan -Hours 1) -RepetitionDuration (New-TimeSpan -Hours 18)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

Register-ScheduledTask -TaskName "Neo Cafe - Auto Update Order Status" -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Description "Automatically updates order statuses based on delivery/pickup dates"
```

## Testing the Task

### Test Manually

1. Open Task Scheduler
2. Find "Neo Cafe - Auto Update Order Status" in the task list
3. Right-click and select "Run"
4. Check the "Last Run Result" column (should show "0x0" for success)

### Test via Command Line

```bash
cd C:\xampp\htdocs\NeoCafe\backend\api
php auto-update-order-status.php
```

### Check Logs

View the auto-update log file:
```bash
type C:\xampp\htdocs\NeoCafe\backend\api\auto-update.log
```

## How It Works

### Business Hours Integration

The auto-update script respects your business hours settings:

1. **Orders Due Tomorrow** → Always updated to "Preparing" (regardless of business hours)
2. **Orders Due Today** → Only updated to "Ready" status **during business hours**

This ensures that:
- Orders don't show as "Ready" at midnight when the business is closed
- Orders transition to "Ready" status when business hours start
- The system respects your operational schedule

### Status Transition Rules

**Pickup Orders:**
- `Confirmed` → `Preparing` (when due tomorrow)
- `Preparing` → `Ready for Pick-up` (when due today AND during business hours)

**Delivery Orders:**
- `Confirmed` → `Preparing` (when due tomorrow)
- `Preparing` → `Ready for Delivery` (when due today AND during business hours)

### Email Notifications

Customers receive automatic email notifications when their order status changes, including:
- Order number
- New status
- Link to view order details

## Troubleshooting

### Task Not Running

1. Check if the task is enabled in Task Scheduler
2. Verify the path to `run-auto-update.bat` is correct
3. Check Windows Event Viewer for error messages
4. Ensure PHP is in the system PATH

### No Orders Being Updated

1. Verify auto-status toggle is enabled in the order management page
2. Check that orders exist with appropriate dates and statuses
3. Review the `auto-update.log` file for errors
4. Manually run the script to see detailed output

### Business Hours Not Working

1. Verify business hours are set in the database:
   ```sql
   SELECT * FROM business_hours ORDER BY id DESC LIMIT 1;
   ```
2. Check current time is within business hours
3. Review the script output for business hours information

## Monitoring

### Check Last Execution

```powershell
Get-ScheduledTask -TaskName "Neo Cafe - Auto Update Order Status" | Get-ScheduledTaskInfo
```

### View Execution History

1. Open Task Scheduler
2. Find the task
3. Click on the "History" tab (enable if disabled)

## Disabling Auto-Update

To temporarily disable automatic updates:

1. **Via UI**: Toggle off the "Toggle auto-status" switch in the order management page
2. **Via Task Scheduler**: Disable the scheduled task
3. **Permanently**: Delete the scheduled task

## Support

For issues or questions, check:
- Auto-update log: `backend/api/auto-update.log`
- PHP error log: `C:\xampp\php\logs\php_error_log`
- Windows Event Viewer: Application logs
