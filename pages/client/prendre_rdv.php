<?php
require_once dirname(__DIR__, 2) . '/php/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

require_role('client');

// Récupération des services
$services = [];
try {
    $stmt = $pdo->query("SELECT * FROM services WHERE statut = 'actif'");
    $services = $stmt->fetchAll();
} catch(PDOException $e) {}

// Récupération des employées (coiffeuses)
$employes = [];
try {
    $stmt = $pdo->query("SELECT e.id_employe, u.nom, u.prenom, e.specialite 
                         FROM employes e 
                         JOIN utilisateurs u ON e.id_utilisateur = u.id_utilisateur 
                         WHERE u.statut = 'actif' AND e.disponibilite != 'absente'");
    $employes = $stmt->fetchAll();
} catch(PDOException $e) {}

include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="container-fluid">
    <h2 class="playfair mb-4">Réserver une prestation</h2>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card p-4 shadow-sm border-0">
                <form id="rdvForm" action="../../php/controllers/rdv_controller.php?action=create" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <!-- Etape 1 : Service -->
                    <h5 class="text-primary mb-3"><i class="fas fa-cut"></i> 1. Choisissez une prestation</h5>
                    <div class="row g-3 mb-4">
                        <?php foreach($services as $srv): ?>
                        <div class="col-md-6">
                            <input type="radio" class="btn-check service-radio" name="id_service" id="srv_<?php echo $srv['id_service']; ?>" value="<?php echo $srv['id_service']; ?>" data-price="<?php echo $srv['prix_standard']; ?>" data-duration="<?php echo $srv['duree']; ?>" autocomplete="off" required>
                            <label class="btn btn-outline-secondary w-100 text-start p-3 h-100" for="srv_<?php echo $srv['id_service']; ?>">
                                <div class="fw-bold"><?php echo htmlspecialchars($srv['nom_service']); ?></div>
                                <div class="small text-muted mb-2"><?php echo htmlspecialchars($srv['description']); ?></div>
                                <div class="d-flex justify-content-between mt-auto">
                                    <span class="text-primary fw-bold"><?php echo format_price($srv['prix_standard']); ?></span>
                                    <span><i class="far fa-clock"></i> <?php echo $srv['duree']; ?> min</span>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Etape 2 : Coiffeuse -->
                    <h5 class="text-primary mb-3"><i class="fas fa-user-tie"></i> 2. Choisissez une coiffeuse</h5>
                    <div class="row g-3 mb-4">
                        <?php foreach($employes as $emp): ?>
                        <div class="col-md-4">
                            <input type="radio" class="btn-check employe-radio" name="id_employe" id="emp_<?php echo $emp['id_employe']; ?>" value="<?php echo $emp['id_employe']; ?>" autocomplete="off" required>
                            <label class="btn btn-outline-secondary w-100 p-3 h-100" for="emp_<?php echo $emp['id_employe']; ?>">
                                <div class="avatar rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width:50px; height:50px">
                                    <i class="fas fa-female text-primary fa-2x"></i>
                                </div>
                                <div class="fw-bold"><?php echo htmlspecialchars($emp['prenom']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($emp['specialite']); ?></div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Etape 3 : Date et Heure -->
                    <h5 class="text-primary mb-3"><i class="far fa-calendar-alt"></i> 3. Date et Heure</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Date du rendez-vous</label>
                            <input type="date" name="date_rdv" id="date_rdv" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Heure disponible</label>
                            <select name="heure_rdv" id="heure_rdv" class="form-select" required disabled>
                                <option value="">Sélectionnez d'abord un service, une coiffeuse et une date</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Etape 4 : Commentaire (Optionnel) -->
                    <div class="mb-4">
                        <label class="form-label text-muted"><i class="fas fa-comment-alt"></i> Demande particulière (optionnel)</label>
                        <textarea name="commentaire" class="form-control" rows="2" placeholder="Une information que nous devrions savoir ?"></textarea>
                    </div>
                    
                    <button type="submit" id="submitBtn" class="btn btn-primary btn-lg w-100" disabled>
                        Confirmer la réservation
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Récapitulatif -->
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card p-4 shadow-sm border-0 sticky-top" style="top: 20px;">
                <h5 class="playfair mb-4">Récapitulatif</h5>
                <ul class="list-group list-group-flush mb-4">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>Prestation</span>
                        <strong id="recap-service">-</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>Durée</span>
                        <strong id="recap-duree">-</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>Coiffeuse</span>
                        <strong id="recap-employe">-</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>Date</span>
                        <strong id="recap-date">-</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span>Heure</span>
                        <strong id="recap-heure">-</strong>
                    </li>
                </ul>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fs-5">Total estimé</span>
                    <h3 class="text-primary mb-0 playfair" id="recap-prix">0 FCFA</h3>
                </div>
                <small class="text-muted d-block text-center">* Le prix final peut varier selon les produits utilisés.</small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceRadios = document.querySelectorAll('.service-radio');
    const employeRadios = document.querySelectorAll('.employe-radio');
    const dateInput = document.getElementById('date_rdv');
    const heureSelect = document.getElementById('heure_rdv');
    const submitBtn = document.getElementById('submitBtn');
    
    // Elements de récapitulatif
    const recapService = document.getElementById('recap-service');
    const recapDuree = document.getElementById('recap-duree');
    const recapEmploye = document.getElementById('recap-employe');
    const recapDate = document.getElementById('recap-date');
    const recapHeure = document.getElementById('recap-heure');
    const recapPrix = document.getElementById('recap-prix');
    
    function checkAvailability() {
        const serviceId = document.querySelector('.service-radio:checked')?.value;
        const employeId = document.querySelector('.employe-radio:checked')?.value;
        const dateVal = dateInput.value;
        
        if (serviceId && employeId && dateVal) {
            heureSelect.innerHTML = '<option value="">Recherche des disponibilités...</option>';
            heureSelect.disabled = true;
            submitBtn.disabled = true;
            
            fetch(`../../php/controllers/api_disponibilites.php?service=${serviceId}&employe=${employeId}&date=${dateVal}`)
                .then(res => res.json())
                .then(data => {
                    heureSelect.innerHTML = '<option value="">Choisissez une heure</option>';
                    if (data.slots && data.slots.length > 0) {
                        data.slots.forEach(slot => {
                            const option = document.createElement('option');
                            option.value = slot + ':00';
                            option.textContent = slot;
                            heureSelect.appendChild(option);
                        });
                        heureSelect.disabled = false;
                    } else {
                        heureSelect.innerHTML = '<option value="">Aucune disponibilité ce jour</option>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    heureSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                });
        }
    }
    
    function updateRecap() {
        const srvChecked = document.querySelector('.service-radio:checked');
        if (srvChecked) {
            const label = document.querySelector(`label[for="${srvChecked.id}"]`);
            recapService.textContent = label.querySelector('.fw-bold').textContent;
            recapDuree.textContent = srvChecked.dataset.duration + ' min';
            
            // Formatage du prix
            let prix = parseInt(srvChecked.dataset.price);
            recapPrix.textContent = new Intl.NumberFormat('fr-FR').format(prix) + ' FCFA';
        }
        
        const empChecked = document.querySelector('.employe-radio:checked');
        if (empChecked) {
            const label = document.querySelector(`label[for="${empChecked.id}"]`);
            recapEmploye.textContent = label.querySelector('.fw-bold').textContent;
        }
        
        if (dateInput.value) {
            const dateObj = new Date(dateInput.value);
            recapDate.textContent = dateObj.toLocaleDateString('fr-FR');
        }
        
        if (heureSelect.value) {
            recapHeure.textContent = heureSelect.options[heureSelect.selectedIndex].text;
            submitBtn.disabled = false;
        } else {
            recapHeure.textContent = '-';
            submitBtn.disabled = true;
        }
    }
    
    // Event listeners
    serviceRadios.forEach(radio => radio.addEventListener('change', () => { updateRecap(); checkAvailability(); }));
    employeRadios.forEach(radio => radio.addEventListener('change', () => { updateRecap(); checkAvailability(); }));
    dateInput.addEventListener('change', () => { updateRecap(); checkAvailability(); });
    heureSelect.addEventListener('change', updateRecap);
});
</script>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>
