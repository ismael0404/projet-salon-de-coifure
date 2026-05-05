<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? 'list';
$user_id = $_SESSION['user_id'];

if ($action === 'list') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id_utilisateur = :id ORDER BY created_at DESC LIMIT 10");
        $stmt->execute(['id' => $user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE id_utilisateur = :id AND is_read = 0");
        $stmt_unread->execute(['id' => $user_id]);
        $unread_count = $stmt_unread->fetchColumn();
        
        header('Content-Type: application/json');
        echo json_encode([
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($action === 'mark_read') {
    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id_utilisateur = :id");
        $stmt->execute(['id' => $user_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
