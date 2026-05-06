<?php
// includes/sidebar.php
if (!is_logged_in()) {
    redirect('/coiffure_salon/pages/connexion.php');
}

$role = $_SESSION['user_role'];
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar d-flex flex-column flex-shrink-0 p-3 bg-dark text-white" style="width: 280px; min-height: 100vh;">
    <a href="/coiffure_salon/index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <img src="/coiffure_salon/assets/images/ty.jpg" alt="Logo" class="rounded-circle me-2" width="40" height="40" onerror="this.src='https://via.placeholder.com/40?text=TYA'">
        <span class="fs-5 fw-bold text-primary">TYA STYLEX</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        
        <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a href="/coiffure_salon/pages/admin/dashboard.php" class="nav-link text-white <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                </a>
            </li>
            <li>
                <a href="/coiffure_salon/pages/admin/rendezvous.php" class="nav-link text-white <?php echo $current_page === 'rendezvous.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt me-2"></i> Tous les RDV
                </a>
            </li>
            <li>
                <a href="/coiffure_salon/pages/admin/clientes.php" class="nav-link text-white <?php echo $current_page === 'clientes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i> Clientes
                </a>
            </li>
            <li>
                <a href="/coiffure_salon/pages/admin/employes.php" class="nav-link text-white <?php echo $current_page === 'employes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie me-2"></i> Employées
                </a>
            </li>
            <li>
                <a href="/coiffure_salon/pages/admin/services.php" class="nav-link text-white <?php echo $current_page === 'services.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cut me-2"></i> Prestations
                </a>
            </li>
            
        <?php elseif ($role === 'employe'): ?>
            <li class="nav-item">
                <a href="/coiffure_salon/pages/employe/dashboard.php" class="nav-link text-white <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Mon Planning
                </a>
            </li>
            <li>
                <a href="/coiffure_salon/pages/employe/rendezvous.php" class="nav-link text-white <?php echo $current_page === 'rendezvous.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list me-2"></i> Mes RDV
                </a>
            </li>
            <li>
                <a href="/coiffure_salon/pages/employe/profil.php" class="nav-link text-white <?php echo $current_page === 'profil.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user me-2"></i> Mon Profil
                </a>
            </li>
            
        <?php elseif ($role === 'client'): ?>
            <li class="nav-item">
                <a href="/coiffure_salon/pages/client/dashboard.php" class="nav-link text-white <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Vue d'ensemble
                </a>
            </li>
            <li>
                <a href="/coiffure_salon/pages/client/prendre_rdv.php" class="nav-link text-white <?php echo $current_page === 'prendre_rdv.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-plus me-2"></i> Prendre un RDV
                </a>
            </li>
            <li>
                <a href="/coiffure_salon/pages/client/mes_rdv.php" class="nav-link text-white <?php echo $current_page === 'mes_rdv.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clock me-2"></i> Mon Historique
                </a>
            </li>
            <li>
                <a href="/coiffure_salon/pages/client/profil.php" class="nav-link text-white <?php echo $current_page === 'profil.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user me-2"></i> Mon Profil
                </a>
            </li>
        <?php endif; ?>
        
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <?php 
            $avatar = !empty($_SESSION['user_avatar']) ? $_SESSION['user_avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($_SESSION['user_nom']).'&background=random';
            ?>
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" width="32" height="32" class="rounded-circle me-2 object-fit-cover">
            <strong><?php echo htmlspecialchars($_SESSION['user_nom']); ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="/coiffure_salon/index.php">Retour au site</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/coiffure_salon/php/controllers/auth_controller.php?action=logout">Déconnexion</a></li>
        </ul>
    </div>
</div>

<style>
.sidebar .nav-link.active {
    background-color: #d4a373 !important; /* Couleur primaire du thème */
    color: white !important;
}
.sidebar .nav-link:hover {
    background-color: rgba(212, 163, 115, 0.2);
}
.text-primary {
    color: #d4a373 !important;
}
</style>
