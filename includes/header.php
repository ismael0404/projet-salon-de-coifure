<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TYA STYLEX</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/coiffure_salon/assets/css/style.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            padding-top: 0 !important;
            height: 100vh;
            overflow: hidden;
        }
        h1, h2, h3, h4, h5, h6, .playfair {
            font-family: 'Playfair Display', serif;
        }
        .dashboard-container {
            display: flex;
            height: 100vh;
            width: 100%;
        }
        .main-content {
            flex-grow: 1;
            padding: 0 25px 25px 25px;
            overflow-y: auto;
            height: 100vh;
            background-color: #f4f7f6;
            scroll-behavior: smooth;
        }
        .sticky-header {
            position: sticky;
            top: 10px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
            margin-top: 10px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-primary {
            background-color: #d4a373;
            border-color: #d4a373;
        }
        .btn-primary:hover {
            background-color: #b88352;
            border-color: #b88352;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content w-100">
        <!-- Barre supérieure -->
        <header class="d-flex justify-content-between align-items-center py-3 mb-4 border-bottom px-4 rounded-4 shadow-sm sticky-header">
            <h4 class="mb-0 text-dark playfair fw-bold">
                <?php
                if($_SESSION['user_role'] === 'admin') echo "Espace Administrateur";
                elseif($_SESSION['user_role'] === 'employe') echo "Espace Employée";
                else echo "Espace Cliente";
                ?>
            </h4>
            <div class="d-flex align-items-center">
                <!-- Notifications Dropdown -->
                <div class="dropdown me-3">
                    <button class="btn btn-link text-dark position-relative p-0" type="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell fa-lg text-primary"></i>
                        <span id="notifCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="display: none; font-size: 0.6rem;">
                            0
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown border-0" aria-labelledby="notifDropdown">
                        <div class="notification-header">
                            <span>Notifications</span>
                            <button class="btn btn-sm btn-link text-primary p-0 text-decoration-none" id="markAllRead">Tout marquer comme lu</button>
                        </div>
                        <div id="notifList" style="max-height: 350px; overflow-y: auto;">
                            <div class="p-4 text-center text-muted">
                                <small>Aucune notification</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-none d-md-block text-end me-3">
                    <div class="fw-bold small"><?php echo htmlspecialchars($_SESSION['user_nom']); ?></div>
                    <div class="text-muted" style="font-size: 0.7rem;"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                </div>
            </div>
        </header>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            function loadNotifications() {
                fetch('/coiffure_salon/php/controllers/notification_controller.php?action=list')
                    .then(response => response.json())
                    .then(data => {
                        const countBadge = document.getElementById('notifCount');
                        const listContainer = document.getElementById('notifList');
                        
                        if (data.unread_count > 0) {
                            countBadge.innerText = data.unread_count;
                            countBadge.style.display = 'block';
                        } else {
                            countBadge.style.display = 'none';
                        }
                        
                        if (data.notifications && data.notifications.length > 0) {
                            listContainer.innerHTML = '';
                            data.notifications.forEach(n => {
                                const item = document.createElement('div');
                                item.className = `notification-item ${n.is_read == 0 ? 'unread' : ''}`;
                                
                                let iconClass = 'fa-info-circle text-info';
                                if (n.type === 'success') iconClass = 'fa-check-circle text-success';
                                if (n.type === 'warning') iconClass = 'fa-exclamation-triangle text-warning';
                                if (n.type === 'danger') iconClass = 'fa-times-circle text-danger';
                                
                                item.innerHTML = `
                                    <div class="icon"><i class="fas ${iconClass}"></i></div>
                                    <div class="content">
                                        <div class="title" style="font-size: 0.85rem;">${n.message}</div>
                                        <div class="time">${new Date(n.created_at).toLocaleString('fr-FR', {hour: '2-digit', minute:'2-digit', day:'2-digit', month:'short'})}</div>
                                    </div>
                                `;
                                listContainer.appendChild(item);
                            });
                        }
                    });
            }

            loadNotifications();
            // Refresh every 60 seconds
            setInterval(loadNotifications, 60000);

            document.getElementById('markAllRead').addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fetch('/coiffure_salon/php/controllers/notification_controller.php?action=mark_read')
                    .then(() => loadNotifications());
            });
        });
        </script>

        <!-- Affichage des alertes SweetAlert2 -->
        <?php
        $flash_success = get_flash_message('success');
        $flash_error = get_flash_message('error');
        if ($flash_success): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: '<?php echo addslashes($flash_success); ?>',
                        confirmButtonColor: '#d4a373'
                    });
                });
            </script>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: '<?php echo addslashes($flash_error); ?>',
                        confirmButtonColor: '#d4a373'
                    });
                });
            </script>
        <?php endif; ?>
