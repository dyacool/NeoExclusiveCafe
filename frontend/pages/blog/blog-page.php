<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Blog";
$additional_css = [
    "../../css/users/blog-page.css"
];

require_once "../../user-includes/user-header.php";