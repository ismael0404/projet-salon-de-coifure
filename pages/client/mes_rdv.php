<?php
/**
 * Interface d'historique des rendez-vous (Espace Cliente)
 * 
 * Ce fichier permet à une cliente connectée de visualiser l'historique complet
 * de ses réservations, de vérifier le statut de chaque rendez-vous,
 * et de télécharger la facture associée au format PDF pour les rendez-vous terminés.
 * 
 * @package TyaStylex\Client
 */

require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

// Vérification de sécurité : Seules les clientes peuvent accéder à cette page
require_role('client');

$user_id = $_SESSION['user_id'];
$id_cliente = 0;

// Récupération de l'ID cliente spécifique (différent de l'ID utilisateur global)
try {
    $stmt = $pdo->prepare("SELECT id_cliente FROM clientes WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $user_id]);
    $client_data = $stmt->fetch();
    if($client_data) {
        $id_cliente = $client_data['id_cliente'];
    }
} catch(PDOException $e) {
    // En production, on loggerait l'erreur ($e->getMessage())
}

// Récupération de l'historique complet des rendez-vous avec jointures
// pour obtenir le nom du service et le prénom de la coiffeuse.
$historique = [];
if($id_cliente > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.id_rdv, r.date_rdv, r.heure_rdv, r.statut, r.prix_total, r.reduction_appliquee, 
                   s.nom_service, e_u.prenom as emp_prenom 
            FROM rendez_vous r 
            JOIN services s ON r.id_service = s.id_service 
            JOIN employes e ON r.id_employe = e.id_employe
            JOIN utilisateurs e_u ON e.id_utilisateur = e_u.id_utilisateur
            WHERE r.id_cliente = :id_client 
            ORDER BY r.date_rdv DESC, r.heure_rdv DESC
        ");
        $stmt->execute(['id_client' => $id_cliente]);
        $historique = $stmt->fetchAll();
    } catch(PDOException $e) {}
}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <h2 class="playfair mb-4">Mon historique de rendez-vous</h2>
    
    <div class="card p-4 shadow-sm border-0">
        <?php if(empty($historique)): ?>
            <div class="alert alert-info">Vous n'avez aucun historique de rendez-vous pour le moment.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date & Heure</th>
                            <th>Prestation</th>
                            <th>Coiffeuse</th>
                            <th>Prix (avec réduc.)</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($historique as $rdv): 
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
                            <td><?php echo htmlspecialchars($rdv['nom_service']); ?></td>
                            <td><?php echo htmlspecialchars($rdv['emp_prenom']); ?></td>
                            <td>
                                <?php echo format_price($rdv['prix_total']); ?>
                                <?php if($rdv['reduction_appliquee'] > 0): ?>
                                    <br><span class="badge bg-success small">-<?php echo $rdv['reduction_appliquee']; ?>%</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($rdv['statut']); ?></span></td>
                            <td>
                                <?php if($rdv['statut'] == 'termine' || $rdv['statut'] == 'confirme'): ?>
                                    <?php if($rdv['statut'] == 'termine'): ?>
                                        <button class="btn btn-sm btn-outline-warning" title="Laisser un avis" onclick="Swal.fire('Info', 'Système d\'avis en cours de développement', 'info')"><i class="fas fa-star"></i></button>
                                    <?php endif; ?>
                                    <a href="facture.php?id=<?php echo $rdv['id_rdv']; ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Télécharger le reçu"><i class="fas fa-file-pdf"></i></a>
                                    <?php if($rdv['statut'] == 'confirme'): ?>
                                        <button class="btn btn-sm btn-outline-primary" title="Modifier le RDV" onclick="modifierRDV(<?php echo $rdv['id_rdv']; ?>, '<?php echo $rdv['date_rdv']; ?>', '<?php echo $rdv['heure_rdv']; ?>')"><i class="fas fa-edit"></i></button>
                                    <?php endif; ?>
                                <?php elseif($rdv['statut'] == 'en_attente'): ?>
                                    <button class="btn btn-sm btn-outline-primary" title="Modifier le RDV" onclick="modifierRDV(<?php echo $rdv['id_rdv']; ?>, '<?php echo $rdv['date_rdv']; ?>', '<?php echo $rdv['heure_rdv']; ?>')"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" title="Annuler le RDV" onclick="annulerRDV(<?php echo $rdv['id_rdv']; ?>)"><i class="fas fa-times"></i></button>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <script>
            function modifierRDV(id, date, heure) {
                Swal.fire({
                    title: 'Modifier mon rendez-vous',
                    html: `
                        <label class="form-label mt-2">Nouvelle date</label>
                        <input type="date" id="new_date" class="swal2-input" value="${date}" min="<?php echo date('Y-m-d'); ?>">
                        <label class="form-label mt-2">Nouvel horaire</label>
                        <input type="time" id="new_time" class="swal2-input" value="${heure.substring(0,5)}">
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Enregistrer les modifications',
                    confirmButtonColor: '#d4a373',
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
            }

            function annulerRDV(id) {
                Swal.fire({
                    title: 'Annuler ce rendez-vous ?',
                    text: "Cette action informera l'administration et l'employée.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Oui, annuler'
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
            </script>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
