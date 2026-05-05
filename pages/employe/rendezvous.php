<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('employe');

$user_id = $_SESSION['user_id'];

try {
    // Récupérer l'ID employé
    $stmt = $pdo->prepare("SELECT id_employe FROM employes WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $user_id]);
    $employe = $stmt->fetch();
    $id_employe = $employe['id_employe'];

    // Récupérer les rendez-vous
    $stmt = $pdo->prepare("
        SELECT r.*, u.nom as client_nom, u.prenom as client_prenom, s.nom_service, s.duree
        FROM rendez_vous r
        JOIN clientes c ON r.id_cliente = c.id_cliente
        JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
        JOIN services s ON r.id_service = s.id_service
        WHERE r.id_employe = :id_emp
        ORDER BY r.date_rdv DESC, r.heure_rdv DESC
    ");
    $stmt->execute(['id_emp' => $id_employe]);
    $rendezvous = $stmt->fetchAll();
} catch(PDOException $e) {
    $rendezvous = [];
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="playfair fw-bold mb-1">Mes Rendez-vous</h2>
            <p class="text-muted small mb-0">Historique complet de vos prestations</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Date & Heure</th>
                        <th>Cliente</th>
                        <th>Service</th>
                        <th>Durée</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($rendezvous)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-3x mb-3 opacity-25"></i>
                                <p>Aucun rendez-vous trouvé.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($rendezvous as $rdv): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo date('d/m/Y', strtotime($rdv['date_rdv'])); ?></div>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($rdv['heure_rdv'])); ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($rdv['client_prenom'] . ' ' . $rdv['client_nom']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($rdv['nom_service']); ?></td>
                                <td><?php echo $rdv['duree']; ?> min</td>
                                <td class="fw-bold text-primary"><?php echo format_price($rdv['prix_total']); ?></td>
                                <td>
                                    <?php 
                                    $status_class = 'badge-soft-secondary';
                                    if($rdv['statut'] == 'confirme') $status_class = 'badge-soft-success';
                                    elseif($rdv['statut'] == 'termine') $status_class = 'badge-soft-info';
                                    elseif($rdv['statut'] == 'annule' || $rdv['statut'] == 'refuse') $status_class = 'badge-soft-danger';
                                    elseif($rdv['statut'] == 'en_attente') $status_class = 'badge-soft-warning';
                                    ?>
                                    <span class="badge <?php echo $status_class; ?> rounded-pill px-3"><?php echo ucfirst($rdv['statut']); ?></span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-check text-success me-2"></i> Terminé</a></li>
                                            <li><a class="dropdown-item" href="#"><i class="fas fa-times text-danger me-2"></i> Annulé</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
