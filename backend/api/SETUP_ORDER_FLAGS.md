# Setup Order Update Flags Table

## Quick Setup

The `order_update_flags` table is required for real-time order notifications to work on the dashboard and order list.

### Run Setup Script

Visit this URL in your browser (replace with your actual domain):

```
http://your-domain.com/NeoCafe/backend/api/setup-order-update-flags-table.php
```

Or run from command line:

```bash
php backend/api/setup-order-update-flags-table.php
```

### Expected Response

```json
{
    "success": true,
    "table_exists": true,
    "results": [
        {
            "statement": "CREATE TABLE IF NOT EXISTS order_update_flags...",
            "success": true
        },
        {
            "statement": "CREATE EVENT IF NOT EXISTS cleanup_old_order_flags...",
            "success": true
        }
    ],
    "message": "Table created successfully!"
}
```

### What This Does

1. Creates the `order_update_flags` table
2. Sets up automatic cleanup event (deletes flags older than 1 minute)
3. Enables real-time notifications for:
   - Dashboard polling
   - Order list polling

### How It Works

- When a new order is created, a flag is inserted
- Polling systems check for flags every 5 seconds
- Flags expire after 1 minute (auto-cleanup)
- Dashboard and order list refresh when flags are detected

### Troubleshooting

If the table doesn't exist after running the script:
1. Check MySQL user has CREATE TABLE permissions
2. Check MySQL user has CREATE EVENT permissions
3. Check error messages in the response
4. Manually run the SQL from `create-order-update-flags-table.sql`
