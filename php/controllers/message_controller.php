<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'send') {
        $destinataire = $_POST['id_destinataire'] ?? null;
        $sujet = sanitize_input($_POST['sujet'] ?? '');
        $contenu = sanitize_input($_POST['contenu'] ?? '');

        if (!$destinataire || empty($contenu)) {
            $_SESSION['flash_error'] = "Veuillez remplir tous les champs.";
            redirect('/coiffure_salon/pages/messages.php');
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO messages (id_expediteur, id_destinataire, sujet, contenu) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $destinataire, $sujet, $contenu]);
            
            // Créer une notification pour le destinataire
            $sender_name = $_SESSION['user_nom'];
            create_notification($destinataire, "Nouveau message de $sender_name", 'info');
            
            $redirect = $_POST['redirect'] ?? '/coiffure_salon/pages/messages.php';
            $_SESSION['flash_success'] = "Message envoyé.";
            redirect($redirect);
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Erreur lors de l'envoi : " . $e->getMessage();
            redirect('/coiffure_salon/pages/messages.php');
        }
    }
} else {
    if ($action === 'list') {
        header('Content-Type: application/json');
        try {
            // Liste des messages reçus
            $stmt = $pdo->prepare("
                SELECT m.*, u.nom, u.prenom, u.role 
                FROM messages m 
                JOIN utilisateurs u ON m.id_expediteur = u.id_utilisateur 
                WHERE m.id_destinataire = ? 
                ORDER BY m.date_envoi DESC
            ");
            $stmt->execute([$user_id]);
            $messages = $stmt->fetchAll();
            echo json_encode($messages);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } elseif ($action === 'mark_read') {
        $msg_id = $_GET['id'] ?? null;
        if ($msg_id) {
            $stmt = $pdo->prepare("UPDATE messages SET lu = 1 WHERE id_message = ? AND id_destinataire = ?");
            $stmt->execute([$msg_id, $user_id]);
        }
        header('Location: /coiffure_salon/pages/messages.php');
    }
}
?>
