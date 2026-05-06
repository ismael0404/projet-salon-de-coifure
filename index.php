<?php
// Démarrer la session
session_start();

// Inclure les configurations et fonctions
require_once 'php/config/database.php';
require_once 'includes/functions.php';

// Récupérer les services depuis la base de données
$services = [];
try {
    $stmt = $pdo->query("SELECT * FROM services WHERE statut = 'actif' ORDER BY id_service LIMIT 4");
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $services = [];
}

// Récupérer les avis depuis la base de données
$avis = [];
try {
    $stmt = $pdo->query("
        SELECT a.*, u.nom, u.prenom 
        FROM avis a 
        JOIN clientes c ON a.id_cliente = c.id_cliente 
        JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur 
        WHERE a.statut = 'visible' 
        ORDER BY a.date_avis DESC 
        LIMIT 3
    ");
    $avis = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $avis = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>TYA STYLEX - Salon de Coiffure Professionnel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ========== NAVIGATION BAR AVEC LOGO ========== -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/images/ty.jpg" alt="Logo TYA STYLEX" class="navbar-logo" onerror="this.src='https://via.placeholder.com/45x45?text=TYA'">
            <span>TYA STYLEX</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#accueil">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="#tarifs">Tarifs</a></li>
                <li class="nav-item"><a class="nav-link" href="#galerie">Galerie</a></li>
                <li class="nav-item"><a class="nav-link" href="#a-propos">À propos</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                <?php if(is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_nom'] ?? 'Mon compte'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if($_SESSION['user_role'] == 'client'): ?>
                                <li><a class="dropdown-item" href="pages/client/dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                            <?php elseif($_SESSION['user_role'] == 'employe'): ?>
                                <li><a class="dropdown-item" href="pages/employe/dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                            <?php elseif($_SESSION['user_role'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="pages/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="php/controllers/auth_controller.php?action=logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="btn btn-outline-primary ms-lg-3" href="pages/connexion.php">
                            <i class="fas fa-sign-in-alt"></i> Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="pages/inscription.php">
                            <i class="fas fa-user-plus"></i> Inscription
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- ========== SECTION HERO / ACCUEIL ========== -->
<section id="accueil" class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container h-100">
        <div class="row h-100 align-items-center">
            <div class="col-lg-7 text-white" data-aos="fade-up" data-aos-duration="1000">
                <h6 class="text-uppercase text-primary mb-3 letter-spacing">
                    <i class="fas fa-spa"></i> Salon de coiffure premium
                </h6>
                <h1 class="display-3 fw-bold mb-4">
                    Révelez votre <span class="text-primary">beauté</span> <br>avec TYA STYLEX
                </h1>
                <p class="lead mb-4 opacity-90">
                    Découvrez une expérience unique dans notre salon de coiffure haut de gamme. 
                    Des professionnelles à votre écoute pour sublimer votre beauté.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="pages/inscription.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-calendar-check"></i> Prendre rendez-vous
                    </a>
                    <a href="#services" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-arrow-down"></i> Nos services
                    </a>
                </div>
                
                <!-- Statistiques rapides -->
                <div class="row mt-5 g-4">
                    <div class="col-4">
                        <div class="stat-item">
                            <h3 class="text-white fw-bold mb-0">500+</h3>
                            <p class="text-white-50 mb-0">Clientes satisfaites</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item">
                            <h3 class="text-white fw-bold mb-0">8</h3>
                            <p class="text-white-50 mb-0">Services exclusifs</p>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item">
                            <h3 class="text-white fw-bold mb-0">4.8</h3>
                            <p class="text-white-50 mb-0">Note moyenne</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== SECTION SERVICES ========== -->
<section id="services" class="services-section py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h6 class="text-primary text-uppercase letter-spacing">Nos prestations</h6>
            <h2 class="display-5 fw-bold">Services <span class="text-primary">exclusifs</span></h2>
            <div class="divider mx-auto"></div>
            <p class="text-muted w-75 mx-auto">
                Découvrez notre gamme complète de services coiffure pour femme, 
                réalisés par des professionnelles passionnées
            </p>
        </div>

        <div class="row g-4">
            <?php if(!empty($services)): ?>
                <?php foreach($services as $service): ?>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-card text-center p-4 rounded-4 shadow-sm">
                        <div class="service-icon mx-auto mb-3">
                            <i class="fas fa-cut fa-3x text-primary"></i>
                        </div>
                        <h5><?php echo htmlspecialchars($service['nom_service']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars(substr($service['description'], 0, 60)); ?>...</p>
                        <div class="d-flex justify-content-between mt-3">
                            <span><i class="far fa-clock"></i> <?php echo $service['duree']; ?> min</span>
                            <span class="fw-bold text-primary"><?php echo number_format($service['prix_standard'], 0, ',', ' '); ?> FCFA</span>
                        </div>
                        <a href="pages/inscription.php" class="btn btn-sm btn-outline-primary mt-3">Réserver</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Services par défaut si BDD non disponible -->
                <div class="col-md-6 col-lg-3" data-aos="fade-up">
                    <div class="service-card text-center p-4 rounded-4 shadow-sm">
                        <div class="service-icon mx-auto mb-3"><i class="fas fa-cut fa-3x text-primary"></i></div>
                        <h5>Coupe femme</h5>
                        <p class="text-muted small">Coupe personnalisée selon votre morphologie</p>
                        <div class="d-flex justify-content-between mt-3">
                            <span><i class="far fa-clock"></i> 45 min</span>
                            <span class="fw-bold text-primary">5 000 FCFA</span>
                        </div>
                        <a href="pages/inscription.php" class="btn btn-sm btn-outline-primary mt-3">Réserver</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-card text-center p-4 rounded-4 shadow-sm">
                        <div class="service-icon mx-auto mb-3"><i class="fas fa-braids fa-3x text-primary"></i></div>
                        <h5>Tresse classique</h5>
                        <p class="text-muted small">Tresses classiques réalisées avec soin</p>
                        <div class="d-flex justify-content-between mt-3">
                            <span><i class="far fa-clock"></i> 120 min</span>
                            <span class="fw-bold text-primary">2 000 FCFA</span>
                        </div>
                        <a href="pages/inscription.php" class="btn btn-sm btn-outline-primary mt-3">Réserver</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-card text-center p-4 rounded-4 shadow-sm">
                        <div class="service-icon mx-auto mb-3"><i class="fas fa-hand-sparkles fa-3x text-primary"></i></div>
                        <h5>Shampoing + Brushing</h5>
                        <p class="text-muted small">Lavage et brushing professionnel</p>
                        <div class="d-flex justify-content-between mt-3">
                            <span><i class="far fa-clock"></i> 60 min</span>
                            <span class="fw-bold text-primary">4 000 FCFA</span>
                        </div>
                        <a href="pages/inscription.php" class="btn btn-sm btn-outline-primary mt-3">Réserver</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="service-card text-center p-4 rounded-4 shadow-sm">
                        <div class="service-icon mx-auto mb-3"><i class="fas fa-palette fa-3x text-primary"></i></div>
                        <h5>Coloration</h5>
                        <p class="text-muted small">Coloration complète sans ammoniaque</p>
                        <div class="d-flex justify-content-between mt-3">
                            <span><i class="far fa-clock"></i> 90 min</span>
                            <span class="fw-bold text-primary">8 000 FCFA</span>
                        </div>
                        <a href="pages/inscription.php" class="btn btn-sm btn-outline-primary mt-3">Réserver</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ========== SECTION TARIFS ========== -->
<section id="tarifs" class="tarifs-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h6 class="text-primary text-uppercase letter-spacing">Nos tarifs</h6>
            <h2 class="display-5 fw-bold">Grille <span class="text-primary">tarifaire</span></h2>
            <div class="divider mx-auto"></div>
            <p class="text-muted w-75 mx-auto">
                Des prix adaptés à toutes les bourses, avec un système de fidélité avantageux
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="tarif-card text-center p-4 rounded-4 shadow bg-white">
                    <i class="fas fa-cut fa-3x text-primary mb-3"></i>
                    <h4>Coupe femme</h4>
                    <div class="prix mt-3">
                        <span class="h2 text-primary">5 000 FCFA</span>
                    </div>
                    <hr>
                    <p class="text-muted small">Durée: 45 min</p>
                    <a href="pages/inscription.php" class="btn btn-primary">Réserver</a>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="tarif-card text-center p-4 rounded-4 shadow bg-white">
                    <i class="fas fa-braids fa-3x text-primary mb-3"></i>
                    <h4>Tresse classique</h4>
                    <div class="prix mt-3">
                        <span class="h2 text-primary">2 000 FCFA</span>
                    </div>
                    <hr>
                    <p class="text-muted small">Durée: 120 min</p>
                    <a href="pages/inscription.php" class="btn btn-primary">Réserver</a>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="tarif-card text-center p-4 rounded-4 shadow bg-white">
                    <i class="fas fa-hand-sparkles fa-3x text-primary mb-3"></i>
                    <h4>Shampoing + Brushing</h4>
                    <div class="prix mt-3">
                        <span class="h2 text-primary">4 000 FCFA</span>
                    </div>
                    <hr>
                    <p class="text-muted small">Durée: 60 min</p>
                    <a href="pages/inscription.php" class="btn btn-primary">Réserver</a>
                </div>
            </div>
        </div>

        <!-- Système de fidélité -->
        <div class="row mt-5" data-aos="fade-up" data-aos-delay="300">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <i class="fas fa-gem text-primary fa-3x"></i>
                            <h3 class="mt-2">Système de fidélité</h3>
                            <p>Plus vous revenez, plus vous économisez !</p>
                        </div>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="fidelite-card p-3 rounded-3">
                                    <div class="badge bg-secondary mb-2">Niveau Bronze</div>
                                    <h4>0 - 4 RDV</h4>
                                    <p class="h3 text-muted">0%</p>
                                    <small>de réduction</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="fidelite-card p-3 rounded-3" style="background: rgba(212, 163, 115, 0.1);">
                                    <div class="badge bg-primary mb-2">Niveau Argent</div>
                                    <h4>5 - 9 RDV</h4>
                                    <p class="h3 text-primary">10%</p>
                                    <small>de réduction</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="fidelite-card p-3 rounded-3" style="background: rgba(255, 193, 7, 0.1);">
                                    <div class="badge bg-warning mb-2">Niveau Or</div>
                                    <h4>10+ RDV</h4>
                                    <p class="h3 text-warning">20%</p>
                                    <small>de réduction</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== SECTION GALERIE ========== -->
<section id="galerie" class="galerie-section py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h6 class="text-primary text-uppercase letter-spacing">Notre travail</h6>
            <h2 class="display-5 fw-bold">Galerie <span class="text-primary">photo</span></h2>
            <div class="divider mx-auto"></div>
            <p class="text-muted w-75 mx-auto">
                Découvrez quelques-unes de nos réalisations
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="galerie-item">
                    <img src="https://images.unsplash.com/photo-1562322140-8baeececf3df?w=600" alt="Coiffure 1" class="img-fluid">
                    <div class="galerie-overlay">
                        <div class="galerie-text">
                            <h5>Coupe tendance</h5>
                            <p>Style moderne et élégant</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="galerie-item">
                    <img src="https://images.unsplash.com/photo-1595476108010-b4d1f102b1b1?w=600" alt="Coiffure 2" class="img-fluid">
                    <div class="galerie-overlay">
                        <div class="galerie-text">
                            <h5>Tresses élégantes</h5>
                            <p>Faites main avec soin</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="galerie-item">
                    <img src="https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=600" alt="Coiffure 3" class="img-fluid">
                    <div class="galerie-overlay">
                        <div class="galerie-text">
                            <h5>Coloration</h5>
                            <p>Cheveux brillants et soyeux</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== SECTION À PROPOS ========== -->
<section id="a-propos" class="apropos-section py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0" data-aos="fade-right">
                <img src="https://images.unsplash.com/photo-1585747860715-2ba37e788b70?w=600" alt="Notre salon" class="img-fluid rounded-4 shadow-lg">
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <h6 class="text-primary text-uppercase letter-spacing">À propos de nous</h6>
                <h2 class="display-5 fw-bold mb-4">TYA STYLEX <br><span class="text-primary">L'excellence au féminin</span></h2>
                <p class="lead mb-4">
                    Fondé par des passionnées de la coiffure, TYA STYLEX est bien plus qu'un simple salon. 
                    C'est un espace dédié à la beauté et au bien-être de la femme.
                </p>
                <div class="row mb-4">
                    <div class="col-6">
                        <div class="d-flex gap-2 mb-3">
                            <i class="fas fa-check-circle text-primary fa-lg"></i>
                            <span>Professionnelles diplômées</span>
                        </div>
                        <div class="d-flex gap-2 mb-3">
                            <i class="fas fa-check-circle text-primary fa-lg"></i>
                            <span>Produits haut de gamme</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex gap-2 mb-3">
                            <i class="fas fa-check-circle text-primary fa-lg"></i>
                            <span>Ambiance chaleureuse</span>
                        </div>
                        <div class="d-flex gap-2 mb-3">
                            <i class="fas fa-check-circle text-primary fa-lg"></i>
                            <span>Service personnalisé</span>
                        </div>
                    </div>
                </div>
                <a href="#contact" class="btn btn-primary btn-lg">
                    <i class="fas fa-envelope"></i> Contactez-nous
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ========== SECTION TEMOIGNAGES ========== -->
<section id="temoignages" class="temoignages-section py-5">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h6 class="text-primary text-uppercase letter-spacing">Ils parlent de nous</h6>
            <h2 class="display-5 fw-bold">Avis <span class="text-primary">clientes</span></h2>
            <div class="divider mx-auto"></div>
        </div>

        <div class="row g-4">
            <?php if(!empty($avis)): ?>
                <?php foreach($avis as $avis_item): ?>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="avis-card p-4 rounded-4 shadow-sm bg-white">
                        <div class="d-flex gap-2 mb-3">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <?php if($i <= $avis_item['note']): ?>
                                    <i class="fas fa-star text-warning"></i>
                                <?php else: ?>
                                    <i class="far fa-star text-warning"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-3">"<?php echo htmlspecialchars(substr($avis_item['commentaire'], 0, 100)); ?>..."</p>
                        <strong>- <?php echo htmlspecialchars($avis_item['prenom'] . ' ' . $avis_item['nom']); ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-md-4" data-aos="fade-up">
                    <div class="avis-card p-4 rounded-4 shadow-sm bg-white">
                        <div class="d-flex gap-2 mb-3">
                            <i class="fas fa-star text-warning"></i><i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i><i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="mb-3">"Service impeccable, je suis ravie de ma coupe !"</p>
                        <strong>- Aminata K.</strong>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="avis-card p-4 rounded-4 shadow-sm bg-white">
                        <div class="d-flex gap-2 mb-3">
                            <i class="fas fa-star text-warning"></i><i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i><i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="mb-3">"Tresses magnifiques, je recommande vivement !"</p>
                        <strong>- Fatou D.</strong>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="avis-card p-4 rounded-4 shadow-sm bg-white">
                        <div class="d-flex gap-2 mb-3">
                            <i class="fas fa-star text-warning"></i><i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i><i class="fas fa-star text-warning"></i>
                            <i class="far fa-star text-warning"></i>
                        </div>
                        <p class="mb-3">"Très bonne accueil et professionnalisme"</p>
                        <strong>- Mariam S.</strong>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ========== SECTION CONTACT ========== -->
<section id="contact" class="contact-section py-5 bg-dark text-white">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h6 class="text-primary text-uppercase letter-spacing">Prenez rendez-vous</h6>
            <h2 class="display-5 fw-bold text-white">Contactez-<span class="text-primary">nous</span></h2>
            <div class="divider mx-auto bg-white"></div>
        </div>

        <div class="row">
            <div class="col-lg-5 mb-4 mb-lg-0" data-aos="fade-right">
                <div class="contact-info">
                    <div class="d-flex mb-4 gap-3">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5>Notre adresse</h5>
                            <p class="text-white-50">Cocody, Abidjan - Côte d'Ivoire</p>
                        </div>
                    </div>
                    <div class="d-flex mb-4 gap-3">
                        <div class="contact-icon">
                            <i class="fas fa-phone-alt fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5>Téléphone</h5>
                            <p class="text-white-50">+225 07 07 07 07 07</p>
                        </div>
                    </div>
                    <div class="d-flex mb-4 gap-3">
                        <div class="contact-icon">
                            <i class="fas fa-envelope fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5>Email</h5>
                            <p class="text-white-50">contact@tyastylex.com</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="contact-icon">
                            <i class="fas fa-clock fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5>Horaires</h5>
                            <p class="text-white-50 mb-0">Lundi - Samedi : 08h00 - 18h00</p>
                            <p class="text-white-50">Dimanche : Fermé</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <form id="contactForm" class="contact-form" method="POST" action="php/contact.php">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="nom" class="form-control" placeholder="Votre nom" required>
                        </div>
                        <div class="col-md-6">
                            <input type="email" name="email" class="form-control" placeholder="Votre email" required>
                        </div>
                        <div class="col-12">
                            <input type="text" name="sujet" class="form-control" placeholder="Sujet">
                        </div>
                        <div class="col-12">
                            <textarea name="message" class="form-control" rows="5" placeholder="Votre message" required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-paper-plane"></i> Envoyer le message
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ========== FOOTER AVEC LOGO ========== -->
<footer class="footer-section py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-4 text-center text-md-start">
                <img src="php/config/assets/images/ty.jpg" alt="Logo TYA STYLEX" class="footer-logo" onerror="this.src='https://via.placeholder.com/40x40?text=TYA'">
            </div>
            <div class="col-md-4 text-center">
                <p class="mb-0">&copy; 2024 TYA STYLEX - Tous droits réservés</p>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- ========== SCRIPTS ========== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Initialisation AOS
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });

    // Navigation scroll
    window.addEventListener('scroll', function() {
        const nav = document.getElementById('mainNav');
        if (window.scrollY > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
    });

    // Smooth scroll pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if(target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
</script>
</body>
</html>