<?php
require_once 'c:/xampp/htdocs/coiffure_salon/php/config/database.php';
$stmt = $pdo->query("SELECT login, mot_de_passe, role FROM utilisateurs WHERE role='admin' LIMIT 1");
$admin = $stmt->fetch();
print_r($admin);
?>
