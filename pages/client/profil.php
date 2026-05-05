<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('client');

$user_id = $_SESSION['user_id'];

// Traitement de la mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $nom = sanitize_input($_POST['nom']);
        $prenom = sanitize_input($_POST['prenom']);
        $telephone = sanitize_input($_POST['telephone']);
        $avatar_path = null;

        // Gestion de l'upload de l'avatar
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $_FILES['avatar']['tmp_name']);
            finfo_close($file_info);

            if (in_array($mime_type, $allowed_types)) {
                $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $destination = dirname(__DIR__, 2) . '/assets/images/uploads/profils/' . $filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
                    $avatar_path = '/coiffure_salon/assets/images/uploads/profils/' . $filename;
                }
            } else {
                $_SESSION['flash_error'] = "Format d'image non supporté (JPEG, PNG ou WEBP).";
                redirect('profil.php');
            }
        }
        
        try {
            if ($avatar_path) {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, telephone = ?, avatar = ? WHERE id_utilisateur = ?");
                $stmt->execute([$nom, $prenom, $telephone, $avatar_path, $user_id]);
                $_SESSION['user_avatar'] = $avatar_path;
            } else {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, prenom = ?, telephone = ? WHERE id_utilisateur = ?");
                $stmt->execute([$nom, $prenom, $telephone, $user_id]);
            }
            
            $_SESSION['user_nom'] = $prenom . ' ' . $nom; // Mise à jour de la session
            $_SESSION['flash_success'] = "Votre profil a été mis à jour avec succès.";
        } catch(PDOException $e) {
            $_SESSION['flash_error'] = "Erreur lors de la mise à jour.";
        }
        redirect('profil.php');
    }
}

// Récupération des infos
try {
    $stmt = $pdo->prepare("SELECT nom, prenom, email, telephone, avatar FROM utilisateurs WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch(PDOException $e) {}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <h2 class="playfair mb-4">Mon Profil</h2>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="card p-4 shadow-sm border-0">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <?php 
                            $avatar_url = !empty($user['avatar']) ? $user['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($user['prenom'].' '.$user['nom']).'&background=random';
                            ?>
                            <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar" class="rounded-circle object-fit-cover border shadow-sm" width="120" height="120" id="avatarPreview">
                            <label for="avatarInput" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 shadow" style="cursor: pointer;">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="avatarInput" name="avatar" class="d-none" accept="image/jpeg, image/png, image/webp">
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-control" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email (Non modifiable)</label>
                        <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        <small class="text-muted">Pour modifier votre email, veuillez contacter le support.</small>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" name="telephone" value="<?php echo htmlspecialchars($user['telephone']); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                </form>
            </div>
        </div>
        
        <div class="col-lg-6 mt-4 mt-lg-0">
            <div class="card p-4 shadow-sm border-0 bg-light">
                <h5 class="playfair mb-3">Sécurité</h5>
                <p class="text-muted mb-4">Vous souhaitez modifier votre mot de passe ?</p>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <!-- La logique de changement de mot de passe n'est pas implémentée dans cet exemple par souci de concision, mais l'UI est prête -->
                    <div class="mb-3">
                        <label class="form-label">Mot de passe actuel</label>
                        <input type="password" class="form-control" name="old_password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nouveau mot de passe</label>
                        <input type="password" class="form-control" name="new_password">
                    </div>
                    <button type="button" class="btn btn-outline-primary" onclick="Swal.fire('Info', 'Fonctionnalité en cours de développement', 'info')">Changer le mot de passe</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('avatarInput').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
