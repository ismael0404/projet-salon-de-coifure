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
$total_depense = 0;
$stats_mensuelles = [];

if($id_cliente > 0) {
    try {
        // 1. Prochain RDV
        $stmt = $pdo->prepare("
            SELECT r.id_rdv, r.date_rdv, r.heure_rdv, r.statut, s.nom_service, s.duree, e_u.nom as emp_nom, e_u.prenom as emp_prenom 
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

        // 2. Total dépensé (uniquement les RDV terminés)
        $stmt = $pdo->prepare("SELECT SUM(prix_total) FROM rendez_vous WHERE id_cliente = ? AND statut = 'termine'");
        $stmt->execute([$id_cliente]);
        $total_depense = $stmt->fetchColumn() ?: 0;

        // 3. Données pour le graphique (6 derniers mois)
        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(date_rdv, '%b') as mois, COUNT(*) as nb 
            FROM rendez_vous 
            WHERE id_cliente = ? AND statut = 'termine' 
            AND date_rdv >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(date_rdv, '%Y-%m')
            ORDER BY date_rdv ASC
        ");
        $stmt->execute([$id_cliente]);
        $stats_mensuelles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {}
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="playfair mb-0">Bonjour, <?php echo explode(' ', $_SESSION['user_nom'])[0]; ?> !</h2>
        <a href="prendre_rdv.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nouveau RDV</a>
    </div>

    <!-- Statistiques Clés -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0 text-center glass-effect">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="icon-box bg-primary-light text-primary me-3">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="text-start">
                        <h6 class="text-muted mb-0">Total Rendez-vous</h6>
                        <h4 class="fw-bold mb-0"><?php echo $fidelite['nombre_rendezvous']; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0 text-center glass-effect">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="icon-box bg-success-light text-success me-3">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="text-start">
                        <h6 class="text-muted mb-0">Total Dépensé</h6>
                        <h4 class="fw-bold mb-0"><?php echo format_price($total_depense); ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 shadow-sm border-0 text-center glass-effect">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="icon-box bg-warning-light text-warning me-3">
                        <i class="fas fa-percent"></i>
                    </div>
                    <div class="text-start">
                        <h6 class="text-muted mb-0">Ma Réduction</h6>
                        <h4 class="fw-bold mb-0">-<?php echo $fidelite['reduction']; ?>%</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-4">
        <!-- Prochain Rendez-vous -->
        <div class="col-lg-8">
            <div class="card p-4 h-100 shadow-sm" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: white; border-radius: 20px;">
                <h5 class="card-title playfair text-primary mb-4"><i class="fas fa-calendar-alt"></i> Votre prochain rendez-vous</h5>
                
                <?php if($prochain_rdv): ?>
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center border-end border-secondary">
                            <h3 class="display-5 text-primary fw-bold mb-0"><?php echo date('d', strtotime($prochain_rdv['date_rdv'])); ?></h3>
                            <h4 class="mb-0 text-uppercase" style="font-size: 0.9rem; letter-spacing: 2px;">
                                <?php 
                                    $mois_fr = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];
                                    $timestamp = strtotime($prochain_rdv['date_rdv']);
                                    $mois = $mois_fr[date('n', $timestamp) - 1];
                                    echo $mois . " " . date('Y', $timestamp);
                                ?>
                            </h4>
                            <p class="text-white-50 mt-2 fs-5"><i class="far fa-clock"></i> <?php echo date('H:i', strtotime($prochain_rdv['heure_rdv'])); ?></p>
                        </div>
                        <div class="col-md-8 ps-md-4 mt-4 mt-md-0">
                            <h4 class="mb-2"><?php echo htmlspecialchars($prochain_rdv['nom_service']); ?></h4>
                            <p class="mb-2 text-white-50"><i class="fas fa-user-tie text-primary"></i> Avec <?php echo htmlspecialchars($prochain_rdv['emp_prenom']); ?></p>
                            <p class="mb-3 text-white-50"><i class="fas fa-hourglass-half text-primary"></i> Durée : <?php echo $prochain_rdv['duree']; ?> min</p>
                            
                            <div class="d-flex gap-2 mt-3">
                                <span class="badge bg-<?php echo $prochain_rdv['statut'] == 'confirme' ? 'success' : 'warning text-dark'; ?> fs-6 py-2 px-3">
                                    <i class="fas <?php echo $prochain_rdv['statut'] == 'confirme' ? 'fa-check-circle' : 'fa-hourglass-start'; ?> me-1"></i>
                                    <?php echo ucfirst($prochain_rdv['statut']); ?>
                                </span>
                                <?php if($prochain_rdv['statut'] == 'confirme'): ?>
                                    <a href="facture.php?id=<?php echo $prochain_rdv['id_rdv']; ?>" target="_blank" class="btn btn-outline-light btn-sm px-3"><i class="fas fa-file-pdf me-1"></i> Reçu</a>
                                <?php endif; ?>
                                <button class="btn btn-outline-light btn-sm px-3" onclick="gererRDV(<?php echo $prochain_rdv['id_rdv']; ?>, '<?php echo $prochain_rdv['date_rdv']; ?>', '<?php echo $prochain_rdv['heure_rdv']; ?>')">Gérer</button>
                            </div>
                        </div>
                    </div>

                    <script>
                    function gererRDV(id, date, heure) {
                        Swal.fire({
                            title: 'Gérer mon rendez-vous',
                            text: "Que souhaitez-vous faire ?",
                            icon: 'question',
                            showCancelButton: true,
                            showDenyButton: true,
                            confirmButtonText: '<i class="fas fa-edit"></i> Modifier',
                            denyButtonText: '<i class="fas fa-times"></i> Annuler',
                            cancelButtonText: 'Retour',
                            confirmButtonColor: '#d4a373',
                            denyButtonColor: '#dc3545',
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Modifier
                                Swal.fire({
                                    title: 'Nouvel horaire',
                                    html: `
                                        <input type="date" id="new_date" class="swal2-input" value="${date}" min="<?php echo date('Y-m-d'); ?>">
                                        <input type="time" id="new_time" class="swal2-input" value="${heure.substring(0,5)}">
                                    `,
                                    showCancelButton: true,
                                    confirmButtonText: 'Enregistrer',
                                    preConfirm: () => {
                                        return {
                                            date: document.getElementById('new_date').value,
                                            time: document.getElementById('new_time').value
                                        }
                                    }
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = '../../php/controllers/rdv_controller.php?action=update';
                                        form.innerHTML = `
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="id_rdv" value="${id}">
                                            <input type="hidden" name="date_rdv" value="${res.value.date}">
                                            <input type="hidden" name="heure_rdv" value="${res.value.time}">
                                        `;
                                        document.body.appendChild(form);
                                        form.submit();
                                    }
                                });
                            } else if (result.isDenied) {
                                // Annuler
                                Swal.fire({
                                    title: 'Êtes-vous sûr ?',
                                    text: "Cette action est irréversible !",
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#dc3545',
                                    confirmButtonText: 'Oui, annuler !'
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = '../../php/controllers/rdv_controller.php?action=cancel';
                                        form.innerHTML = `
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="id_rdv" value="${id}">
                                        `;
                                        document.body.appendChild(form);
                                        form.submit();
                                    }
                                });
                            }
                        });
                    }
                    </script>
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
            <div class="card p-4 h-100 shadow-sm text-center border-0" style="border-radius: 20px;">
                <h5 class="card-title playfair mb-3">Votre fidélité</h5>
                
                <?php 
                $niveau = $fidelite['niveau_fidelite'];
                $couleur = '#cd7f32'; // Bronze
                $nom_niveau = 'Bronze';
                if($niveau == 1) { $couleur = '#c0c0c0'; $nom_niveau = 'Argent'; } // Argent
                if($niveau >= 2) { $couleur = '#ffd700'; $nom_niveau = 'Or'; } // Or
                ?>
                
                <div class="mb-3 position-relative">
                    <i class="fas fa-crown fa-5x" style="color: <?php echo $couleur; ?>; filter: drop-shadow(0 0 10px <?php echo $couleur; ?>44);"></i>
                </div>
                <h4>Niveau <?php echo $nom_niveau; ?></h4>
                <h2 class="text-primary my-3 fw-bold">-<?php echo $fidelite['reduction']; ?>%</h2>
                <p class="text-muted small">Réduction appliquée automatiquement sur vos soins</p>
                
                <div class="mt-auto">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-muted">Progression</span>
                        <span class="small fw-bold text-primary"><?php echo $fidelite['nombre_rendezvous']; ?> RDV</span>
                    </div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, ($fidelite['nombre_rendezvous'] % 5) * 20); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique de Fréquentation -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card p-4 shadow-sm border-0" style="border-radius: 20px;">
                <h5 class="card-title playfair mb-4"><i class="fas fa-chart-line text-primary me-2"></i> Ma fréquence de soins</h5>
                <div style="height: 300px;">
                    <canvas id="frequenceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.icon-box {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.bg-primary-light { background: rgba(212, 163, 115, 0.1); }
.bg-success-light { background: rgba(25, 135, 84, 0.1); }
.bg-warning-light { background: rgba(255, 193, 7, 0.1); }
.glass-effect {
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('frequenceChart').getContext('2d');
    
    // Données PHP vers JS
    const labels = <?php echo json_encode(array_column($stats_mensuelles, 'mois')); ?>;
    const data = <?php echo json_encode(array_column($stats_mensuelles, 'nb')); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length ? labels : ['Jan', 'Féb', 'Mar', 'Avr', 'Mai', 'Juin'],
            datasets: [{
                label: 'Nombre de soins',
                data: data.length ? data : [0, 0, 0, 0, 0, 0],
                borderColor: '#d4a373',
                backgroundColor: 'rgba(212, 163, 115, 0.2)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#d4a373',
                pointBorderColor: '#fff',
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
});
</script>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
