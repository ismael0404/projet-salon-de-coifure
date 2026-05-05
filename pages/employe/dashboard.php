<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('employe');

$user_id = $_SESSION['user_id'];

try {
    // Récupérer les infos de l'employé
    $stmt = $pdo->prepare("SELECT id_employe, specialite FROM employes WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $user_id]);
    $employe = $stmt->fetch();
    $id_employe = $employe['id_employe'];

    // Stats rapides
    // 1. RDV du jour
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rendez_vous WHERE id_employe = :id AND date_rdv = CURDATE() AND statut != 'annule'");
    $stmt->execute(['id' => $id_employe]);
    $rdv_aujourdhui = $stmt->fetchColumn();

    // 2. Chiffre d'affaires de la semaine (Commission possible)
    $stmt = $pdo->prepare("SELECT SUM(prix_total) FROM rendez_vous WHERE id_employe = :id AND WEEK(date_rdv) = WEEK(CURDATE()) AND statut = 'termine'");
    $stmt->execute(['id' => $id_employe]);
    $ca_semaine = $stmt->fetchColumn() ?: 0;

    // Planning du jour
    $stmt = $pdo->prepare("
        SELECT r.*, u.nom, u.prenom, s.nom_service, s.duree
        FROM rendez_vous r
        JOIN clientes c ON r.id_cliente = c.id_cliente
        JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
        JOIN services s ON r.id_service = s.id_service
        WHERE r.id_employe = :id AND r.date_rdv = CURDATE() AND r.statut != 'annule'
        ORDER BY r.heure_rdv ASC
    ");
    $stmt->execute(['id' => $id_employe]);
    $planning = $stmt->fetchAll();

} catch(PDOException $e) {
    die("Erreur de chargement du dashboard");
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-8">
            <h2 class="playfair fw-bold mb-1">Bonjour, <?php echo htmlspecialchars($_SESSION['user_nom']); ?> 👋</h2>
            <p class="text-muted small mb-0">Voici votre planning pour aujourd'hui, <strong><?php echo date('d F Y'); ?></strong></p>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="btn-group shadow-sm">
                <button class="btn btn-white border"><i class="fas fa-print me-2"></i>Imprimer le planning</button>
            </div>
        </div>
    </div>

    <!-- Stats Rapides -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 glass-card h-100">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-0">Rendez-vous aujourd'hui</h6>
                        <h3 class="fw-bold mb-0"><?php echo $rdv_aujourdhui; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 glass-card h-100">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-0">C.A Généré (Semaine)</h6>
                        <h3 class="fw-bold mb-0"><?php echo format_price($ca_semaine); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 glass-card h-100">
                <div class="d-flex align-items-center">
                    <div class="icon-box bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-0">Note Moyenne</h6>
                        <h3 class="fw-bold mb-0">4.9/5</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Planning Détaillé -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="playfair fw-bold mb-4">Mon Planning du Jour</h5>
                <div class="timeline">
                    <?php if(empty($planning)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-coffee fa-3x mb-3 text-muted opacity-25"></i>
                            <p class="text-muted">Aucune prestation prévue pour aujourd'hui.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($planning as $item): ?>
                            <div class="list-group-item px-0 py-3 border-0 mb-3 bg-light rounded-4 px-4 position-relative">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="time-box bg-white shadow-sm rounded-3 p-2 text-center me-4" style="min-width: 70px;">
                                            <div class="fw-bold text-primary"><?php echo date('H:i', strtotime($item['heure_rdv'])); ?></div>
                                            <div class="small text-muted"><?php echo $item['duree']; ?>m</div>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($item['nom_service']); ?></h6>
                                            <p class="mb-0 text-muted small"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($item['prenom'] . ' ' . $item['nom']); ?></p>
                                        </div>
                                    </div>
                                    <div>
                                        <?php if($item['statut'] == 'en_attente'): ?>
                                            <span class="badge badge-soft-warning">En attente</span>
                                        <?php elseif($item['statut'] == 'confirme'): ?>
                                            <span class="badge badge-soft-success">Confirmé</span>
                                        <?php else: ?>
                                            <span class="badge badge-soft-info"><?php echo ucfirst($item['statut']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Rappels / Notes -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="background: linear-gradient(135deg, #d4a373, #b88352); color: white;">
                <h5 class="playfair fw-bold mb-3 text-white">Conseil du Jour</h5>
                <p class="small mb-0 opacity-90">
                    "La ponctualité est la politesse des rois. Un client accueilli avec le sourire et à l'heure est un client qui revient."
                </p>
                <div class="text-end mt-3">
                    <i class="fas fa-quote-right fa-2x opacity-25"></i>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="playfair fw-bold mb-3">Mes Prochaines Semaines</h5>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-alt fa-3x mb-3 text-muted opacity-25"></i>
                    <p class="small text-muted">Votre agenda est rempli à 60% pour la semaine prochaine.</p>
                    <a href="rendezvous.php" class="btn btn-sm btn-outline-primary rounded-pill px-4">Voir tout</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.time-box {
    border-left: 4px solid var(--primary-color);
}
.list-group-item {
    transition: transform 0.2s ease;
}
.list-group-item:hover {
    transform: scale(1.02);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}
</style>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
