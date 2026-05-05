<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('admin');

try {
    $stmt = $pdo->query("
        SELECT e.id_employe, u.id_utilisateur, u.nom, u.prenom, u.email, u.telephone, u.avatar,
               e.specialite, e.disponibilite, e.horaire_debut, e.horaire_fin
        FROM employes e 
        JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur
        ORDER BY u.nom
    ");
    $employes = $stmt->fetchAll();
} catch(PDOException $e) {
    $employes = [];
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="playfair fw-bold mb-1">Gestion des Employées</h2>
            <p class="text-muted small mb-0">Gérez votre équipe et leurs horaires</p>
        </div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addEmployeModal">
            <i class="fas fa-plus-circle me-2"></i> Ajouter une Employée
        </button>
    </div>
    
    <div class="row g-4">
        <?php foreach($employes as $emp): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card p-4 shadow-sm border-0 h-100 glass-card">
                <div class="d-flex align-items-center mb-3">
                    <?php 
                    $avatar = !empty($emp['avatar']) ? $emp['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($emp['prenom'].'+'.$emp['nom']).'&background=d4a373&color=fff';
                    ?>
                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="rounded-circle me-3 border border-2 border-primary" style="width:70px; height:70px; object-fit: cover;">
                    <div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($emp['prenom'] . ' ' . $emp['nom']); ?></h5>
                        <span class="badge badge-soft-primary"><?php echo htmlspecialchars($emp['specialite']); ?></span>
                    </div>
                </div>
                
                <div class="bg-light p-3 rounded-3 mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-envelope text-muted me-2 small"></i>
                        <small class="text-truncate"><?php echo htmlspecialchars($emp['email']); ?></small>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-phone text-muted me-2 small"></i>
                        <small><?php echo htmlspecialchars($emp['telephone']); ?></small>
                    </div>
                </div>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="border rounded p-2 text-center bg-white">
                            <div class="text-muted small" style="font-size: 0.7rem;">Début</div>
                            <div class="fw-bold"><?php echo date('H:i', strtotime($emp['horaire_debut'])); ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2 text-center bg-white">
                            <div class="text-muted small" style="font-size: 0.7rem;">Fin</div>
                            <div class="fw-bold"><?php echo date('H:i', strtotime($emp['horaire_fin'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top">
                    <div>
                        <?php if($emp['disponibilite'] == 'disponible'): ?>
                            <span class="badge bg-success rounded-pill px-3">Disponible</span>
                        <?php elseif($emp['disponibilite'] == 'occupe'): ?>
                            <span class="badge bg-warning text-dark rounded-pill px-3">Occupée</span>
                        <?php else: ?>
                            <span class="badge bg-danger rounded-pill px-3"><?php echo ucfirst($emp['disponibilite']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Ajout Employée -->
<div class="modal fade" id="addEmployeModal" tabindex="-1" aria-labelledby="addEmployeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title playfair fw-bold" id="addEmployeModalLabel">Nouvelle Collaboratrice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/coiffure_salon/php/controllers/admin_controller.php?action=add_employe" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nom</label>
                            <input type="text" name="nom" class="form-control" placeholder="Nom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Prénom</label>
                            <input type="text" name="prenom" class="form-control" placeholder="Prénom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email (Identifiant)</label>
                            <input type="email" name="email" class="form-control" placeholder="email@tyastylex.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Téléphone</label>
                            <input type="text" name="telephone" class="form-control" placeholder="07 07 07 07 07">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Spécialité</label>
                            <input type="text" name="specialite" class="form-control" placeholder="ex: Brushing, Coloration, Tresses" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Heure Début</label>
                            <input type="time" name="horaire_debut" class="form-control" value="08:00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Heure Fin</label>
                            <input type="time" name="horaire_fin" class="form-control" value="18:00">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-primary">Mot de passe provisoire</label>
                            <input type="password" name="mot_de_passe" class="form-control" placeholder="Le mot de passe pour sa première connexion" required>
                            <div class="form-text small">L'employée pourra le modifier ultérieurement.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4">Créer le compte</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
