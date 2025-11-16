<?php
// Base URL configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $host);

// Function to get the full URL
function get_full_url($path) {
    return BASE_URL . '/NeoExclusiveCafe' . $path;
} 