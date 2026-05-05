<?php
/**
 * API REST Interne : Gestion des disponibilités
 * 
 * Ce script est appelé en AJAX par le formulaire de prise de rendez-vous.
 * Il calcule les créneaux horaires disponibles pour un employé spécifique
 * et une date donnée, en prenant en compte la durée de la prestation choisie
 * et les rendez-vous existants, prévenant ainsi tout conflit d'horaire.
 * 
 * @package TyaStylex\API
 * @return JSON Retourne un tableau JSON des heures disponibles
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$date = $_GET['date'] ?? '';
$id_employe = isset($_GET['employe']) ? (int)$_GET['employe'] : 0;
$id_service = isset($_GET['service']) ? (int)$_GET['service'] : 0;

if (empty($date) || $id_employe <= 0 || $id_service <= 0) {
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

try {
    // Récupérer la durée du service
    $stmt = $pdo->prepare("SELECT duree FROM services WHERE id_service = :id");
    $stmt->execute(['id' => $id_service]);
    $service = $stmt->fetch();
    if (!$service) {
        echo json_encode(['error' => 'Service introuvable']);
        exit;
    }
    $duree = (int)$service['duree'];

    // Récupérer les horaires de l'employé
    $stmt = $pdo->prepare("SELECT horaire_debut, horaire_fin FROM employes WHERE id_employe = :id");
    $stmt->execute(['id' => $id_employe]);
    $employe = $stmt->fetch();
    if (!$employe) {
        echo json_encode(['error' => 'Employé introuvable']);
        exit;
    }

    $debut_journee = strtotime($date . ' ' . $employe['horaire_debut']);
    $fin_journee = strtotime($date . ' ' . $employe['horaire_fin']);
    
    // Si la date est aujourd'hui, on ne propose que les heures futures
    if ($date == date('Y-m-d')) {
        $maintenant = time();
        if ($debut_journee < $maintenant) {
            // Arrondir à la demi-heure supérieure
            $debut_journee = ceil($maintenant / 1800) * 1800; 
        }
    } elseif ($date < date('Y-m-d')) {
        echo json_encode(['slots' => []]);
        exit;
    }

    // Récupérer les RDV existants pour cet employé à cette date
    $stmt = $pdo->prepare("
        SELECT r.heure_rdv, s.duree 
        FROM rendez_vous r 
        JOIN services s ON r.id_service = s.id_service
        WHERE r.id_employe = :id_emp AND r.date_rdv = :date_rdv 
        AND r.statut IN ('en_attente', 'confirme')
    ");
    $stmt->execute(['id_emp' => $id_employe, 'date_rdv' => $date]);
    $rdvs = $stmt->fetchAll();

    $occuped_slots = [];
    foreach ($rdvs as $r) {
        $start = strtotime($date . ' ' . $r['heure_rdv']);
        $end = $start + ($r['duree'] * 60);
        $occuped_slots[] = ['start' => $start, 'end' => $end];
    }

    $available_slots = [];
    $current_time = $debut_journee;

    // Générer les créneaux (par pas de 30 minutes)
    // On boucle de l'heure de début jusqu'à l'heure de fin moins la durée du service
    while ($current_time + ($duree * 60) <= $fin_journee) {
        $slot_end = $current_time + ($duree * 60);
        $is_available = true;

        // Vérifier les conflits avec tous les rendez-vous déjà confirmés ou en attente
        foreach ($occuped_slots as $occ) {
            // Logique d'intersection de temps :
            // Si le créneau demandé chevauche un créneau occupé, on le marque indisponible.
            if (
                ($current_time >= $occ['start'] && $current_time < $occ['end']) || 
                ($slot_end > $occ['start'] && $slot_end <= $occ['end']) ||
                ($current_time <= $occ['start'] && $slot_end >= $occ['end'])
            ) {
                $is_available = false;
                break;
            }
        }

        if ($is_available) {
            $available_slots[] = date('H:i', $current_time);
        }

        $current_time += 1800; // Avancer de 30 minutes
    }

    echo json_encode(['slots' => $available_slots]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur base de données']);
}
