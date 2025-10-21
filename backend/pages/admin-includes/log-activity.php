<?php
function logAdminActivity($conn, $action_type, $action_description, $affected_table = null, $affected_id = null) {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_firstname']) || !isset($_SESSION['admin_lastname'])) {
        return false;
    }

    $admin_id = $_SESSION['admin_id'];
    $admin_name = $_SESSION['admin_firstname'] . ' ' . $_SESSION['admin_lastname'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_id, admin_name, action_type, action_description, affected_table, affected_id, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("issssss", $admin_id, $admin_name, $action_type, $action_description, $affected_table, $affected_id, $ip_address);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}