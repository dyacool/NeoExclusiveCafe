<?php
session_start();
session_unset();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/'); // Ensure session is fully removed

header("Location: admin-login.php");
exit();
?>
