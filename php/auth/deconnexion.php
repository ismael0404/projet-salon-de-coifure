<?php
session_start();
session_unset();
session_destroy();
session_start(); // Redémarrer une nouvelle session vide pour pouvoir stocker le flash message
$_SESSION['flash_success'] = "Vous avez été déconnecté avec succès.";
header("Location: /coiffure_salon/pages/connexion.php");
exit();
?>
