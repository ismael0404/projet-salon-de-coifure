<?php
/**
 * Contrôleur d'Authentification (Auth Controller)
 * 
 * Gère les processus de connexion et d'inscription pour tous les utilisateurs.
 * Inclut la vérification CSRF, la vérification des statuts de compte,
 * la rétrocompatibilité des mots de passe (MD5 vers Bcrypt) et la gestion des sessions.
 * 
 * @package TyaStylex\Controllers
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Redirection si déjà connecté
// Redirection si déjà connecté (sauf si on veut se déconnecter)
$action = $_GET['action'] ?? '';
if (is_logged_in() && $action !== 'logout') {
    redirect('/coiffure_salon/pages/' . $_SESSION['user_role'] . '/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_error'] = "Erreur de sécurité (CSRF). Veuillez réessayer.";
        redirect('javascript:history.back()');
    }

    // ==== TRAITEMENT DE LA CONNEXION ====
    if ($action === 'login') {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';
        
        if (empty($email) || empty($password)) {
            redirect('/coiffure_salon/pages/connexion.php?error=empty_fields');
        }

        try {
            // Requête préparée pour éviter les injections SQL
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Vérification du statut du compte (sécurité d'accès)
                if ($user['statut'] === 'inactif') {
                    redirect('/coiffure_salon/pages/connexion.php?error=account_inactive');
                } elseif ($user['statut'] === 'suspendu') {
                    redirect('/coiffure_salon/pages/connexion.php?error=account_suspended');
                }

                $valid_password = false;
                
                // Vérification du mot de passe (gestion de la transition MD5 vers bcrypt si nécessaire)
                if (strlen($user['mot_de_passe']) === 32 && ctype_xdigit($user['mot_de_passe'])) {
                    // C'est un hash MD5 (ancien système)
                    if (md5($password) === $user['mot_de_passe']) {
                        $valid_password = true;
                        // On met à jour vers bcrypt pour le futur
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = :hash WHERE id_utilisateur = :id");
                        $update->execute(['hash' => $new_hash, 'id' => $user['id_utilisateur']]);
                    }
                } else {
                    // C'est un hash bcrypt classique
                    if (password_verify($password, $user['mot_de_passe'])) {
                        $valid_password = true;
                    }
                }

                if ($valid_password) {
                    // Regénérer l'ID de session pour prévenir la fixation de session
                    session_regenerate_id(true);
                    
                    // Stocker les données en session
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['user_nom'] = $user['prenom'] . ' ' . $user['nom'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_avatar'] = $user['avatar'];

                    // Mettre à jour la date de dernière connexion
                    $update_login = $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id_utilisateur = :id");
                    $update_login->execute(['id' => $user['id_utilisateur']]);

                    // Gestion de "Se souvenir de moi" (cookie 30 jours)
                    if (isset($_POST['remember_me'])) {
                        setcookie('user_email', $email, time() + (86400 * 30), "/");
                    } else {
                        setcookie('user_email', '', time() - 3600, "/");
                    }

                    // Redirection selon le rôle
                    if ($user['role'] === 'admin') {
                        redirect('/coiffure_salon/pages/admin/dashboard.php');
                    } elseif ($user['role'] === 'employe') {
                        redirect('/coiffure_salon/pages/employe/dashboard.php');
                    } else {
                        redirect('/coiffure_salon/pages/client/dashboard.php');
                    }
                } else {
                    redirect('/coiffure_salon/pages/connexion.php?error=invalid_credentials');
                }
            } else {
                redirect('/coiffure_salon/pages/connexion.php?error=invalid_credentials');
            }
        } catch(PDOException $e) {
            redirect('/coiffure_salon/pages/connexion.php?error=system_error');
        }
    }

    // ==== TRAITEMENT DE L'INSCRIPTION ====
    elseif ($action === 'register') {
        $nom = sanitize_input($_POST['nom'] ?? '');
        $prenom = sanitize_input($_POST['prenom'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $telephone = sanitize_input($_POST['telephone'] ?? '');
        $password = $_POST['mot_de_passe'] ?? '';
        $confirm = $_POST['confirme_mot_de_passe'] ?? '';
        $terms = isset($_POST['terms']);

        // Validations
        if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
            redirect('/coiffure_salon/pages/inscription.php?error=empty_fields');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirect('/coiffure_salon/pages/inscription.php?error=invalid_email');
        }
        if (strlen($password) < 6) {
            redirect('/coiffure_salon/pages/inscription.php?error=weak_password');
        }
        if ($password !== $confirm) {
            redirect('/coiffure_salon/pages/inscription.php?error=password_mismatch');
        }
        if (!$terms) {
            redirect('/coiffure_salon/pages/inscription.php?error=terms_required');
        }

        try {
            // 1. Vérifier si l'email existe déjà dans le système
            $check = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = :email");
            $check->execute(['email' => $email]);
            if ($check->fetch()) {
                redirect('/coiffure_salon/pages/inscription.php?error=email_exists');
            }

            // Démarrage de la transaction pour s'assurer que l'utilisateur 
            // et la cliente sont créés simultanément ou pas du tout
            $pdo->beginTransaction();

            // Hashage sécurisé du mot de passe avec Bcrypt
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            // 2. Création de l'utilisateur de base
            $insert = $pdo->prepare("
                INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, role, statut) 
                VALUES (:nom, :prenom, :email, :pass, :tel, 'client', 'actif')
            ");
            $insert->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'pass' => $hash,
                'tel' => !empty($telephone) ? $telephone : null
            ]);
            
            $user_id = $pdo->lastInsertId();
            
            // 3. Création de l'entité cliente spécifique pour le programme de fidélité
            $client = $pdo->prepare("INSERT INTO clientes (id_utilisateur) VALUES (:id)");
            $client->execute(['id' => $user_id]);

            // Validation de la transaction
            $pdo->commit();
            
            $_SESSION['flash_success'] = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
            redirect('/coiffure_salon/pages/connexion.php');
            
        } catch(PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            redirect('/coiffure_salon/pages/inscription.php?error=system_error');
        }
    }
} else {
    // ==== TRAITEMENT DES ACTIONS GET (comme logout) ====
    if ($action === 'logout') {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_success'] = "Vous avez été déconnecté avec succès.";
        redirect('/coiffure_salon/pages/connexion.php');
    }
    
    // Si aucune action GET n'est prévue, retour à l'accueil
    redirect('/coiffure_salon/index.php');
}
