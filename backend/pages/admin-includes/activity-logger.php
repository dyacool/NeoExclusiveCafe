<?php
// Function to get the client's IP address
function getClientIP() {
    $ip = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Function to log admin activity
function logAdminActivity($conn, $action_type, $description, $affected_table = null, $affected_id = null) {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }

    $admin_id = $_SESSION['admin_id'];
    $admin_name = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
    $ip_address = getClientIP();

    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, admin_name, action_type, action_description, affected_table, affected_id, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("issssss", $admin_id, $admin_name, $action_type, $description, $affected_table, $affected_id, $ip_address);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

// Function to generate descriptive log messages
function generateLogMessage($action, $details) {
    switch ($action) {
        case 'LOGIN':
            return "Logged into the admin panel";
            
        case 'LOGOUT':
            return "Logged out from the admin panel";
            
        case 'CREATE':
            return "Created new {$details['type']}: {$details['name']}";
            
        case 'UPDATE':
            return "Updated {$details['type']}: {$details['name']}";
            
        case 'DELETE':
            return "Deleted {$details['type']}: {$details['name']}";
            
        case 'STATUS':
            return "Changed status of {$details['type']} #{$details['id']} to {$details['status']}";
            
        case 'SETTINGS':
            return "Modified {$details['type']} settings";
            
        default:
            return $details['message'] ?? 'Performed an action';
    }
}