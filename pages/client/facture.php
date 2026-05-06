<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('client');

if (!isset($_GET['id'])) {
    redirect('mes_rdv.php');
}

$id_rdv = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Récupérer les informations complètes de la facture
try {
    $stmt = $pdo->prepare("
        SELECT r.id_rdv, r.date_rdv, r.heure_rdv, r.prix_total, r.reduction_appliquee, r.statut, r.recu_telecharge,
               s.nom_service, s.prix_standard,
               e_u.prenom as emp_prenom,
               c_u.nom as client_nom, c_u.prenom as client_prenom, c_u.email, c_u.telephone, c_u.adresse
        FROM rendez_vous r 
        JOIN clientes c ON r.id_cliente = c.id_cliente 
        JOIN utilisateurs c_u ON c.id_utilisateur = c_u.id_utilisateur
        JOIN services s ON r.id_service = s.id_service 
        JOIN employes e ON r.id_employe = e.id_employe
        JOIN utilisateurs e_u ON e.id_utilisateur = e_u.id_utilisateur
        WHERE r.id_rdv = :id_rdv AND c_u.id_utilisateur = :id_user AND r.statut IN ('confirme', 'termine')
    ");
    $stmt->execute(['id_rdv' => $id_rdv, 'id_user' => $user_id]);
    $facture = $stmt->fetch();
    
    if (!$facture) {
        $_SESSION['flash_error'] = "Facture introuvable ou rendez-vous non confirmé.";
        redirect('mes_rdv.php');
    }

    // Marquer comme téléchargé/vu si c'est la première fois
    if ($facture['recu_telecharge'] == 0) {
        $pdo->prepare("UPDATE rendez_vous SET recu_telecharge = 1 WHERE id_rdv = ?")->execute([$id_rdv]);
        
        $client_name = $facture['client_prenom'] . ' ' . $facture['client_nom'];
        $stmt_admin = $pdo->query("SELECT id_utilisateur FROM utilisateurs WHERE role = 'admin' LIMIT 1");
        $admin_id = $stmt_admin->fetchColumn();
        if ($admin_id) {
            create_notification($admin_id, "Le client $client_name a téléchargé son reçu pour le RDV #$id_rdv", 'info');
        }
    }

    // Récupérer les paramètres du site pour les afficher sur la facture
    $stmt_settings = $pdo->query("SELECT cle, valeur FROM parametres_site");
    $site_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
} catch(PDOException $e) {
    redirect('mes_rdv.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #<?php echo $facture['id_rdv']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@400;500&display=swap" rel="stylesheet">
    <!-- html2pdf -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f4f4f4; padding: 20px; }
        .facture-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .playfair { font-family: 'Playfair Display', serif; }
        .text-primary-custom { color: #d4a373; }
        .border-custom { border-color: #d4a373 !important; }
        @media print {
            body { background-color: #fff; padding: 0; }
            .facture-container { box-shadow: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="text-center mb-4 no-print">
    <button onclick="downloadPDF()" class="btn btn-primary" style="background-color: #d4a373; border-color: #d4a373;">
        <i class="fas fa-download"></i> Télécharger PDF
    </button>
    <a href="mes_rdv.php" class="btn btn-secondary">Retour</a>
</div>

<div class="facture-container" id="factureContent">
    <div class="row border-bottom border-custom pb-3 mb-4">
        <div class="col-6">
            <h1 class="playfair text-primary-custom fw-bold"><?php echo htmlspecialchars($site_settings['site_nom'] ?? 'TYA STYLEX'); ?></h1>
            <p class="mb-0 text-muted"><?php echo htmlspecialchars($site_settings['site_description'] ?? ''); ?></p>
            <p class="mb-0 text-muted"><?php echo htmlspecialchars($site_settings['site_adresse'] ?? ''); ?></p>
            <p class="text-muted"><?php echo htmlspecialchars($site_settings['site_email'] ?? ''); ?> | <?php echo htmlspecialchars($site_settings['site_telephone'] ?? ''); ?></p>
        </div>
        <div class="col-6 text-end">
            <h2 class="text-muted"><?php echo $facture['statut'] == 'termine' ? 'FACTURE' : 'REÇU'; ?></h2>
            <p class="fw-bold mb-0">N° F-<?php echo date('Y', strtotime($facture['date_rdv'])) . '-' . str_pad($facture['id_rdv'], 4, '0', STR_PAD_LEFT); ?></p>
            <p class="mb-0">Date : <?php echo date('d/m/Y', strtotime($facture['date_rdv'])); ?></p>
        </div>
    </div>
    
    <div class="row mb-5">
        <div class="col-12">
            <h5 class="text-muted border-bottom pb-2">Facturé à :</h5>
            <h4 class="fw-bold"><?php echo htmlspecialchars($facture['client_prenom'] . ' ' . $facture['client_nom']); ?></h4>
            <p class="mb-0"><?php echo htmlspecialchars($facture['email']); ?></p>
            <p class="mb-0"><?php echo htmlspecialchars($facture['telephone']); ?></p>
            <?php if(!empty($facture['adresse'])): ?>
                <p><?php echo htmlspecialchars($facture['adresse']); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="table table-bordered mb-5">
        <thead class="table-light">
            <tr>
                <th>Description (Prestation)</th>
                <th>Coiffeuse</th>
                <th class="text-end">Prix Unitaire</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($facture['nom_service']); ?></strong><br>
                    <small class="text-muted">Rendez-vous du <?php echo date('d/m/Y', strtotime($facture['date_rdv'])); ?> à <?php echo date('H:i', strtotime($facture['heure_rdv'])); ?></small>
                </td>
                <td><?php echo htmlspecialchars($facture['emp_prenom']); ?></td>
                <td class="text-end"><?php echo format_price($facture['prix_standard']); ?></td>
            </tr>
        </tbody>
    </table>
    
    <div class="row justify-content-end">
        <div class="col-md-5">
            <table class="table table-sm table-borderless">
                <tr>
                    <td>Sous-total :</td>
                    <td class="text-end"><?php echo format_price($facture['prix_standard']); ?></td>
                </tr>
                <?php if($facture['reduction_appliquee'] > 0): ?>
                <tr class="text-success">
                    <td>Réduction fidélité (-<?php echo $facture['reduction_appliquee']; ?>%) :</td>
                    <td class="text-end">- <?php echo format_price($facture['prix_standard'] * ($facture['reduction_appliquee']/100)); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="border-top border-custom border-2">
                    <td class="fw-bold fs-5"><?php echo $facture['statut'] == 'termine' ? 'TOTAL PAYÉ :' : 'TOTAL À RÉGLER :'; ?></td>
                    <td class="text-end fw-bold fs-5 text-primary-custom"><?php echo format_price($facture['prix_total']); ?></td>
                </tr>
            </table>
            <div class="text-end mt-2">
                <span class="badge bg-light text-dark border p-2">
                    <i class="fas fa-hand-holding-usd me-1"></i> PAIEMENT SUR PLACE
                </span>
            </div>
        </div>
    </div>
    
    <div class="mt-5 text-center text-muted border-top pt-3">
        <p class="playfair fs-5 mb-1">Merci pour votre confiance !</p>
        <small>Tya Stylex - La beauté à votre image</small>
    </div>
</div>

<script>
function downloadPDF() {
    const element = document.getElementById('factureContent');
    const opt = {
        margin:       10,
        filename:     'Recu_TyaStylex_<?php echo $facture['id_rdv']; ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

</body>
</html>
