<?php
require_once dirname(__DIR__) . '/php/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!is_logged_in()) {
    redirect('/coiffure_salon/pages/connexion.php');
}

$user_id = $_SESSION['user_id'];

// Récupérer le contact sélectionné
$contact_id = $_GET['contact'] ?? null;

// Récupérer la liste des conversations (utilisateurs avec qui on a échangé)
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        u.id_utilisateur, u.nom, u.prenom, u.role, u.avatar,
        (SELECT contenu FROM messages 
         WHERE (id_expediteur = :id AND id_destinataire = u.id_utilisateur) 
            OR (id_expediteur = u.id_utilisateur AND id_destinataire = :id)
         ORDER BY date_envoi DESC LIMIT 1) as last_msg,
        (SELECT date_envoi FROM messages 
         WHERE (id_expediteur = :id AND id_destinataire = u.id_utilisateur) 
            OR (id_expediteur = u.id_utilisateur AND id_destinataire = :id)
         ORDER BY date_envoi DESC LIMIT 1) as last_date,
        (SELECT COUNT(*) FROM messages WHERE id_expediteur = u.id_utilisateur AND id_destinataire = :id AND lu = 0) as unread_count
    FROM utilisateurs u
    JOIN messages m ON (m.id_expediteur = u.id_utilisateur AND m.id_destinataire = :id)
                    OR (m.id_expediteur = :id AND m.id_destinataire = u.id_utilisateur)
    WHERE u.id_utilisateur != :id
    ORDER BY last_date DESC
");
$stmt->execute(['id' => $user_id]);
$conversations = $stmt->fetchAll();

