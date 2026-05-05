<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('admin');

try {
    $stmt = $pdo->query("
        SELECT c.id_cliente, u.nom, u.prenom, u.email, u.telephone, u.date_inscription,
               c.nombre_rendezvous, c.niveau_fidelite, c.reduction
        FROM clientes c 
        JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
        ORDER BY u.date_inscription DESC
    ");
    $clientes = $stmt->fetchAll();
} catch(PDOException $e) {
    $clientes = [];
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="playfair mb-0">Base de Clientes</h2>
        <div class="input-group" style="width: 300px;">
            <input type="text" class="form-control" placeholder="Rechercher...">
            <button class="btn btn-primary" type="button"><i class="fas fa-search"></i></button>
        </div>
    </div>
    
    <div class="card p-4 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Cliente</th>
                        <th>Contact</th>
                        <th>Date d'inscription</th>
                        <th>RDV Effectués</th>
                        <th>Fidélité</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($clientes as $cli): 
                        $niveau = $cli['niveau_fidelite'];
                        $couleur = '#6c757d'; // Bronze
                        $nom_niveau = 'Bronze';
                        if($niveau == 1) { $couleur = '#0d6efd'; $nom_niveau = 'Argent'; }
                        if($niveau >= 2) { $couleur = '#ffc107'; $nom_niveau = 'Or'; }
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width:40px; height:40px">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($cli['prenom'] . ' ' . $cli['nom']); ?></strong>
                                </div>
                            </div>
                        </td>
                        <td>
                            <small class="d-block"><i class="fas fa-envelope text-muted"></i> <?php echo htmlspecialchars($cli['email']); ?></small>
                            <small class="d-block"><i class="fas fa-phone text-muted"></i> <?php echo htmlspecialchars($cli['telephone']); ?></small>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($cli['date_inscription'])); ?></td>
                        <td><span class="badge bg-secondary rounded-pill px-3"><?php echo $cli['nombre_rendezvous']; ?></span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-crown" style="color: <?php echo $couleur; ?>"></i>
                                <span><?php echo $nom_niveau; ?></span>
                                <?php if($cli['reduction'] > 0): ?>
                                    <span class="badge bg-success small">-<?php echo $cli['reduction']; ?>%</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" title="Voir l'historique"><i class="fas fa-history"></i></button>
                            <button class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="fas fa-edit"></i></button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
