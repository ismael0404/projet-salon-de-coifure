<?php
/**
 * Contrôleur des Rendez-vous (RDV Controller)
 * 
 * Ce contrôleur gère la création des nouveaux rendez-vous par les clientes.
 * Il récupère les informations de fidélité pour appliquer automatiquement
 * des réductions sur le prix final de la prestation.
 * 
 * @package TyaStylex\Controllers
 */

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['flash_error'] = "Erreur de sécurité. Veuillez réessayer.";
        redirect('javascript:history.back()');
    }

    // ==== CRÉATION D'UN RDV ====
    if ($action === 'create') {
        require_role('client');
        
        $id_service = (int)$_POST['id_service'];
        $id_employe = (int)$_POST['id_employe'];
        $date_rdv = $_POST['date_rdv'];
        $heure_rdv = $_POST['heure_rdv'];
        $commentaire = sanitize_input($_POST['commentaire'] ?? '');
        
        try {
            // 1. Obtenir l'ID cliente
            $stmt = $pdo->prepare("SELECT id_cliente, reduction FROM clientes WHERE id_utilisateur = :id");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $cliente = $stmt->fetch();
            
            if (!$cliente) {
                $_SESSION['flash_error'] = "Profil client introuvable.";
                redirect('/coiffure_salon/pages/client/dashboard.php');
            }
            
            // 2. Obtenir infos du service
            $stmt = $pdo->prepare("SELECT duree, prix_standard FROM services WHERE id_service = :id");
            $stmt->execute(['id' => $id_service]);
            $service = $stmt->fetch();
            
            // 3. Calculer le prix final avec réduction
            $prix_final = $service['prix_standard'];
            $reduction_appliquee = 0;
            if ($cliente['reduction'] > 0) {
                $reduction_appliquee = $cliente['reduction'];
                $prix_final = $prix_final - ($prix_final * ($reduction_appliquee / 100));
            }
            
            // 4. Insérer le RDV avec le prix calculé et la réduction
            $stmt = $pdo->prepare("
                INSERT INTO rendez_vous (id_cliente, id_employe, id_service, date_rdv, heure_rdv, duree, prix_total, reduction_appliquee, statut, commentaire) 
                VALUES (:id_client, :id_emp, :id_serv, :date_r, :heure_r, :duree, :prix, :reduc, 'en_attente', :comm)
            ");
            $stmt->execute([
                'id_client' => $cliente['id_cliente'],
                'id_emp' => $id_employe,
                'id_serv' => $id_service,
                'date_r' => $date_rdv,
                'heure_r' => $heure_rdv,
                'duree' => $service['duree'],
                'prix' => $prix_final,
                'reduc' => $reduction_appliquee,
                'comm' => $commentaire
            ]);
            
            // 5. Enregistrement de l'action dans le journal d'activités (Audit Trail)
            $stmt = $pdo->prepare("INSERT INTO log_activites (id_utilisateur, action, description) VALUES (:id, 'Nouveau RDV', 'Réservation effectuée en ligne')");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            
            $_SESSION['flash_success'] = "Votre rendez-vous a été enregistré avec succès ! Il est actuellement en attente de confirmation.";
            redirect('/coiffure_salon/pages/client/mes_rdv.php');
            
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Une erreur s'est produite lors de la réservation.";
            redirect('/coiffure_salon/pages/client/prendre_rdv.php');
        }
    }
}
