<?php require_once 'config.php';
session_destroy();
header("Location: index.php?logout_success=1");
exit();
?>