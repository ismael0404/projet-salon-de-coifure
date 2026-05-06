<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('admin');

$user_id = $_SESSION['user_id'];

// Récupérer les infos de l'admin
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
$stmt->execute([$user_id]);
$admin = $stmt->fetch();

// Récupérer les paramètres du site
$stmt = $pdo->query("SELECT cle, valeur FROM parametres_site");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Retourne [cle => valeur]

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nom = sanitize_input($_POST['nom']);
        $prenom = sanitize_input($_POST['prenom']);
        $email = sanitize_input($_POST['email']);
        $telephone = sanitize_input($_POST['telephone']);

        try {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, telephone = ? WHERE id_utilisateur = ?");
            $stmt->execute([$nom, $prenom, $email, $telephone, $user_id]);
            
            $_SESSION['user_nom'] = $prenom . ' ' . $nom;
            $_SESSION['flash_success'] = "Profil mis à jour avec succès.";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Erreur lors de la mise à jour.";
        }
        redirect('profil.php');
    }

    if ($action === 'update_site') {
        try {
            $pdo->beginTransaction();
            foreach ($_POST['settings'] as $cle => $valeur) {
                $stmt = $pdo->prepare("UPDATE parametres_site SET valeur = ? WHERE cle = ?");
                $stmt->execute([sanitize_input($valeur), $cle]);
            }
            $pdo->commit();
            $_SESSION['flash_success'] = "Paramètres du site mis à jour.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['flash_error'] = "Erreur lors de la mise à jour des paramètres.";
        }
        redirect('profil.php');
    }
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <h2 class="playfair fw-bold mb-4">Profil & Paramètres</h2>

    <div class="row g-4">
        <!-- Profil Personnel -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user-circle text-primary me-2"></i> Mes Informations Personnelles</h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Prénom</label>
                                <input type="text" name="prenom" class="form-control" value="<?php echo htmlspecialchars($admin['prenom']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nom</label>
                                <input type="text" name="nom" class="form-control" value="<?php echo htmlspecialchars($admin['nom']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Téléphone</label>
                                <input type="text" name="telephone" class="form-control" value="<?php echo htmlspecialchars($admin['telephone']); ?>">
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary w-100 rounded-pill">Enregistrer les modifications</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Paramètres du Site -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-cogs text-primary me-2"></i> Informations du Salon</h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="update_site">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Nom du Salon</label>
                                <input type="text" name="settings[site_nom]" class="form-control" value="<?php echo htmlspecialchars($settings['site_nom']); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Description</label>
                                <input type="text" name="settings[site_description]" class="form-control" value="<?php echo htmlspecialchars($settings['site_description']); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Adresse Physique</label>
                                <textarea name="settings[site_adresse]" class="form-control" rows="2" required><?php echo htmlspecialchars($settings['site_adresse']); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email Contact</label>
                                <input type="email" name="settings[site_email]" class="form-control" value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Téléphone Salon</label>
                                <input type="text" name="settings[site_telephone]" class="form-control" value="<?php echo htmlspecialchars($settings['site_telephone']); ?>" required>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-dark w-100 rounded-pill">Mettre à jour le site</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
