<?php
/**
 * Settings Helper Functions
 * Provides functions to get and set system-wide settings
 */

require_once __DIR__ . '/database.php';

/**
 * Get a setting value from the database
 * 
 * @param string $key The setting key
 * @param mixed $default Default value if setting doesn't exist
 * @return mixed The setting value
 */
function getSetting($key, $default = null) {
    global $conn;
    
    // Check if system_settings table exists
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
        // Table doesn't exist, return default value
        return $default;
    }
    
    $key = mysqli_real_escape_string($conn, $key);
    $sql = "SELECT setting_value, setting_type FROM system_settings WHERE setting_key = '$key'";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $value = $row['setting_value'];
        $type = $row['setting_type'];
        
        // Parse value based on type
        switch ($type) {
            case 'json':
                return json_decode($value, true);
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return intval($value);
            default:
                return $value;
        }
    }
    
    return $default;
}

/**
 * Set a setting value in the database
 * 
 * @param string $key The setting key
 * @param mixed $value The setting value
 * @param string $type The data type (string, json, boolean, integer)
 * @param string $description Optional description
 * @return bool Success status
 */
function setSetting($key, $value, $type = 'string', $description = null) {
    global $conn;
    
    // Check if system_settings table exists
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'system_settings'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) == 0) {
        // Table doesn't exist, return false
        error_log("Warning: system_settings table does not exist. Please run the migration.");
        return false;
    }
    
    $key = mysqli_real_escape_string($conn, $key);
    
    // Convert value based on type
    switch ($type) {
        case 'json':
            $value = json_encode($value);
            break;
        case 'boolean':
            $value = $value ? '1' : '0';
            break;
        case 'integer':
            $value = strval(intval($value));
            break;
        default:
            $value = strval($value);
    }
    
    $value = mysqli_real_escape_string($conn, $value);
    $type = mysqli_real_escape_string($conn, $type);
    
    // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both insert and update
    if ($description !== null) {
        $description = mysqli_real_escape_string($conn, $description);
        $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type, description) 
                VALUES ('$key', '$value', '$type', '$description')
                ON DUPLICATE KEY UPDATE 
                    setting_value = '$value', 
                    setting_type = '$type',
                    description = '$description',
                    updated_at = CURRENT_TIMESTAMP";
    } else {
        $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type) 
                VALUES ('$key', '$value', '$type')
                ON DUPLICATE KEY UPDATE 
                    setting_value = '$value', 
                    setting_type = '$type',
                    updated_at = CURRENT_TIMESTAMP";
    }
    
    return mysqli_query($conn, $sql);
}

/**
 * Delete a setting from the database
 * 
 * @param string $key The setting key
 * @return bool Success status
 */
function deleteSetting($key) {
    global $conn;
    
    $key = mysqli_real_escape_string($conn, $key);
    $sql = "DELETE FROM system_settings WHERE setting_key = '$key'";
    
    return mysqli_query($conn, $sql);
}

/**
 * Check if a setting exists
 * 
 * @param string $key The setting key
 * @return bool True if setting exists
 */
function settingExists($key) {
    global $conn;
    
    $key = mysqli_real_escape_string($conn, $key);
    $sql = "SELECT COUNT(*) as count FROM system_settings WHERE setting_key = '$key'";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['count'] > 0;
    }
    
    return false;
}
// End of file - no closing PHP tag to prevent whitespace issues