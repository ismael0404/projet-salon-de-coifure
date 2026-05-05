<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Vérification du rôle admin
require_role('admin');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_error'] = "Erreur de sécurité (CSRF).";
        redirect('/coiffure_salon/pages/admin/employes.php');
    }

    if ($action === 'add_employe') {
        $nom = sanitize_input($_POST['nom']);
        $prenom = sanitize_input($_POST['prenom']);
        $email = sanitize_input($_POST['email']);
        $telephone = sanitize_input($_POST['telephone']);
        $specialite = sanitize_input($_POST['specialite']);
        $horaire_debut = $_POST['horaire_debut'] ?: '08:00:00';
        $horaire_fin = $_POST['horaire_fin'] ?: '18:00:00';
        $password = $_POST['mot_de_passe'];

        if (empty($email) || empty($password)) {
            $_SESSION['flash_error'] = "L'email et le mot de passe sont obligatoires.";
            redirect('/coiffure_salon/pages/admin/employes.php');
        }

        try {
            // Vérifier si l'email existe
            $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $_SESSION['flash_error'] = "Cet email est déjà utilisé.";
                redirect('/coiffure_salon/pages/admin/employes.php');
            }

            $pdo->beginTransaction();

            // 1. Créer l'utilisateur
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, role, statut) 
                VALUES (:nom, :prenom, :email, :pass, :tel, 'employe', 'actif')
            ");
            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'pass' => $hash,
                'tel' => $telephone
            ]);
            $user_id = $pdo->lastInsertId();

            // 2. Créer le profil employé
            $stmt = $pdo->prepare("
                INSERT INTO employes (id_utilisateur, specialite, horaire_debut, horaire_fin) 
                VALUES (:user_id, :specialite, :debut, :fin)
            ");
            $stmt->execute([
                'user_id' => $user_id,
                'specialite' => $specialite,
                'debut' => $horaire_debut,
                'fin' => $horaire_fin
            ]);

            // 3. Notification pour l'admin
            $stmt = $pdo->prepare("INSERT INTO notifications (id_utilisateur, message, type) VALUES (:admin_id, :msg, 'success')");
            $stmt->execute([
                'admin_id' => $_SESSION['user_id'],
                'msg' => "Nouvelle employée ajoutée : $prenom $nom"
            ]);

            $pdo->commit();
            $_SESSION['flash_success'] = "Compte employé créé avec succès pour $prenom $nom.";
            redirect('/coiffure_salon/pages/admin/employes.php');

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = "Erreur lors de la création : " . $e->getMessage();
            redirect('/coiffure_salon/pages/admin/employes.php');
        }
    }
}
