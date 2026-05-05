<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('admin');

// Traitement de l'ajout / modification (simplifié)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $nom = sanitize_input($_POST['nom_service']);
        $desc = sanitize_input($_POST['description']);
        $duree = (int)$_POST['duree'];
        $prix = (float)$_POST['prix'];
        $cat = sanitize_input($_POST['categorie']);
        
        if ($_POST['action'] === 'add') {
            try {
                $stmt = $pdo->prepare("INSERT INTO services (nom_service, description, duree, prix_standard, categorie, statut) VALUES (?, ?, ?, ?, ?, 'actif')");
                $stmt->execute([$nom, $desc, $duree, $prix, $cat]);
                $_SESSION['flash_success'] = "Prestation ajoutée avec succès.";
            } catch(PDOException $e) {
                $_SESSION['flash_error'] = "Erreur lors de l'ajout.";
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)$_POST['id_service'];
            try {
                $stmt = $pdo->prepare("UPDATE services SET nom_service=?, description=?, duree=?, prix_standard=?, categorie=? WHERE id_service=?");
                $stmt->execute([$nom, $desc, $duree, $prix, $cat, $id]);
                $_SESSION['flash_success'] = "Prestation modifiée avec succès.";
            } catch(PDOException $e) {
                $_SESSION['flash_error'] = "Erreur lors de la modification.";
            }
        }
        redirect('services.php');
    }
}

// Désactivation
if (isset($_GET['disable']) && verify_csrf_token($_GET['token'] ?? '')) {
    $id = (int)$_GET['disable'];
    $pdo->query("UPDATE services SET statut = 'inactif' WHERE id_service = $id");
    $_SESSION['flash_success'] = "Prestation désactivée.";
    redirect('services.php');
}

// Réactivation
if (isset($_GET['enable']) && verify_csrf_token($_GET['token'] ?? '')) {
    $id = (int)$_GET['enable'];
    $pdo->query("UPDATE services SET statut = 'actif' WHERE id_service = $id");
    $_SESSION['flash_success'] = "Prestation réactivée.";
    redirect('services.php');
}

$services = $pdo->query("SELECT * FROM services ORDER BY categorie, nom_service")->fetchAll();

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="playfair mb-0">Gestion des Prestations</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="fas fa-plus"></i> Nouvelle Prestation
        </button>
    </div>
    
    <div class="card p-4 shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Catégorie</th>
                        <th>Nom de la prestation</th>
                        <th>Durée</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($services as $srv): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($srv['categorie']); ?></span></td>
                        <td>
                            <strong><?php echo htmlspecialchars($srv['nom_service']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars(substr($srv['description'], 0, 50)); ?>...</small>
                        </td>
                        <td><i class="far fa-clock text-muted"></i> <?php echo $srv['duree']; ?> min</td>
                        <td class="fw-bold text-primary"><?php echo format_price($srv['prix_standard']); ?></td>
                        <td>
                            <?php if($srv['statut'] == 'actif'): ?>
                                <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="editService(<?php echo htmlspecialchars(json_encode($srv)); ?>)"><i class="fas fa-edit"></i></button>
                            <?php if($srv['statut'] == 'actif'): ?>
                                <a href="?disable=<?php echo $srv['id_service']; ?>&token=<?php echo generate_csrf_token(); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Désactiver cette prestation ?')"><i class="fas fa-ban"></i></a>
                            <?php else: ?>
                                <a href="?enable=<?php echo $srv['id_service']; ?>&token=<?php echo generate_csrf_token(); ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ajout/Modif -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Nouvelle Prestation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id_service" id="formId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Nom de la prestation *</label>
                        <input type="text" class="form-control" name="nom_service" id="formNom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catégorie *</label>
                        <input type="text" class="form-control" name="categorie" id="formCat" required placeholder="ex: Coupe, Soins, Tresses...">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prix (FCFA) *</label>
                            <input type="number" step="100" class="form-control" name="prix" id="formPrix" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Durée (minutes) *</label>
                            <input type="number" step="5" class="form-control" name="duree" id="formDuree" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="formDesc" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editService(srv) {
    document.getElementById('modalTitle').textContent = "Modifier Prestation";
    document.getElementById('formAction').value = "edit";
    document.getElementById('formId').value = srv.id_service;
    document.getElementById('formNom').value = srv.nom_service;
    document.getElementById('formCat').value = srv.categorie;
    document.getElementById('formPrix').value = srv.prix_standard;
    document.getElementById('formDuree').value = srv.duree;
    document.getElementById('formDesc').value = srv.description;
    
    new bootstrap.Modal(document.getElementById('addServiceModal')).show();
}
</script>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
