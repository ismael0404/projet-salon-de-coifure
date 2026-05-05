<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('client');

$user_id = $_SESSION['user_id'];
$id_cliente = 0;
$fidelite = ['niveau_fidelite' => 0, 'nombre_rendezvous' => 0, 'reduction' => 0];

try {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $user_id]);
    $client_data = $stmt->fetch();
    if($client_data) {
        $id_cliente = $client_data['id_cliente'];
        $fidelite = $client_data;
    }
} catch(PDOException $e) {}

// Récupérer le prochain RDV
$prochain_rdv = null;
if($id_cliente > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.date_rdv, r.heure_rdv, r.statut, s.nom_service, s.duree, e_u.nom as emp_nom, e_u.prenom as emp_prenom 
            FROM rendez_vous r 
            JOIN services s ON r.id_service = s.id_service 
            JOIN employes e ON r.id_employe = e.id_employe
            JOIN utilisateurs e_u ON e.id_utilisateur = e_u.id_utilisateur
            WHERE r.id_cliente = :id_client 
            AND (r.date_rdv > CURDATE() OR (r.date_rdv = CURDATE() AND r.heure_rdv >= CURTIME()))
            AND r.statut IN ('en_attente', 'confirme')
            ORDER BY r.date_rdv ASC, r.heure_rdv ASC LIMIT 1
        ");
        $stmt->execute(['id_client' => $id_cliente]);
        $prochain_rdv = $stmt->fetch();
    } catch(PDOException $e) {}
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="playfair mb-0">Bonjour, <?php echo explode(' ', $_SESSION['user_nom'])[0]; ?> !</h2>
        <a href="prendre_rdv.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau RDV</a>
    </div>
    
    <div class="row g-4 mb-4">
        <!-- Prochain Rendez-vous -->
        <div class="col-lg-8">
            <div class="card p-4 h-100 shadow-sm" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white;">
                <h5 class="card-title playfair text-primary mb-4"><i class="fas fa-calendar-alt"></i> Votre prochain rendez-vous</h5>
                
                <?php if($prochain_rdv): ?>
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center border-end border-secondary">
                            <h3 class="display-5 text-primary fw-bold mb-0"><?php echo date('d', strtotime($prochain_rdv['date_rdv'])); ?></h3>
                            <h4 class="mb-0"><?php setlocale(LC_TIME, 'fr_FR.UTF-8'); echo strftime('%B %Y', strtotime($prochain_rdv['date_rdv'])); ?></h4>
                            <p class="text-white-50 mt-2 fs-5"><i class="far fa-clock"></i> <?php echo date('H:i', strtotime($prochain_rdv['heure_rdv'])); ?></p>
                        </div>
                        <div class="col-md-8 ps-md-4 mt-4 mt-md-0">
                            <h4 class="mb-2"><?php echo htmlspecialchars($prochain_rdv['nom_service']); ?></h4>
                            <p class="mb-2 text-white-50"><i class="fas fa-user-tie text-primary"></i> Avec <?php echo htmlspecialchars($prochain_rdv['emp_prenom']); ?></p>
                            <p class="mb-3 text-white-50"><i class="fas fa-hourglass-half text-primary"></i> Durée : <?php echo $prochain_rdv['duree']; ?> min</p>
                            
                            <div class="d-flex gap-2 mt-3">
                                <span class="badge bg-<?php echo $prochain_rdv['statut'] == 'confirme' ? 'success' : 'warning text-dark'; ?> fs-6">
                                    <?php echo ucfirst($prochain_rdv['statut']); ?>
                                </span>
                                <button class="btn btn-outline-light btn-sm">Gérer</button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="far fa-calendar-times fa-3x text-muted mb-3"></i>
                        <p class="mb-3">Vous n'avez aucun rendez-vous à venir.</p>
                        <a href="prendre_rdv.php" class="btn btn-primary">Prendre rendez-vous</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Programme de Fidélité -->
        <div class="col-lg-4">
            <div class="card p-4 h-100 shadow-sm text-center">
                <h5 class="card-title playfair mb-3">Votre fidélité</h5>
                
                <?php 
                $niveau = $fidelite['niveau_fidelite'];
                $couleur = '#6c757d'; // Bronze
                $nom_niveau = 'Bronze';
                if($niveau == 1) { $couleur = '#0d6efd'; $nom_niveau = 'Argent'; } // Argent
                if($niveau >= 2) { $couleur = '#ffc107'; $nom_niveau = 'Or'; } // Or
                ?>
                
                <div class="mb-3">
                    <i class="fas fa-crown fa-4x" style="color: <?php echo $couleur; ?>"></i>
                </div>
                <h4>Niveau <?php echo $nom_niveau; ?></h4>
                <h2 class="text-primary my-3">-<?php echo $fidelite['reduction']; ?>%</h2>
                <p class="text-muted small">Sur toutes vos prochaines prestations</p>
                
                <div class="mt-auto">
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, ($fidelite['nombre_rendezvous'] % 5) * 20); ?>%"></div>
                    </div>
                    <small class="text-muted"><?php echo $fidelite['nombre_rendezvous']; ?> RDV effectués</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
