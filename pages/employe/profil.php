<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('employe');

$user_id = $_SESSION['user_id'];

try {
    // Récupérer les infos complètes
    $stmt = $pdo->prepare("
        SELECT u.*, e.specialite, e.horaire_debut, e.horaire_fin, e.disponibilite
        FROM utilisateurs u
        JOIN employes e ON u.id_utilisateur = e.id_utilisateur
        WHERE u.id_utilisateur = :id
    ");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch();
} catch(PDOException $e) {
    die("Erreur de chargement du profil");
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="mb-4">
        <h2 class="playfair fw-bold mb-1">Mon Profil Professionnel</h2>
        <p class="text-muted small">Gérez vos informations personnelles et vos disponibilités</p>
    </div>

    <div class="row g-4">
        <!-- Carte Profil -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center glass-card">
                <div class="position-relative d-inline-block mb-3">
                    <?php 
                    $avatar = !empty($user['avatar']) ? $user['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($user['prenom'].'+'.$user['nom']).'&background=d4a373&color=fff';
                    ?>
                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="rounded-circle border border-4 border-white shadow" style="width:120px; height:120px; object-fit: cover;">
                    <button class="btn btn-primary btn-sm rounded-circle position-absolute bottom-0 end-0 p-2" title="Changer l'avatar">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></h4>
                <p class="text-primary fw-medium mb-3"><?php echo htmlspecialchars($user['specialite']); ?></p>
                
                <div class="d-flex justify-content-center gap-2 mb-4">
                    <span class="badge badge-soft-success rounded-pill px-3 py-2">Compte Actif</span>
                    <span class="badge badge-soft-primary rounded-pill px-3 py-2"><?php echo ucfirst($user['role']); ?></span>
                </div>

                <div class="text-start bg-light p-3 rounded-4">
                    <div class="mb-2">
                        <small class="text-muted d-block">Email</small>
                        <span class="fw-bold"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Téléphone</small>
                        <span class="fw-bold"><?php echo htmlspecialchars($user['telephone']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire de modification -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="playfair fw-bold mb-4">Modifier mes informations</h5>
                <form action="#" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Prénom</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['prenom']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nom</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['nom']); ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Ma Spécialité</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['specialite']); ?>">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="playfair fw-bold mb-3">Mes Horaires de Travail</h5>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Début de journée</label>
                            <input type="time" class="form-control" value="<?php echo $user['horaire_debut']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fin de journée</label>
                            <input type="time" class="form-control" value="<?php echo $user['horaire_fin']; ?>">
                        </div>
                        
                        <div class="col-md-12 mt-4">
                            <button type="button" class="btn btn-primary px-5 py-2 rounded-pill shadow-sm">
                                <i class="fas fa-save me-2"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
