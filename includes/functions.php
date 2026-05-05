<?php
/**
 * Fonctions utilitaires globales pour l'application TYA STYLEX
 */

// Démarrage de la session si ce n'est pas encore fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Génère un jeton CSRF et le stocke en session
 * @return string Jeton CSRF
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité du jeton CSRF
 * @param string $token Jeton à vérifier
 * @return bool True si valide, False sinon
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirige l'utilisateur et termine le script
 * @param string $url URL de redirection
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Vérifie si un utilisateur est connecté
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur possède un rôle spécifique. 
 * Redirige s'il n'a pas les droits.
 * @param string $role Rôle requis ('admin', 'employe', 'client')
 */
function require_role($role) {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = "Veuillez vous connecter pour accéder à cette page.";
        redirect('/coiffure_salon/pages/connexion.php');
    }
    if ($_SESSION['user_role'] !== $role) {
        $_SESSION['flash_error'] = "Accès non autorisé.";
        // Redirection vers le dashboard correspondant à son rôle
        redirect('/coiffure_salon/pages/' . $_SESSION['user_role'] . '/dashboard.php');
    }
}

/**
 * Nettoie les données d'entrée pour prévenir les failles XSS
 * @param string $data Donnée à nettoyer
 * @return string Donnée propre
 */
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Formate un prix en FCFA
 * @param float $prix Prix à formater
 * @return string Prix formaté
 */
function format_price($prix) {
    return number_format($prix, 0, ',', ' ') . ' FCFA';
}
/**
 * Récupère et supprime un message flash de la session
 * @param string $type Type de message ('success', 'error', etc.)
 * @return string Le message ou une chaîne vide
 */
function get_flash_message($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return '';
}
?>
