<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('admin');

// Traitement du changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $id_rdv = (int)$_POST['id_rdv'];
        $nouveau_statut = sanitize_input($_POST['statut']);
        
        try {
            $pdo->beginTransaction();
            
            // Mise à jour du statut
            $stmt = $pdo->prepare("UPDATE rendez_vous SET statut = ? WHERE id_rdv = ?");
            $stmt->execute([$nouveau_statut, $id_rdv]);
            
            // Si le statut est "termine", mettre à jour la fidélité de la cliente
            if ($nouveau_statut === 'termine') {
                $stmt = $pdo->prepare("SELECT id_cliente FROM rendez_vous WHERE id_rdv = ?");
                $stmt->execute([$id_rdv]);
                $cliente = $stmt->fetch();
                
                if ($cliente) {
                    // Appeler la procédure stockée update_niveau_fidelite
                    $stmt = $pdo->prepare("CALL update_niveau_fidelite(?)");
                    $stmt->execute([$cliente['id_cliente']]);
                }
            }
            
            
            // Notifications lors de la confirmation
            if ($nouveau_statut === 'confirme') {
                $stmt = $pdo->prepare("SELECT r.date_rdv, r.heure_rdv, c.id_utilisateur as id_client_user, e.id_utilisateur as id_emp_user 
                                     FROM rendez_vous r 
                                     JOIN clientes c ON r.id_cliente = c.id_cliente 
                                     JOIN employes e ON r.id_employe = e.id_employe 
                                     WHERE r.id_rdv = ?");
                $stmt->execute([$id_rdv]);
                $info = $stmt->fetch();
                
                if ($info) {
                    $date_f = date('d/m', strtotime($info['date_rdv']));
                    $heure_f = date('H:i', strtotime($info['heure_rdv']));
                    
                    // Notifier la cliente
                    create_notification($info['id_client_user'], "Bonne nouvelle ! Votre RDV du $date_f à $heure_f est confirmé.", 'success');
                    
                    // Notifier l'employée
                    create_notification($info['id_emp_user'], "Confirmation : Le RDV du $date_f à $heure_f est confirmé.", 'info');
                }
            }
            
            $pdo->commit();
            $_SESSION['flash_success'] = "Statut mis à jour avec succès.";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = "Erreur lors de la mise à jour.";
        }
        redirect('rendezvous.php');
    }
}

// Filtres
$filtre_statut = $_GET['statut'] ?? 'tous';
$where_clause = "";
$params = [];

if ($filtre_statut !== 'tous') {
    $where_clause = "WHERE r.statut = :statut";
    $params['statut'] = $filtre_statut;
}

try {
    $sql = "SELECT r.id_rdv, r.date_rdv, r.heure_rdv, r.statut, r.prix_total, 
                   s.nom_service, c_u.prenom as client_prenom, c_u.nom as client_nom,
                   e_u.prenom as emp_prenom
            FROM rendez_vous r 
            JOIN clientes c ON r.id_cliente = c.id_cliente 
            JOIN utilisateurs c_u ON c.id_utilisateur = c_u.id_utilisateur
            JOIN services s ON r.id_service = s.id_service 
            JOIN employes e ON r.id_employe = e.id_employe
            JOIN utilisateurs e_u ON e.id_utilisateur = e_u.id_utilisateur
            $where_clause
            ORDER BY r.date_rdv DESC, r.heure_rdv DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rdvs = $stmt->fetchAll();
} catch(PDOException $e) {
    $rdvs = [];
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="playfair mb-0">Gestion des Rendez-vous</h2>
        
        <div class="btn-group">
            <a href="?statut=tous" class="btn btn-outline-secondary <?php echo $filtre_statut == 'tous' ? 'active' : ''; ?>">Tous</a>
            <a href="?statut=en_attente" class="btn btn-outline-warning <?php echo $filtre_statut == 'en_attente' ? 'active' : ''; ?>">En attente</a>
            <a href="?statut=confirme" class="btn btn-outline-primary <?php echo $filtre_statut == 'confirme' ? 'active' : ''; ?>">Confirmés</a>
            <a href="?statut=termine" class="btn btn-outline-success <?php echo $filtre_statut == 'termine' ? 'active' : ''; ?>">Terminés</a>
        </div>
    </div>
    
    <div class="card p-4 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date & Heure</th>
                        <th>Cliente</th>
                        <th>Prestation</th>
                        <th>Coiffeuse</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rdvs as $rdv): 
                        $badge_class = 'bg-secondary';
                        if($rdv['statut'] == 'confirme') $badge_class = 'bg-primary';
                        if($rdv['statut'] == 'termine') $badge_class = 'bg-success';
                        if($rdv['statut'] == 'annule') $badge_class = 'bg-danger';
                        if($rdv['statut'] == 'en_attente') $badge_class = 'bg-warning text-dark';
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo date('d/m/Y', strtotime($rdv['date_rdv'])); ?></strong><br>
                            <small class="text-muted"><i class="far fa-clock"></i> <?php echo date('H:i', strtotime($rdv['heure_rdv'])); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($rdv['client_prenom'] . ' ' . $rdv['client_nom']); ?></td>
                        <td><?php echo htmlspecialchars($rdv['nom_service']); ?></td>
                        <td><?php echo htmlspecialchars($rdv['emp_prenom']); ?></td>
                        <td class="fw-bold text-primary"><?php echo format_price($rdv['prix_total']); ?></td>
                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($rdv['statut']); ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Gérer
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <form method="POST" action="">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id_rdv" value="<?php echo $rdv['id_rdv']; ?>">
                                        <input type="hidden" name="statut" value="confirme">
                                        <button type="submit" class="dropdown-item text-primary"><i class="fas fa-check"></i> Confirmer</button>
                                    </form>
                                </li>
                                <li>
                                    <form method="POST" action="">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id_rdv" value="<?php echo $rdv['id_rdv']; ?>">
                                        <input type="hidden" name="statut" value="termine">
                                        <button type="submit" class="dropdown-item text-success"><i class="fas fa-flag-checkered"></i> Terminer</button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id_rdv" value="<?php echo $rdv['id_rdv']; ?>">
                                        <input type="hidden" name="statut" value="annule">
                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Annuler ce rendez-vous ?')"><i class="fas fa-times"></i> Annuler</button>
                                    </form>
                                </li>
                            </ul>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
