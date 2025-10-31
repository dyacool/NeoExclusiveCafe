# Database Migration: Saved Customer Info

This directory contains the database migration files for the saved customer information feature.

## Files

- `create_saved_customer_info_table.sql` - Main migration SQL script
- `rollback_saved_customer_info_table.sql` - Rollback script to undo migration
- `test_saved_customer_info_table.sql` - Test queries to verify table structure
- `run_migration.php` - PHP script to execute the migration
- `README.md` - This file

## Running the Migration

### Option 1: Using PHP Script (Recommended)

1. Open terminal/command prompt
2. Navigate to this directory:
   ```bash
   cd backend/database/migrations
   ```
3. Run the migration script:
   ```bash
   php run_migration.php
   ```
4. Verify the output shows success messages

### Option 2: Using MySQL Client

1. Open your MySQL client (phpMyAdmin, MySQL Workbench, or command line)
2. Select the `neoexclusivecafe_crud` database
3. Copy and paste the contents of `create_saved_customer_info_table.sql`
4. Execute the SQL
5. Verify the table was created

### Option 3: Using Command Line

```bash
mysql -u 429123 -p neoexclusivecafe_crud < create_saved_customer_info_table.sql
```

## Verifying the Migration

After running the migration, you can verify it was successful by:

1. Checking if the table exists:
   ```sql
   SHOW TABLES LIKE 'saved_customer_info';
   ```

2. Viewing the table structure:
   ```sql
   DESCRIBE saved_customer_info;
   ```

3. Running the test script:
   ```bash
   mysql -u 429123 -p neoexclusivecafe_crud < test_saved_customer_info_table.sql
   ```

## Rolling Back

If you need to undo the migration:

### Using MySQL Client
```sql
DROP TABLE IF EXISTS saved_customer_info;
```

### Using Command Line
```bash
mysql -u 429123 -p neoexclusivecafe_crud < rollback_saved_customer_info_table.sql
```

## Table Structure

```
saved_customer_info
├── id (INT, PRIMARY KEY, AUTO_INCREMENT)
├── user_id (INT, NOT NULL, FK -> users.id)
├── label (VARCHAR(50), NULL)
├── first_name (VARCHAR(100), NOT NULL)
├── last_name (VARCHAR(100), NOT NULL)
├── email (VARCHAR(255), NOT NULL)
├── phone (VARCHAR(20), NOT NULL)
├── delivery_location_id (INT, NOT NULL, FK -> delivery_locations.delivery_id)
├── complete_address (TEXT, NOT NULL)
├── is_primary (TINYINT(1), DEFAULT 0)
├── created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
└── updated_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
```

## Constraints

- **Foreign Keys:**
  - `user_id` references `users(id)` with CASCADE DELETE
  - `delivery_location_id` references `delivery_locations(delivery_id)` with RESTRICT DELETE

- **Indexes:**
  - `idx_user_id` on `user_id`
  - `idx_is_primary` on `is_primary`
  - `idx_user_primary` on `(user_id, is_primary)`

- **Application-Level Constraints:**
  - Maximum 3 entries per user (enforced in PHP)
  - Only one primary entry per user (enforced in PHP)

## Notes

- The table uses `utf8mb4` character set for full Unicode support
- Timestamps are automatically managed by MySQL
- Foreign key constraints ensure data integrity
- Indexes optimize query performance for user lookups

## Troubleshooting

### Error: Table already exists
If you get an error that the table already exists, you can either:
1. Drop the existing table first (see Rolling Back section)
2. Modify the migration to use `CREATE TABLE IF NOT EXISTS` (already included)

### Error: Foreign key constraint fails
This means either:
1. The `users` table doesn't exist
2. The `delivery_locations` table doesn't exist
3. The referenced columns don't match the expected types

Verify both tables exist and have the correct structure.

### Error: Access denied
Make sure you're using the correct database credentials and have sufficient privileges to create tables.