// Si un contact est sélectionné, récupérer l'historique complet
$chat_history = [];
$contact_info = null;
if ($contact_id) {
    // Infos du contact
    $stmt = $pdo->prepare("SELECT id_utilisateur, nom, prenom, role, avatar, email FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$contact_id]);
    $contact_info = $stmt->fetch();

    if ($contact_info) {
        // Historique
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (id_expediteur = :me AND id_destinataire = :him)
               OR (id_expediteur = :him AND id_destinataire = :me)
            ORDER BY date_envoi ASC
        ");
        $stmt->execute(['me' => $user_id, 'him' => $contact_id]);
        $chat_history = $stmt->fetchAll();

        // Marquer comme lu
        $stmt = $pdo->prepare("UPDATE messages SET lu = 1 WHERE id_expediteur = :him AND id_destinataire = :me AND lu = 0");
        $stmt->execute(['me' => $user_id, 'him' => $contact_id]);
    }
}

// Liste de tous les utilisateurs pour démarrer une nouvelle discussion
$stmt = $pdo->prepare("SELECT id_utilisateur, nom, prenom, role FROM utilisateurs WHERE id_utilisateur != :id AND statut = 'actif' ORDER BY role, nom");
$stmt->execute(['id' => $user_id]);
$all_users = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container-fluid chat-wrapper px-0">
    <div class="row g-0 h-100 shadow-sm rounded-4 overflow-hidden bg-white mx-md-4 mb-4" style="height: 80vh !important;">
        <!-- Liste des conversations (Sidebar Gauche) -->
        <div class="col-md-4 col-lg-3 border-end h-100 d-flex flex-column bg-light">
            <div class="p-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold playfair">Messages</h5>
                <button class="btn btn-primary btn-sm rounded-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#newUserModal">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            
            <div class="flex-grow-1 overflow-auto bg-white">
                <?php if (empty($conversations)): ?>
                    <div class="p-4 text-center text-muted">
                        <small>Aucune conversation en cours.</small>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($conversations as $conv): ?>
                            <a href="?contact=<?php echo $conv['id_utilisateur']; ?>" class="list-group-item list-group-item-action border-0 py-3 <?php echo $contact_id == $conv['id_utilisateur'] ? 'active-chat' : ''; ?>">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative">
                                        <img src="<?php echo $conv['avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($conv['prenom']).'&background=random'; ?>" class="rounded-circle me-3" width="50" height="50">
                                        <?php if($conv['unread_count'] > 0): ?>
                                            <span class="position-absolute top-0 start-0 badge rounded-pill bg-danger border border-2 border-white" style="font-size: 0.6rem;">
                                                <?php echo $conv['unread_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 fw-bold text-truncate <?php echo $conv['unread_count'] > 0 ? 'text-primary' : ''; ?>">
                                                <?php echo htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']); ?>
                                            </h6>
                                            <small class="text-muted" style="font-size: 0.7rem;"><?php echo date('H:i', strtotime($conv['last_date'])); ?></small>
                                        </div>
                                        <div class="small text-muted text-truncate"><?php echo htmlspecialchars($conv['last_msg']); ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Fenêtre de Chat (Droite) -->
        <div class="col-md-8 col-lg-9 h-100 d-flex flex-column bg-white">
            <?php if ($contact_info): ?>
                <!-- Header du Chat -->
                <div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <img src="<?php echo $contact_info['avatar'] ?: 'https://ui-avatars.com/api/?name='.urlencode($contact_info['prenom']).'&background=random'; ?>" class="rounded-circle me-3" width="40" height="40">
                        <div>
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($contact_info['prenom'] . ' ' . $contact_info['nom']); ?></h6>
                            <small class="text-muted"><?php echo ucfirst($contact_info['role']); ?></small>
                        </div>
                    </div>
                    <div>
                        <button class="btn btn-link text-muted"><i class="fas fa-ellipsis-v"></i></button>
                    </div>
                </div>

                <!-- Zone de messages -->
                <div class="flex-grow-1 overflow-auto p-4 bg-chat" id="chatWindow">
                    <?php 
                    $current_date = '';
                    foreach ($chat_history as $msg): 
                        $msg_date = date('d/m/Y', strtotime($msg['date_envoi']));
                        if ($msg_date !== $current_date):
                            $current_date = $msg_date;
                    ?>
                        <div class="text-center my-4">
                            <span class="badge bg-light text-dark shadow-sm rounded-pill px-3 py-2 small">
                                <?php echo $msg_date === date('d/m/Y') ? "Aujourd'hui" : $msg_date; ?>
                            </span>
                        </div>
                    <?php endif; ?>

                        <div class="d-flex <?php echo $msg['id_expediteur'] == $user_id ? 'justify-content-end' : 'justify-content-start'; ?> mb-3">
                            <div class="message-bubble <?php echo $msg['id_expediteur'] == $user_id ? 'me' : 'them'; ?> shadow-sm">
                                <div class="message-text"><?php echo nl2br(htmlspecialchars($msg['contenu'])); ?></div>
                                <div class="message-time text-end">
                                    <?php echo date('H:i', strtotime($msg['date_envoi'])); ?>
                                    <?php if($msg['id_expediteur'] == $user_id): ?>
                                        <i class="fas fa-check-double ms-1 <?php echo $msg['lu'] ? 'text-primary' : ''; ?>"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Footer / Input -->
                <div class="p-3 border-top bg-white">
                    <form action="/coiffure_salon/php/controllers/message_controller.php?action=send" method="POST" class="d-flex gap-2">
                        <input type="hidden" name="id_destinataire" value="<?php echo $contact_id; ?>">
                        <input type="hidden" name="redirect" value="/coiffure_salon/pages/messages.php?contact=<?php echo $contact_id; ?>">
                        <div class="flex-grow-1 position-relative">
                            <textarea name="contenu" class="form-control rounded-pill border-0 bg-light px-4 py-2" rows="1" placeholder="Écrivez votre message..." required style="resize: none;"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Écran vide -->
                <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted bg-light">
                    <div class="text-center">
                        <div class="mb-4">
                            <i class="fab fa-whatsapp fa-5x text-primary opacity-25"></i>
                        </div>
                        <h4 class="playfair fw-bold text-dark">Messagerie Instantanée</h4>
                        <p class="small px-5">Sélectionnez une conversation pour commencer à discuter ou cliquez sur le bouton "+" pour démarrer une nouvelle discussion.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nouvel Utilisateur -->
<div class="modal fade" id="newUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title playfair fw-bold">Nouvelle discussion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="list-group list-group-flush scroll-y" style="max-height: 400px; overflow-y: auto;">
                    <?php 
                    $last_role = '';
                    foreach ($all_users as $u): 
                        if ($u['role'] !== $last_role):
                            $last_role = $u['role'];
                    ?>
                        <div class="small fw-bold text-primary mt-3 mb-2 border-bottom pb-1"><?php echo ucfirst($u['role']); ?>s</div>
                    <?php endif; ?>
                        <a href="?contact=<?php echo $u['id_utilisateur']; ?>" class="list-group-item list-group-item-action d-flex align-items-center border-0 rounded-3 mb-1">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['prenom']); ?>&background=random" class="rounded-circle me-3" width="35" height="35">
                            <div><?php echo htmlspecialchars($u['prenom'] . ' ' . $u['nom']); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-wrapper {
    height: calc(100vh - 120px);
}
.bg-chat {
    background-color: #e5ddd5;
    background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
    background-repeat: repeat;
}
.active-chat {
    background-color: #f0f2f5 !important;
    border-left: 4px solid var(--primary-color) !important;
}
.message-bubble {
    max-width: 75%;
    padding: 8px 12px;
    border-radius: 12px;
    position: relative;
    font-size: 0.95rem;
}
.message-bubble.me {
    background-color: #dcf8c6;
    border-top-right-radius: 0;
}
.message-bubble.them {
    background-color: #ffffff;
    border-top-left-radius: 0;
}
.message-time {
    font-size: 0.7rem;
    color: #999;
    margin-top: 4px;
}
.scroll-y { overflow-y: auto; }

/* Scrollbar Style */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #999; }
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const chatWindow = document.getElementById('chatWindow');
    if (chatWindow) {
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
