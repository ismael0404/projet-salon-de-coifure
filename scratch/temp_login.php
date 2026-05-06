<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_nom'] = 'Admin Test';
$_SESSION['user_role'] = 'admin';
header('Location: /coiffure_salon/pages/admin/dashboard.php');
?>
