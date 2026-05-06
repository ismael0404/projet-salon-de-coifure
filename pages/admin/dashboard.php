<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Vérification du rôle
require_role('admin');

// Récupération des statistiques rapides
try {
    $stats = [];
    
    // Nombre de clients
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'client'");
    $stats['clients'] = $stmt->fetchColumn();
    
    // Nombre d'employés
    $stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'employe'");
    $stats['employes'] = $stmt->fetchColumn();
    
    // Rendez-vous du jour
    $stmt = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE date_rdv = CURDATE()");
    $stats['rdv_jour'] = $stmt->fetchColumn();
    
    // Chiffre d'affaires du mois
    $stmt = $pdo->query("SELECT SUM(prix_total) FROM rendez_vous WHERE MONTH(date_rdv) = MONTH(CURDATE()) AND YEAR(date_rdv) = YEAR(CURDATE()) AND statut = 'termine'");
    $ca = $stmt->fetchColumn();
    $stats['ca_mois'] = $ca ? $ca : 0;

    // Données pour le graphique (6 derniers mois)
    $months = [];
    $revenues = [];
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-$i months"));
        $label = date('M', strtotime("-$i months"));
        $months[] = $label;
        
        $stmt = $pdo->prepare("SELECT SUM(prix_total) FROM rendez_vous WHERE DATE_FORMAT(date_rdv, '%Y-%m') = :month AND statut = 'termine'");
        $stmt->execute(['month' => $m]);
        $val = $stmt->fetchColumn();
        $revenues[] = $val ? $val : 0;
    }
    
    // Répartition des services
    $stmt = $pdo->query("SELECT s.nom_service, COUNT(r.id_rdv) as count 
                         FROM services s 
                         LEFT JOIN rendez_vous r ON s.id_service = r.id_service 
                         GROUP BY s.id_service 
                         ORDER BY count DESC LIMIT 5");
    $service_labels = [];
    $service_counts = [];
    while($row = $stmt->fetch()) {
        $service_labels[] = $row['nom_service'];
        $service_counts[] = $row['count'];
    }

} catch(PDOException $e) {
    $stats = ['clients' => 0, 'employes' => 0, 'rdv_jour' => 0, 'ca_mois' => 0];
    $months = [];
    $revenues = [];
    $service_labels = [];
    $service_counts = [];
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="playfair fw-bold mb-1">Tableau de Bord Décisionnel</h2>
            <p class="text-muted small mb-0">Analyse de l'activité du salon en temps réel</p>
        </div>
        <div class="text-end">
            <button class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-download"></i> Rapport PDF</button>
            <span class="badge bg-white text-dark border p-2"><i class="fas fa-calendar-alt text-primary me-2"></i> <?php echo date('d F Y'); ?></span>
        </div>
    </div>
    
    <!-- Cartes de statistiques modernes -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary">
                <div class="icon-box"><i class="fas fa-users fa-lg"></i></div>
                <h6>Total Clientes</h6>
                <h2><?php echo number_format($stats['clients']); ?></h2>
                <div class="mt-2 small opacity-75"><i class="fas fa-arrow-up"></i> +12% ce mois</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: #27ae60;">
                <div class="icon-box"><i class="fas fa-coins fa-lg"></i></div>
                <h6>C.A du Mois</h6>
                <h2><?php echo format_price($stats['ca_mois']); ?></h2>
                <div class="mt-2 small opacity-75"><i class="fas fa-arrow-up"></i> +5% vs mois dernier</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: #2980b9;">
                <div class="icon-box"><i class="fas fa-calendar-check fa-lg"></i></div>
                <h6>RDV Aujourd'hui</h6>
                <h2><?php echo $stats['rdv_jour']; ?></h2>
                <div class="mt-2 small opacity-75">Taux d'occupation: 85%</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: #e67e22;">
                <div class="icon-box"><i class="fas fa-user-tie fa-lg"></i></div>
                <h6>Staff Actif</h6>
                <h2><?php echo $stats['employes']; ?></h2>
                <div class="mt-2 small opacity-75">1 en congé aujourd'hui</div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        <!-- Graphique d'activité principal -->
        <div class="col-lg-8">
            <div class="card p-4 h-100 border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title playfair fw-bold mb-0">Évolution de la Performance</h5>
                    <select class="form-select form-select-sm w-auto">
                        <option>6 derniers mois</option>
                        <option>Cette année</option>
                    </select>
                </div>
                <div class="chart-container" style="position: relative; height: 350px;">
                    <canvas id="mainChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Répartition par Service -->
        <div class="col-lg-4">
            <div class="card p-4 h-100 border-0 shadow-sm">
                <h5 class="card-title playfair fw-bold mb-4">Popularité des Services</h5>
                <div class="chart-container" style="position: relative; height: 250px;">
                    <canvas id="serviceChart"></canvas>
                </div>
                <div class="mt-4">
                    <?php 
                    $colors = ['#d4a373', '#2c3e50', '#27ae60', '#2980b9', '#e67e22'];
                    for($i = 0; $i < count($service_labels); $i++): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small><i class="fas fa-circle me-2" style="color: <?php echo $colors[$i]; ?>;"></i> <?php echo $service_labels[$i]; ?></small>
                        <span class="fw-bold"><?php echo $service_counts[$i]; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Derniers RDV et Activité -->
        <div class="col-lg-6">
            <div class="card p-4 border-0 shadow-sm">
                <h5 class="card-title playfair fw-bold mb-3">Dernières Réservations</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT r.id_rdv, r.date_rdv, r.statut, u.nom, u.prenom, s.nom_service 
                                                     FROM rendez_vous r 
                                                     JOIN clientes c ON r.id_cliente = c.id_cliente 
                                                     JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur 
                                                     JOIN services s ON r.id_service = s.id_service 
                                                     ORDER BY r.date_reservation DESC LIMIT 5");
                                while($row = $stmt->fetch()) {
                                    $status_class = '';
                                    if($row['statut'] == 'confirme') $status_class = 'badge-soft-success';
                                    elseif($row['statut'] == 'en_attente') $status_class = 'badge-soft-warning';
                                    elseif($row['statut'] == 'annule') $status_class = 'badge-soft-danger';
                                    
                                    echo '<tr>';
                                    echo '<td><div class="fw-bold">' . htmlspecialchars($row['prenom']) . '</div></td>';
                                    echo '<td>' . htmlspecialchars($row['nom_service']) . '</td>';
                                    echo '<td>' . date('d/m', strtotime($row['date_rdv'])) . '</td>';
                                    echo '<td><span class="badge ' . $status_class . '">' . ucfirst($row['statut']) . '</span></td>';
                                    echo '</tr>';
                                }
                            } catch(PDOException $e) {}
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Performance Employées -->
        <div class="col-lg-6">
            <div class="card p-4 border-0 shadow-sm h-100">
                <h5 class="card-title playfair fw-bold mb-3">Performance Staff</h5>
                <div class="list-group list-group-flush">
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT u.nom, u.prenom, COUNT(r.id_rdv) as total_rdv, SUM(r.prix_total) as total_ca
                                             FROM employes e
                                             JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
                                             LEFT JOIN rendez_vous r ON e.id_employe = r.id_employe AND r.statut = 'termine'
                                             GROUP BY e.id_employe
                                             ORDER BY total_ca DESC");
                        while($row = $stmt->fetch()) {
                            echo '<div class="list-group-item d-flex justify-content-between align-items-center px-0">';
                            echo '<div>';
                            echo '<h6 class="mb-0 fw-bold">' . htmlspecialchars($row['prenom'] . ' ' . $row['nom']) . '</h6>';
                            echo '<small class="text-muted">' . $row['total_rdv'] . ' prestations terminées</small>';
                            echo '</div>';
                            echo '<div class="text-end">';
                            echo '<div class="fw-bold text-primary">' . format_price($row['total_ca'] ? $row['total_ca'] : 0) . '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } catch(PDOException $e) {}
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Graphique Principal (Courbe de revenus)
    const ctxMain = document.getElementById('mainChart').getContext('2d');
    new Chart(ctxMain, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Chiffre d\'Affaires (FCFA)',
                data: <?php echo json_encode($revenues); ?>,
                borderColor: '#d4a373',
                backgroundColor: 'rgba(212, 163, 115, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#d4a373',
                pointBorderColor: '#fff',
                pointHoverRadius: 6
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
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Graphique Services (Doughnut)
    const ctxService = document.getElementById('serviceChart').getContext('2d');
    new Chart(ctxService, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($service_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($service_counts); ?>,
                backgroundColor: ['#d4a373', '#2c3e50', '#27ae60', '#2980b9', '#e67e22'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            cutout: '70%'
        }
    });
});
</script>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
