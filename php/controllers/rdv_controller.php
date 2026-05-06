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
            
            // 6. Notifications en temps réel
            // Notification pour l'employée concernée
            $stmt_emp = $pdo->prepare("SELECT id_utilisateur FROM employes WHERE id_employe = ?");
            $stmt_emp->execute([$id_employe]);
            $emp_user_id = $stmt_emp->fetchColumn();
            if ($emp_user_id) {
                create_notification($emp_user_id, "Nouveau RDV : Vous avez une réservation pour le " . date('d/m', strtotime($date_rdv)) . " à " . date('H:i', strtotime($heure_rdv)), 'info');
            }

            // Notifications pour tous les administrateurs
            $stmt_admins = $pdo->query("SELECT id_utilisateur FROM utilisateurs WHERE role = 'admin' AND statut = 'actif'");
            $admins = $stmt_admins->fetchAll(PDO::FETCH_COLUMN);
            foreach ($admins as $admin_id) {
                create_notification($admin_id, "Nouveau RDV de " . $_SESSION['user_nom'] . " pour le " . date('d/m', strtotime($date_rdv)) . " à " . date('H:i', strtotime($heure_rdv)), 'info');
            }
            
            $_SESSION['flash_success'] = "Votre rendez-vous a été enregistré avec succès ! Il est actuellement en attente de confirmation.";
            redirect('/coiffure_salon/pages/client/mes_rdv.php');
            
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Une erreur s'est produite lors de la réservation.";
            redirect('/coiffure_salon/pages/client/prendre_rdv.php');
        }
    }
    // ==== ANNULATION D'UN RDV PAR LA CLIENTE ====
    elseif ($action === 'cancel') {
        require_role('client');
        $id_rdv = (int)$_POST['id_rdv'];

        try {
            // Vérifier que le RDV appartient bien à la cliente
            $stmt = $pdo->prepare("
                SELECT r.id_rdv, r.date_rdv, r.heure_rdv, c.id_utilisateur as id_client_user, e.id_utilisateur as id_emp_user 
                FROM rendez_vous r 
                JOIN clientes c ON r.id_cliente = c.id_cliente 
                JOIN employes e ON r.id_employe = e.id_employe 
                WHERE r.id_rdv = ? AND c.id_utilisateur = ?
            ");
            $stmt->execute([$id_rdv, $_SESSION['user_id']]);
            $rdv = $stmt->fetch();

            if ($rdv) {
                $stmt = $pdo->prepare("UPDATE rendez_vous SET statut = 'annule' WHERE id_rdv = ?");
                $stmt->execute([$id_rdv]);

                $date_f = date('d/m', strtotime($rdv['date_rdv']));
                $msg = "RDV Annulé : La cliente " . $_SESSION['user_nom'] . " a annulé son RDV du $date_f.";

                // Notifier l'employée
                create_notification($rdv['id_emp_user'], $msg, 'danger');

                // Notifier les admins
                $stmt_admins = $pdo->query("SELECT id_utilisateur FROM utilisateurs WHERE role = 'admin' AND statut = 'actif'");
                foreach ($stmt_admins->fetchAll(PDO::FETCH_COLUMN) as $admin_id) {
                    create_notification($admin_id, $msg, 'danger');
                }

                $_SESSION['flash_success'] = "Rendez-vous annulé avec succès.";
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Erreur lors de l'annulation.";
        }
        redirect('/coiffure_salon/pages/client/mes_rdv.php');
    }

    // ==== MODIFICATION D'UN RDV PAR LA CLIENTE ====
    elseif ($action === 'update') {
        require_role('client');
        $id_rdv = (int)$_POST['id_rdv'];
        $date_rdv = $_POST['date_rdv'];
        $heure_rdv = $_POST['heure_rdv'];

        try {
            // Vérifier que le RDV appartient bien à la cliente
            $stmt = $pdo->prepare("
                SELECT r.id_rdv, c.id_utilisateur as id_client_user, e.id_utilisateur as id_emp_user 
                FROM rendez_vous r 
                JOIN clientes c ON r.id_cliente = c.id_cliente 
                JOIN employes e ON r.id_employe = e.id_employe 
                WHERE r.id_rdv = ? AND c.id_utilisateur = ?
            ");
            $stmt->execute([$id_rdv, $_SESSION['user_id']]);
            $rdv = $stmt->fetch();

            if ($rdv) {
                $stmt = $pdo->prepare("UPDATE rendez_vous SET date_rdv = ?, heure_rdv = ?, statut = 'en_attente' WHERE id_rdv = ?");
                $stmt->execute([$date_rdv, $heure_rdv, $id_rdv]);

                $date_f = date('d/m', strtotime($date_rdv));
                $msg = "RDV Modifié : " . $_SESSION['user_nom'] . " a déplacé son RDV au $date_f à " . date('H:i', strtotime($heure_rdv)) . ".";

                // Notifier l'employée
                create_notification($rdv['id_emp_user'], $msg, 'warning');

                // Notifier les admins
                $stmt_admins = $pdo->query("SELECT id_utilisateur FROM utilisateurs WHERE role = 'admin' AND statut = 'actif'");
                foreach ($stmt_admins->fetchAll(PDO::FETCH_COLUMN) as $admin_id) {
                    create_notification($admin_id, $msg, 'warning');
                }

                $_SESSION['flash_success'] = "Rendez-vous modifié. Il est de nouveau en attente de confirmation.";
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Erreur lors de la modification.";
        }
        redirect('/coiffure_salon/pages/client/mes_rdv.php');
    }
}
