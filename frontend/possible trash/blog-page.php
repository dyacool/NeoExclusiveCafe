<?php
// Load database first (starts session)
if (!isset($conn)) {
    require_once "../../../backend/pages/admin-includes/database.php";
}

$page_title = "Blog";
$additional_css = [
    "blog-page.css"
];

require_once "../../user-includes/user-header.php";