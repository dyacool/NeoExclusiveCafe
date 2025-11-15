<?php
/**
 * Chatbot Database Migration Runner
 * Run this file once to create necessary tables for OTP and database settings
 */

require_once __DIR__ . '/../../../admin-includes/config.php';
require_once __DIR__ . '/../../../admin-includes/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Chatbot Migration Runner</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .error { color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .info { color: blue; background: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 4px; }
        h1 { color: #333; }
    </style>
</head>
<body>
    <h1>🔧 Chatbot Database Migration</h1>";

try {
    // Read SQL file
    $sqlFile = __DIR__ . '/create_otp_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: {$sqlFile}");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        throw new Exception("Failed to read SQL file");
    }
    
    echo "<div class='info'>📄 SQL file loaded successfully</div>";
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    echo "<div class='info'>📝 Found " . count($statements) . " SQL statements to execute</div>";
    
    // Execute each statement
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement)) continue;
        
        try {
            if ($conn->query($statement)) {
                $successCount++;
                
                // Show what was created
                if (preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $matches)) {
                    echo "<div class='success'>✅ Table created: {$matches[1]}</div>";
                } elseif (preg_match('/INSERT INTO.*?`(\w+)`/i', $statement, $matches)) {
                    echo "<div class='success'>✅ Default data inserted into: {$matches[1]}</div>";
                } elseif (preg_match('/DELETE FROM.*?`(\w+)`/i', $statement, $matches)) {
                    echo "<div class='success'>✅ Cleanup performed on: {$matches[1]}</div>";
                } else {
                    echo "<div class='success'>✅ Statement " . ($index + 1) . " executed successfully</div>";
                }
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "<div class='error'>❌ Error in statement " . ($index + 1) . ": " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<div class='info'><strong>Summary:</strong></div>";
    echo "<div class='success'>✅ Successful: {$successCount}</div>";
    
    if ($errorCount > 0) {
        echo "<div class='error'>❌ Failed: {$errorCount}</div>";
    }
    
    echo "<div class='info'>
        <h2>🎉 Migration Complete!</h2>
        <p>The following tables have been created/updated:</p>
        <ul>
            <li><strong>chatbot_otp</strong> - Stores OTP codes for admin verification</li>
            <li><strong>chatbot_access_tokens</strong> - Manages access tokens for database settings</li>
            <li><strong>chatbot_database_settings</strong> - Stores chatbot database configuration</li>
        </ul>
        <p>You can now safely delete this migration file or keep it for reference.</p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>❌ Migration Failed:</strong> " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
