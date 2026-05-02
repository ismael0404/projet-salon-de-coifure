<?php
// Configuration de la base de données
$host = 'localhost';
$database = 'tya_stylex';
$username = 'root';
$password = '';

try {
    // Connexion PDO à MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    
    // Configuration des erreurs PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Pour déboguer (optionnel, à désactiver en production)
    // echo "Connexion réussie à la base de données";
    
} catch(PDOException $e) {
    // En cas d'erreur de connexion
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>